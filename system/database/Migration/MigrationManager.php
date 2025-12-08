<?php

/**
 * Migration Manager - Track dan run migrations
 * 
 * Fitur:
 * - Automatic migration detection
 * - Run pending migrations
 * - Rollback migrations
 * - Migration history tracking
 * - Batch processing
 * 
 * Database migrations disimpan di:
 * - database/migrations/ - untuk migration files
 * - migrations table - untuk tracking
 */

class MigrationManager
{
   private $db;
   private static $migrationsPath;
   private $table = 'migrations';

   public function __construct(Database $db, ?string $migrationsPath = null)
   {
      $this->db = $db;
      self::$migrationsPath = $migrationsPath ?? DISK_PATH . '/database/migrations';
      $this->ensureMigrationTable();
   }

   /**
    * Mark a migration as executed without running its code.
    * Useful when the table/schema already exists and you want to sync state.
    */
   public function markAsExecuted(string $migrationName, ?int $batch = null): bool
   {
      $batch = $batch ?? $this->getNextBatch();
      $sql = "INSERT INTO `{$this->table}` (migration, batch, executed_at) VALUES (?, ?, ?)";
      $this->db->insert($sql, [$migrationName, $batch, date('Y-m-d H:i:s')]);
      return true;
   }

   /**
    * Scan migration files and if corresponding tables already exist in DB,
    * mark those migrations as executed. This helps keep migration history
    * in sync when tables were created manually or in other environments.
    *
    * It only processes migration files that look like `*_create_<table>_table` pattern.
    */
   public function syncWithDatabase(): array
   {
      $results = [];
      $all = $this->getAllMigrations();
      $executed = $this->getExecutedMigrations();
      $executedNames = array_column($executed, 'migration');

      foreach ($all as $migration) {
         if (in_array($migration['name'], $executedNames)) continue;

         // Try to detect table name from filename pattern: *_create_<table>_table
         if (preg_match('/create_([a-z0-9_]+)_table$/i', $migration['name'], $m)) {
            $table = $m[1];
            $schema = new Schema($this->db);
            if ($schema->hasTable($table)) {
               $this->markAsExecuted($migration['name']);
               $results[] = ['migration' => $migration['name'], 'action' => 'marked'];
            }
         }
      }

      return $results;
   }

   /**
    * Find migration(s) that likely create or affect the given table name.
    * Returns array of migration file info (file, path, name).
    */
   public function findMigrationsByTable(string $tableName): array
   {
      $all = $this->getAllMigrations();
      $found = [];

      foreach ($all as $migration) {
         // quick heuristic: filename contains table name
         if (stripos($migration['name'], $tableName) !== false) {
            $found[] = $migration;
            continue;
         }

         // fallback: try to search file content for create('table') or table('table') patterns
         if (file_exists($migration['path'])) {
            $content = file_get_contents($migration['path']);
            if (preg_match("/create\(\s*'{$tableName}'|create\(\s*\"{$tableName}\"|table\(\s*'{$tableName}'/i", $content)) {
               $found[] = $migration;
            }
         }
      }

      return $found;
   }

   /**
    * Get all migration files
    */
   public function getAllMigrations(): array
   {
      if (!is_dir(self::$migrationsPath)) {
         return [];
      }

      $files = scandir(self::$migrationsPath);
      $migrations = [];

      foreach ($files as $file) {
         if ($file === '.' || $file === '..') continue;
         if (substr($file, -4) !== '.php') continue;

         $migrations[] = [
            'file' => $file,
            'path' => self::$migrationsPath . '/' . $file,
            'name' => str_replace('.php', '', $file),
            'batch' => null
         ];
      }

      // Sort by timestamp (filename)
      usort($migrations, function ($a, $b) {
         return strcmp($a['file'], $b['file']);
      });

      return $migrations;
   }

