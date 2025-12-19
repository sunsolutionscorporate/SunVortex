<?php

// require_once __DIR__ . '/../Exceptions/DBException.php';
// require_once __DIR__ . '/QueryManager.php';

/**
 * Database Manager - Menangani multiple koneksi database
 * Inspirasi: CodeIgniter 3
 * Kompatibel: PHP 7.3+
 * 
 * @uses QueryCache
 * @uses QueryProfiler
 * @uses TransactionManager
 * @uses QueryBuilder
 * @uses QueryResult
 */
class Database
{
   private static $instance = null;
   private $connections = array();
   private $active_connection = null;
   private $config = array();
   // Integration helpers
   protected $profiler;
   protected $cache;
   protected $transactionManager;

   private function __construct()
   {
      // Private constructor untuk Singleton
   }

   /**
    * Mendapatkan instance Database Manager (Singleton)
    * @return Database
    * @phpstan-return Database
    */
   public static function init()
   {
      if (self::$instance === null) {
         self::$instance = new self();
         self::$instance->loadConfig();
         // Initialize QueryProfiler, QueryCache and TransactionManager

         try {
            // Log initialization
            if (class_exists('Logger')) {
               Logger::debug('Database initialized with ' . count(self::$instance->config) . ' config(s)');
            }
            /** @var QueryProfiler $profiler */
            self::$instance->profiler = new QueryProfiler();

            // Read cache config flags from application config/.env
            $defaultTtl = null;
            try {
               $cfgTtl = config('DEFAULT_QUERY_CACHE_TTL');
               if ($cfgTtl !== null) {
                  $defaultTtl = (int)$cfgTtl;
               }
            } catch (Exception $e) {
               $defaultTtl = null;
            }
            if ($defaultTtl === null) $defaultTtl = 600;

            /** @var QueryCache $cache */
            self::$instance->cache = new QueryCache('file', $defaultTtl);

            // Global enable flag - allows turning cache off via config
            $enableCache = true;
            try {
               $raw = config('ENABLE_QUERY_CACHE');
               if ($raw !== null) {
                  $enableCache = filter_var($raw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                  if ($enableCache === null) $enableCache = true;
               }
            } catch (Exception $e) {
               $enableCache = true;
            }
            if (!$enableCache) {
               self::$instance->cache->disable();
            }

            // Ensure a default connection exists for TransactionManager
            $pdo = null;
            try {
               $pdo = self::$instance->getConnection();
            } catch (Exception $e) {
               // ignore - transaction manager can be created later when connection exists
            }
            if ($pdo instanceof PDO) {
               self::$instance->transactionManager = new TransactionManager($pdo);
            } else {
               self::$instance->transactionManager = null;
            }
         } catch (Throwable $t) {
            // Non-fatal: if QueryManager classes are missing, keep Database functional
            if (class_exists('Logger')) {
               Logger::warning('QueryManager init warning: ' . $t->getMessage());
            }
            self::$instance->profiler = null;
            self::$instance->cache = null;
            self::$instance->transactionManager = null;
         }
      }
      return self::$instance;
   }

   /**
    * Alias mirip CodeIgniter4: Database::connect($group = null)
    * Mengembalikan instance Database yang sudah ter-load dan jika $group diberikan,
    * akan melakukan koneksi ke grup tersebut sehingga panggilan berikutnya tanpa param
    * akan menggunakan koneksi itu.
    */
   public static function connectTo($group = null)
   {
      $db = self::init();
      if ($group !== null) {
         $db->connect($group);
      }
      return $db;
   }

   /**
    * Load konfigurasi database dari .env
    * @throws \DBException
    */
   private function loadConfig()
   {
      $db_config = config('DB_CONFIG');
      if (empty($db_config)) {
         /** @var \DBException $ex */
         throw new DBException("DB_CONFIG tidak ditemukan di .env");
      }

      // Jika DB_CONFIG adalah string (dari .env), parse sebagai JSON
      if (is_string($db_config)) {
         // Remove single quotes jika ada
         $db_config = trim($db_config, "'\"");

         $this->config = json_decode($db_config, true);
         if (json_last_error() !== JSON_ERROR_NONE) {
            $error_msg = json_last_error_msg();
            throw new DBException("DB_CONFIG JSON tidak valid: " . $error_msg . " | Raw: " . substr($db_config, 0, 100));
         }
      } else {
         $this->config = $db_config;
      }

      if (empty($this->config)) {
         throw new DBException("DB_CONFIG kosong atau tidak valid");
      }
   }

   /**
    * Koneksi ke database tertentu
    * @param mixed $group Nama atau index dari database config
    * @return PDO
    */
   public function connect($group = 0)
   {
      // Jika sudah terkoneksi, kembalikan koneksi yang ada
      if (isset($this->connections[$group])) {
         Logger::debug('Reusing existing DB connection for group: ' . $group);
         return $this->connections[$group];
      }

      // Validasi config
      $config = null;
      if (is_int($group)) {
         if (!isset($this->config[$group])) {
            throw new DBException("Database config index '{$group}' tidak ditemukan");
         }
         $config = $this->config[$group];
      } else {
         // Cari berdasarkan database name
         foreach ($this->config as $cfg) {
            if ((isset($cfg['database']) ? $cfg['database'] : null) === $group) {
               $config = $cfg;
               break;
            }
         }
         if ($config === null) {
            throw new DBException("Database config '{$group}' tidak ditemukan");
         }
      }

      $driver = strtolower(isset($config['driver']) ? $config['driver'] : 'mysql');



      try {
         switch ($driver) {
            case 'mysql':
               $connection = $this->connectMySQL($config);
               break;
            case 'pgsql':
               $connection = $this->connectPostgreSQL($config);
               break;
            case 'sqlite':
               $connection = $this->connectSQLite($config);
               break;
            default:
               throw new DBException("Driver database '{$driver}' tidak didukung");
         }

         // Simpan koneksi
         $this->connections[$group] = $connection;
         $this->active_connection = $group;
         // Log attempt
         if (class_exists('Logger')) {
            $host = isset($config['host']) ? $config['host'] : 'localhost';
            $dbName = isset($config['database']) ? $config['database'] : '(unnamed)';
            $user = isset($config['username']) ? $config['username'] : '(root)';
            Logger::debug("Database Connecting to [group={$group}] driver={$driver} host={$host} db={$dbName} user={$user}");
         }

         return $connection;
      } catch (PDOException $e) {
         $host = isset($config['host']) ? $config['host'] : 'localhost';
         $dbName = isset($config['database']) ? $config['database'] : '(unnamed)';
         $user = isset($config['username']) ? $config['username'] : '(root)';
         throw new DBException('DB connect failed [group=' . $group . '] message: ' . $e->getMessage());
         // throw new DBException("DB connect failed [group={$group}]  driver={$driver} host={$host} db={$dbName} user={$user}");
      }
   }

   /**
    * Koneksi ke MySQL menggunakan PDO
    */
   private function connectMySQL(array $config)
   {
      $dsn = sprintf(
         'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
         isset($config['host']) ? $config['host'] : 'localhost',
         isset($config['port']) ? $config['port'] : 3306,
         $config['database']
      );

      $username = isset($config['username']) ? $config['username'] : 'root';
      $password = isset($config['password']) ? $config['password'] : '';

      $options = array(
         PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
         PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
         PDO::ATTR_EMULATE_PREPARES => false,
      );

      return new PDO($dsn, $username, $password, $options);
   }

   /**
    * Koneksi ke PostgreSQL menggunakan PDO
    */
   private function connectPostgreSQL(array $config)
   {
      $dsn = sprintf(
         'pgsql:host=%s;port=%d;dbname=%s',
         isset($config['host']) ? $config['host'] : 'localhost',
         isset($config['port']) ? $config['port'] : 5432,
         $config['database']
      );

      $username = isset($config['username']) ? $config['username'] : 'postgres';
      $password = isset($config['password']) ? $config['password'] : '';

      $options = array(
         PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
         PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      );

      return new PDO($dsn, $username, $password, $options);
   }

   /**
    * Koneksi ke SQLite menggunakan PDO
    */
   private function connectSQLite(array $config)
   {
      $path = $config['database'];

      // Jika path relative, letakkan di folder storage
      if (!$this->str_starts_with($path, '/') && !preg_match('/^[a-zA-Z]:/', $path)) {
         $path = config('PATH_DISK') . $path;
      }

      $dsn = 'sqlite:' . $path;

      $options = array(
         PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
         PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      );

      return new PDO($dsn, null, null, $options);
   }

   /**
    * Helper untuk str_starts_with (PHP < 8)
    */
   private function str_starts_with($haystack, $needle)
   {
      return substr($haystack, 0, strlen($needle)) === $needle;
   }

   /**
    * Mendapatkan koneksi aktif
    */
   public function getConnection($group = null)
   {
      if ($group === null) {
         $group = $this->active_connection !== null ? $this->active_connection : 0;
      }

      if (!isset($this->connections[$group])) {
         $this->connect($group);
      }

      return $this->connections[$group];
   }

   /**
    * Get profiler instance (may be null)
    */
   public function getProfiler()
   {
      return $this->profiler;
   }

   /**
    * Get cache instance (may be null)
    */
   public function getCache()
   {
      return $this->cache;
   }

   /**
    * Get TransactionManager instance (may be null)
    */
   public function getTransactionManager()
   {
      // Lazily create TransactionManager if connection becomes available later
      if ($this->transactionManager === null) {
         try {
            $pdo = $this->getConnection();
            if ($pdo instanceof PDO) {
               $this->transactionManager = new TransactionManager($pdo);
            }
         } catch (Exception $e) {
            // ignore
         }
      }
      return $this->transactionManager;
   }

   /**
    * queryWithCache - helper untuk SELECT dengan optional cache
    * @param string $sql
    * @param array $params
    * @param string|null $cacheKey
    * @param int|null $ttl
    * @param mixed $group
    * @return array
    */
   public function queryWithCache($sql, $params = [], $cacheKey = null, $ttl = null, $group = null)
   {
      if ($cacheKey && $this->cache) {
         $cached = $this->cache->get($cacheKey);
         if ($cached !== null) {
            return $cached;
         }
      }

      $connection = $this->getConnection($group);
      $start = microtime(true);
      $stmt = $connection->prepare($sql);
      $stmt->execute($params);
      $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
      $duration = (microtime(true) - $start) * 1000;

      if ($this->profiler) {
         $this->profiler->log($sql, $params, $duration);
      }

      if ($cacheKey && $this->cache) {
         $this->cache->put($cacheKey, $rows, $ttl);
      }

      return $rows;
   }

   /**
    * executeWithProfiling - helper untuk INSERT/UPDATE/DELETE
    * @param string $sql
    * @param array $params
    * @param mixed $group
    * @return int affected rows
    */
   public function executeWithProfiling($sql, $params = [], $group = null)
   {
      $connection = $this->getConnection($group);
      $start = microtime(true);
      $stmt = $connection->prepare($sql);
      $stmt->execute($params);
      $duration = (microtime(true) - $start) * 1000;
      if ($this->profiler) {
         $this->profiler->log($sql, $params, $duration);
      }
      return $stmt->rowCount();
   }

   /**
    * Convenience: return first row from query
    */
   public function first($sql, $params = [], $group = null)
   {
      $rows = $this->queryWithCache($sql, $params, null, null, $group);
      return $rows[0] ?? null;
   }

   /**
    * Switch ke database lain
    */
   public function switchTo($group)
   {
      $this->connect($group);
      $this->active_connection = $group;
      return $this;
   }

   /**
    * Test koneksi
    */
   public function testConnection($group = null)
   {
      try {
         $connection = $this->getConnection($group);
         $connection->query('SELECT 1');
         return true;
      } catch (Exception $e) {
         return false;
      }
   }

   /**
    * Mendapatkan semua config
    */
   public function getConfig()
   {
      return $this->config;
   }

   /**
    * Eksekusi query mentah dan kembalikan QueryResult (mirip CI4: $this->db->query(...))
    * @param string $sql
    * @param array $params
    * @param mixed $group
    * @return QueryResult
    */
   public function query(string $sql, array $params = array(), $group = null)
   {
      $connection = $this->getConnection($group);
      if (class_exists('Logger')) {
         Logger::debug('DBQuery: ', ['sql' => $sql, 'params' => $params, 'group' => $group]);
      }
      try {
         $stmt = $connection->prepare($sql);
         $stmt->execute($params);
         if (class_exists('Logger')) {
            $sqlStr = DBException::toQueryStr($sql, $params);
            Logger::info('DBQuery executed: "', $sqlStr, '"');
         }
         return new QueryResult($stmt);
      } catch (PDOException $err) {
         throw new DBException("DBQuery failed:", $err, $sql, $params);
      }
   }

   /**
    * Dapatkan QueryBuilder untuk tabel tertentu (mirip CI4: $this->db->table('name'))
    * @param string $table
    * @param mixed $group
    * @return QueryBuilder
    */
   public function table(string $table, $group = null)
   {
      $connection = $this->getConnection($group);
      // if (class_exists('Logger')) {
      //    Logger::debug('Getting QueryBuilder for table: ' . $table, ['group' => $group]);
      // }
      $qb = new QueryBuilder($connection);
      return $qb->from($table);
   }

   /**
    * Dapatkan QueryBuilder kosong (tanpa table)
    */
   public function getQueryBuilder($group = null)
   {
      $connection = $this->getConnection($group);
      return new QueryBuilder($connection);
   }

   /**
    * Escape value using PDO::quote — convenience wrapper.
    * Return quoted string including surrounding quotes.
    */
   public function escape($value, $group = null)
   {
      $pdo = $this->getConnection($group);
      return $pdo->quote($value);
   }

   /**
    * Escape string for use in LIKE clauses.
    * This only escapes SQL wildcard characters (%, _) and the escape char itself,
    * but does NOT add surrounding % — caller should add those as needed.
    */
   public function escapeLikeString($str, $escape_char = '!')
   {
      // escape the escape char first
      $search = [$escape_char, '%', '_'];
      $replace = [$escape_char . $escape_char, $escape_char . '%', $escape_char . '_'];
      return str_replace($search, $replace, $str);
   }

   /**
    * Execute raw SQL statement
    */
   public function statement(string $sql, array $params = []): bool
   {
      $result = $this->query($sql, $params);
      return $result instanceof QueryResult;
   }

   /**
    * Select multiple rows
    */
   public function select(string $sql, array $params = []): array
   {
      $result = $this->query($sql, $params);
      return $result instanceof QueryResult ? $result->getResultArray() : [];
   }

   /**
    * Select single row
    */
   public function selectOne(string $sql, array $params = [])
   {
      $result = $this->query($sql, $params);
      return $result instanceof QueryResult ? $result->getFirstRow() : null;
   }

   /**
    * Insert row(s)
    */
   public function insert(string $sql, array $params = []): int
   {
      $this->query($sql, $params);
      return $this->getLastInsertId();
   }

   /**
    * Update row(s)
    */
   public function update(string $sql, array $params = []): int
   {
      $result = $this->query($sql, $params);
      return $result instanceof QueryResult ? $result->rowCount() : 0;
   }

   /**
    * Delete row(s)
    */
   public function delete(string $sql, array $params = []): int
   {
      $result = $this->query($sql, $params);
      return $result instanceof QueryResult ? $result->rowCount() : 0;
   }

   /**
    * Get last insert ID
    */
   public function getLastInsertId(): int
   {
      $pdo = $this->getPdo();
      return (int)$pdo->lastInsertId();
   }

   /**
    * Get PDO instance
    */
   public function getPdo(): PDO
   {
      $conn = $this->getConnection();
      return $conn instanceof PDO ? $conn : $conn->getConnection();
   }

   /**
    * Begin transaction
    */
   public function beginTransaction(): void
   {
      $this->getTransactionManager()->begin();
   }

   /**
    * Commit transaction
    */
   public function commit(): void
   {
      $this->getTransactionManager()->commit();
   }

   /**
    * Rollback transaction
    */
   public function rollBack(): void
   {
      $this->getTransactionManager()->rollback();
   }
}

// Initialize default connection
// Uncomment di Bootstrap.php jika ingin lazy load
// Database::init();
