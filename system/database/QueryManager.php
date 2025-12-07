<?php

/**
 * QueryCache untuk handle caching query results
 * Support TTL dan tag-based invalidation
 */
class QueryCache
{
   protected $driver;
   protected $ttl = 3600;
   protected $enabled = true;
   protected $cacheDir = null;
   protected $tags = []; // Tags untuk cache entry saat ini

   /**
    * @param string $driver
    * @param int $ttl
    */
   public function __construct($driver = 'file', $ttl = 3600)
   {
      $this->driver = $driver;
      $this->ttl = $ttl;

      // Initialize cache directory for file driver
      if ($this->driver === 'file') {
         $this->cacheDir = DISK_PATH . '.cache/query/';
         @mkdir($this->cacheDir, 0755, true);
      }
   }

   /**
    * @param string $key
    * @return mixed
    */
   public function get($key)
   {
      if (!$this->enabled) return null;
      if ($this->driver === 'file') {
         $file = $this->getCacheFile($key);
         if (!file_exists($file)) return null;

         $contents = @file_get_contents($file);
         if ($contents === false) return null;

         $data = @unserialize($contents);

         // New-format: array with ['expires' => timestamp|0, 'value' => mixed]
         if (is_array($data) && array_key_exists('expires', $data) && array_key_exists('value', $data)) {
            if ($data['expires'] === 0 || $data['expires'] > time()) {
               return $data['value'];
            }
            // expired - remove file
            @unlink($file);
            return null;
         }

         // Backwards-compat: older files that store raw serialized value
         if ((time() - filemtime($file)) < $this->ttl) {
            return @unserialize($contents);
         }
      }
      return null;
   }

   /**
    * @param string $key
    * @param mixed $value
    * @param int|null $ttl
    */
   public function put($key, $value, $ttl = null)
   {
      if (!$this->enabled) return false;
      $ttl = $ttl ?? $this->ttl;
      if ($this->driver === 'file') {
         $file = $this->getCacheFile($key);
         @mkdir(dirname($file), 0755, true);
         $payload = [
            'expires' => $ttl > 0 ? (time() + (int)$ttl) : 0,
            'value' => $value,
            'tags' => $this->tags, // Simpan tags dalam file cache
         ];
         $this->tags = []; // Reset tags setelah disimpan
         return @file_put_contents($file, serialize($payload)) !== false;
      }
      return false;
   }

   /**
    * @param string $key
    */
   public function forget($key)
   {
      if ($this->driver === 'file') {
         $file = $this->getCacheFile($key);
         if (file_exists($file)) {
            @unlink($file);
         }
      }
      return true;
   }

   public function flush()
   {
      if ($this->driver === 'file') {
         $dir = DISK_PATH . '.cache/query/';
         if (is_dir($dir)) {
            array_map('unlink', glob($dir . '*.cache'));
         }
      }
      return true;
   }

   /**
    * @param string $key
    * @return string
    */
   protected function getCacheFile($key)
   {
      $hash = md5($key);
      return $this->cacheDir . $hash . '.cache';
   }

   public function disable()
   {
      $this->enabled = false;
      return $this;
   }

   public function enable()
   {
      $this->enabled = true;
      return $this;
   }

   /**
    * Get driver being used
    * 
    * @return string
    */
   public function getDriver()
   {
      return $this->driver;
   }

   /**
    * Set tags untuk cache entry berikutnya (fluent interface)
    * 
    * @param array|string $tags
    * @return $this
    */
   public function tags($tags)
   {
      if (is_string($tags)) {
         $this->tags = [$tags];
      } else {
         $this->tags = is_array($tags) ? $tags : [];
      }
      return $this;
   }

   /**
    * Invalidate/flush semua cache dengan tag tertentu
    * 
    * @param string|array $tags
    * @return bool
    */
   public function flushTag($tags)
   {
      if (is_string($tags)) {
         $tags = [$tags];
      }

      if ($this->driver === 'file') {
         $cacheDir = DISK_PATH . '.cache/query/';
         $files = @glob($cacheDir . '*.cache');

         if (is_array($files)) {
            foreach ($files as $file) {
               try {
                  $content = @file_get_contents($file);
                  if ($content !== false) {
                     $data = @unserialize($content);
                     if (is_array($data) && isset($data['tags']) && is_array($data['tags'])) {
                        // Check jika ada kecocokan tag
                        foreach ($tags as $tag) {
                           if (in_array($tag, $data['tags'])) {
                              @unlink($file);
                              continue 2; // Lanjut ke file berikutnya
                           }
                        }
                     }
                  }
               } catch (Exception $e) {
                  // Ignore errors
               }
            }
         }
      }

      return true;
   }