   /**
    * Get pending migrations (belum dijalankan)
    */
   public function getPendingMigrations(): array
   {
      $all = $this->getAllMigrations();
      $executed = $this->getExecutedMigrations();
      $executedNames = array_column($executed, 'migration');

      $pending = [];
      foreach ($all as $migration) {
         if (!in_array($migration['name'], $executedNames)) {
            $pending[] = $migration;
         }
      }

      return $pending;
   }

   /**
    * Get executed migrations
    */
   public function getExecutedMigrations(): array
   {
      $sql = "SELECT * FROM `{$this->table}` ORDER BY batch, migration";
      return $this->db->select($sql);
   }

   /**
    * Run pending migrations
    */
   public function run(): array
   {
      $pending = $this->getPendingMigrations();

      if (empty($pending)) {
         return ['message' => 'No pending migrations'];
      }

      $batch = $this->getNextBatch();
      $results = [];

      foreach ($pending as $migration) {
         try {
            $this->runMigration($migration, $batch);
            $results[] = [
               'status' => 'success',
               'migration' => $migration['name'],
               'message' => 'Migrated'
            ];
         } catch (Exception $e) {
            $results[] = [
               'status' => 'error',
               'migration' => $migration['name'],
               'message' => $e->getMessage()
            ];
         }
      }

      return $results;
   }

   /**
    * Run specific migration
    */
   private function runMigration(array $migration, int $batch): void
   {
      // Include dan instantiate migration class
      require_once $migration['path'];

      $className = $this->getMigrationClassName($migration['name']);

      if (!class_exists($className)) {
         throw new Exception("Migration class {$className} not found");
      }

      $instance = new $className();

      // Start transaction
      $this->db->beginTransaction();

      try {
         // Run up()
         $instance->up();

         // Record migration
         $this->recordMigration($migration['name'], $batch);

         // Commit
         $this->db->commit();
      } catch (Exception $e) {
         $this->db->rollBack();
         throw $e;
      }
   }

   /**
    * Rollback migrations
    */
   public function rollback(?int $steps = 1): array
   {
      $executed = $this->getExecutedMigrations();

      if (empty($executed)) {
         return ['message' => 'No migrations to rollback'];
      }

      // Get latest batch
      $batches = array_unique(array_column($executed, 'batch'));
      $latestBatch = max($batches);

      // Get migrations to rollback from latest batch
      $migrationsToRollback = array_filter(
         $executed,
         function ($m) use ($latestBatch) {
            return $m['batch'] === $latestBatch;
         }
      );

      // Sort reverse untuk rollback dalam order yang benar
      usort($migrationsToRollback, function ($a, $b) {
         return strcmp($b['migration'], $a['migration']);
      });

      $results = [];

      foreach (array_slice($migrationsToRollback, 0, $steps) as $migration) {
         try {
            $this->rollbackMigration($migration);
            $results[] = [
               'status' => 'success',
               'migration' => $migration['migration'],
               'message' => 'Rolled back'
            ];
         } catch (Exception $e) {
            $results[] = [
               'status' => 'error',
               'migration' => $migration['migration'],
               'message' => $e->getMessage()
            ];
         }
      }

      return $results;
   }

   /**
    * Rollback specific migration
    */
   private function rollbackMigration(array $migration): void
   {
      // Find migration file
      $path = self::$migrationsPath . '/' . $migration['migration'] . '.php';

      if (!file_exists($path)) {
         throw new Exception("Migration file {$path} not found");
      }

      require_once $path;

      $className = $this->getMigrationClassName($migration['migration']);

      if (!class_exists($className)) {
         throw new Exception("Migration class {$className} not found");
      }

      $instance = new $className();

      // Start transaction
      $this->db->beginTransaction();

      try {
         // Run down()
         $instance->down();

         // Remove migration record
         $this->forgetMigration($migration['migration']);

         // Commit
         $this->db->commit();
      } catch (Exception $e) {
         $this->db->rollBack();
         throw $e;
      }
   }

   /**
    * Rollback all migrations
    */
   public function reset(): array
   {
      $executed = $this->getExecutedMigrations();
      $count = count($executed);

      return $this->rollback($count);
   }

   /**
    * Refresh - rollback semua dan run lagi
    */
   public function refresh(): array
   {
      $this->reset();
      return $this->run();
   }

   /**
    * Get next batch number
    */
   private function getNextBatch(): int
   {
      $sql = "SELECT MAX(batch) as batch FROM `{$this->table}`";
      $result = $this->db->selectOne($sql);

      return (int)($result['batch'] ?? 0) + 1;
   }

   /**
    * Record migration di database
    */
   private function recordMigration(string $name, int $batch): void
   {
      $sql = "INSERT INTO `{$this->table}` (migration, batch, executed_at) VALUES (?, ?, ?)";
      $this->db->insert($sql, [$name, $batch, date('Y-m-d H:i:s')]);
   }

   /**
    * Remove migration record dari database
    */
   private function forgetMigration(string $name): void
   {
      $sql = "DELETE FROM `{$this->table}` WHERE migration = ?";
      $this->db->delete($sql, [$name]);
   }

   /**
    * Ensure migrations table exists
    */
   private function ensureMigrationTable(): void
   {
      $schema = new Schema($this->db);

      if (!$schema->hasTable($this->table)) {
         $sql = "CREATE TABLE `{$this->table}` (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(255) NOT NULL UNIQUE,
                batch INT NOT NULL,
                executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";

         $this->db->statement($sql);
      }
   }

   /**
    * Get migration class name dari filename
    * 
    * Format: YYYY_MM_DD_HHMMSS_create_table_name.php
    * Class: CreateTableNameMigration
    */
   private function getMigrationClassName(string $filename): string
   {
      // Remove timestamp: expect format YYYY_MM_DD_HHMMSS_description
      $parts = explode('_', $filename);

      if (count($parts) < 5) {
         throw new Exception("Invalid migration filename format: {$filename}");
      }

      // Join bagian description (dari index 4 dst)
      $description = implode('_', array_slice($parts, 4));

      // Convert ke StudlyCase
      $words = explode('_', $description);
      $className = '';

      foreach ($words as $word) {
         $className .= ucfirst($word);
      }

      return $className . 'Migration';
   }

   /**
    * Create migration file
    */
   public static function create(string $name): string
   {
      // Resolve app path safely (fallback if APP_PATH constant not defined)
      // $appPath = defined('APP_PATH') ? rtrim(APP_PATH, '/\\') : rtrim(dirname(dirname(__DIR__)) . '/app', '/\\');


      $appPath = self::$migrationsPath;

      if (!is_dir($appPath)) {
         mkdir($appPath, 0755, true);
      }

      if (!is_dir($appPath)) {
         mkdir($appPath, 0755, true);
      }

      $timestamp = date('Y_m_d_His');
      $filename = "{$timestamp}_{$name}.php";
      $filepath = $appPath . DIRECTORY_SEPARATOR . $filename;

      $className = self::getMigrationClassNameFromFileName("{$timestamp}_{$name}");

      $stub = <<<'STUB'
<?php

class {$className} extends Migration
{
    /**
     * Run migration
     */
    public function up()
    {
        $this->create('table_name', function(Blueprint $table) {
            $table->id();
            // Add columns here
            $table->timestamps();
        });
    }

    /**
     * Rollback migration
     */
    public function down()
    {
        $this->dropIfExists('table_name');
    }
}

STUB;

      $stub = str_replace('{$className}', $className, $stub);
      file_put_contents($filepath, $stub);
      return $filepath;
   }

   /**
    * Get class name dari filename (static helper)
    */
   private static function getMigrationClassNameFromFileName(string $filename): string
   {
      $parts = explode('_', $filename);

      if (count($parts) < 5) {
         $description = '';
      } else {
         $description = implode('_', array_slice($parts, 4));
      }
      $words = explode('_', $description);
      $className = '';

      foreach ($words as $word) {
         $className .= ucfirst($word);
      }

      return $className . 'Migration';
   }
};