   /**
    * Invalidate cache untuk tabel tertentu
    * 
    * @param string|array $tables
    * @return bool
    */
   public function flushTable($tables)
   {
      if (is_string($tables)) {
         $tables = [$tables];
      }

      // Setiap table di-tag sebagai "table:{table_name}"
      $tags = array_map(function ($table) {
         return 'table:' . $table;
      }, $tables);

      return $this->flushTag($tags);
   }
}

/**
 * QueryProfiler untuk log dan monitor queries
 */
class QueryProfiler
{
   protected $queries = [];
   protected $enabled = true;

   /**
    * @param string $sql
    * @param array $params
    * @param float $duration
    */
   public function log($sql, $params, $duration)
   {
      if (!$this->enabled) return;
      $this->queries[] = [
         'sql' => $sql,
         'params' => $params,
         'duration' => $duration,
         'timestamp' => time(),
      ];
   }

   public function getQueries()
   {
      return $this->queries;
   }

   /**
    * @param float $threshold
    * @return array
    */
   public function getSlowQueries($threshold = 1000)
   {
      return array_filter($this->queries, function ($q) use ($threshold) {
         return $q['duration'] >= $threshold;
      });
   }

   public function getTotalTime()
   {
      return array_sum(array_column($this->queries, 'duration'));
   }

   public function getQueryCount()
   {
      return count($this->queries);
   }

   public function reset()
   {
      $this->queries = [];
      return $this;
   }

   public function disable()
   {
      $this->enabled = false;
      return $this;
   }

   public function enable()
   {
      $this->enabled = true;
      return $this;
   }
}

/**
 * Database Transaction Manager
 */
class TransactionManager
{
   protected $pdo;
   protected $transactions = [];
   protected $afterCommit = [];

   /**
    * @param PDO $pdo
    */
   public function __construct(PDO $pdo)
   {
      $this->pdo = $pdo;
   }

   /**
    * Begin transaction atau savepoint jika sudah ada transaction aktif
    */
   public function begin($name = null)
   {
      if (empty($this->transactions)) {
         $this->pdo->beginTransaction();
         $this->transactions[] = 'main';
         if (class_exists('Logger')) {
            Logger::debug('Database transaction started');
         }
      } else {
         $name = $name ?? 'sp_' . count($this->transactions);
         $this->pdo->exec("SAVEPOINT $name");
         $this->transactions[] = $name;
         if (class_exists('Logger')) {
            Logger::debug('Savepoint created: ' . $name);
         }
      }
      return $this;
   }

   /**
    * Commit transaction atau release savepoint
    */
   public function commit()
   {
      if (empty($this->transactions)) return false;

      $name = array_pop($this->transactions);
      if ($name === 'main') {
         $this->pdo->commit();
         if (class_exists('Logger')) {
            Logger::debug('Database transaction committed');
         }

         // Execute after-commit callbacks
         foreach ($this->afterCommit as $cb) {
            try {
               call_user_func($cb);
            } catch (Throwable $t) {
               if (class_exists('Logger')) {
                  Logger::warning('afterCommit callback error: ' . $t->getMessage());
               }
            }
         }
         $this->afterCommit = [];
      } else {
         $this->pdo->exec("RELEASE SAVEPOINT $name");
         if (class_exists('Logger')) {
            Logger::debug('Savepoint released: ' . $name);
         }
      }
      return true;
   }

   /**
    * Rollback transaction atau savepoint
    */
   public function rollback()
   {
      if (empty($this->transactions)) return false;

      $name = array_pop($this->transactions);
      if ($name === 'main') {
         $this->pdo->rollBack();
         if (class_exists('Logger')) {
            Logger::debug('Database transaction rolled back');
         }
         // Clear after-commit callbacks
         $this->afterCommit = [];
      } else {
         $this->pdo->exec("ROLLBACK TO SAVEPOINT $name");
         if (class_exists('Logger')) {
            Logger::debug('Savepoint rolled back: ' . $name);
         }
      }
      return true;
   }

   /**
    * Register a callback to run after a successful top-level commit
    * @param callable $cb
    */
   public function registerAfterCommit(callable $cb)
   {
      $this->afterCommit[] = $cb;
      return $this;
   }

   /**
    * Transaction wrapper dengan auto rollback/commit
    * @param callable $callback
    */
   public function transaction($callback)
   {
      try {
         $this->begin();
         $result = call_user_func($callback, $this);
         $this->commit();
         return $result;
      } catch (Throwable $e) {
         $this->rollback();
         throw $e;
      }
   }

   /**
    * Check apakah ada transaction aktif
    */
   public function inTransaction()
   {
      return !empty($this->transactions);
   }

   /**
    * Get current transaction depth
    */
   public function getTransactionDepth()
   {
      return count($this->transactions);
   }
}
