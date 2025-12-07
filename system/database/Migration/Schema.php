<?php

/**
 * Schema Manager - SQL DDL Builder
 * 
 * Menghandle:
 * - CREATE TABLE
 * - ALTER TABLE
 * - DROP TABLE
 * - SQL generation dari Blueprint
 */

class Schema
{
   private $db;
   private $platform; // MySQL, SQLite, PostgreSQL, etc

   public function __construct(Database $db)
   {
      $this->db = $db;
      // Detect platform dari PDO driver
      $this->platform = $db->getPdo()->getAttribute(PDO::ATTR_DRIVER_NAME);
   }

   /**
    * Create table from blueprint
    */
   public function create(Blueprint $blueprint): void
   {
      $sql = $this->compileCreate($blueprint);
      $this->db->statement($sql);
   }

   /**
    * Alter table from blueprint
    */
   public function alter(Blueprint $blueprint): void
   {
      $sql = $this->compileAlter($blueprint);
      $this->db->statement($sql);
   }

   /**
    * Drop table
    */
   public function drop(string $table): void
   {
      $sql = "DROP TABLE `{$table}`";
      $this->db->statement($sql);
   }

   /**
    * Drop table if exists
    */
   public function dropIfExists(string $table): void
   {
      $sql = "DROP TABLE IF EXISTS `{$table}`";
      $this->db->statement($sql);
   }

   /**
    * Rename table
    */
   public function rename(string $from, string $to): void
   {
      if ($this->platform === 'sqlite') {
         $sql = "ALTER TABLE `{$from}` RENAME TO `{$to}`";
      } else {
         $sql = "RENAME TABLE `{$from}` TO `{$to}`";
      }
      $this->db->statement($sql);
   }

   /**
    * Check if table exists
    */
   public function hasTable(string $table): bool
   {
      $sql = $this->platform === 'sqlite'
         ? "SELECT name FROM sqlite_master WHERE type='table' AND name=?"
         : "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?";

      $result = $this->db->selectOne($sql, [$table]);
      return !empty($result);
   }

   /**
    * Check if column exists
    */
   public function hasColumn(string $table, string $column): bool
   {
      if ($this->platform === 'sqlite') {
         $sql = "PRAGMA table_info(`{$table}`)";
         $columns = $this->db->select($sql);
         foreach ($columns as $col) {
            if ($col['name'] === $column) {
               return true;
            }
         }
         return false;
      } else {
         $sql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?";
         $result = $this->db->selectOne($sql, [$table, $column]);
         return !empty($result);
      }
   }

    // ===== SQL COMPILATION =====

   /**
    * Compile CREATE TABLE statement
    */
   private function compileCreate(Blueprint $blueprint): string
   {
      $table = $blueprint->getTable();
      $columns = $blueprint->getColumns();
      $indexes = $blueprint->getIndexes();

      $sql = "CREATE TABLE `{$table}` (\n";
      $definitions = [];

      // Add columns
      foreach ($columns as $column) {
         $definitions[] = $this->compileColumn($column);
      }

      // Add indexes
      foreach ($indexes as $index) {
         $definitions[] = $this->compileIndex($index, null);
      }

      $sql .= implode(",\n", $definitions);
      $sql .= "\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

      return $sql;
   }

   /**
    * Compile ALTER TABLE statement
    */
   private function compileAlter(Blueprint $blueprint): string
   {
      $table = $blueprint->getTable();
      $columns = $blueprint->getColumns();
      $changes = $blueprint->getChanges();

      $sql = "ALTER TABLE `{$table}`\n";
      $statements = [];

      // Add columns
      foreach ($columns as $column) {
         $statements[] = "ADD COLUMN " . $this->compileColumn($column);
      }

      // Apply changes
      foreach ($changes as $change) {
         if ($change['action'] === 'modify') {
            $statements[] = "MODIFY COLUMN " . $this->compileColumn($change['column']);
         } elseif ($change['action'] === 'rename') {
            $statements[] = "CHANGE COLUMN `{$change['from']}` `{$change['to']}` {$change['definition']}";
         } elseif ($change['action'] === 'drop') {
            $statements[] = "DROP COLUMN `{$change['column']}`";
         } elseif ($change['action'] === 'dropIndex') {
            $statements[] = "DROP INDEX `{$change['index']}`";
         } elseif ($change['action'] === 'dropForeign') {
            $statements[] = "DROP FOREIGN KEY `{$change['constraint']}`";
         }
      }

      $sql .= implode(",\n", $statements);

      return $sql;
   }

   /**
    * Compile single column definition
    */
   private function compileColumn(ColumnDefinition $column): string
   {
      $name = $column->getName();
      $type = $this->getColumnType($column);

      $definition = "`{$name}` {$type}";

      // Nullable
      if (!$column->isNullable()) {
         $definition .= " NOT NULL";
      }

      // Auto increment
      if ($column->isAutoIncrement()) {
         $definition .= " AUTO_INCREMENT";
      }

      // Default
      if ($column->getDefault() !== null) {
         $default = $column->getDefault();
         if ($default === 'CURRENT_TIMESTAMP') {
            $definition .= " DEFAULT CURRENT_TIMESTAMP";
         } elseif (is_string($default)) {
            $definition .= " DEFAULT '{$default}'";
         } elseif (is_bool($default)) {
            $definition .= " DEFAULT " . ($default ? '1' : '0');
         } else {
            $definition .= " DEFAULT {$default}";
         }
      } elseif ($column->isNullable() && $this->isTimestampType($type)) {
         // For TIMESTAMP nullable without explicit default, use NULL as default
         $definition .= " DEFAULT NULL";
      }

      // ON UPDATE CURRENT_TIMESTAMP
      if ($column->hasOnUpdateCurrentTimestamp()) {
         $definition .= " ON UPDATE CURRENT_TIMESTAMP";
      }

      // Primary
      if ($column->isPrimary()) {
         $definition .= " PRIMARY KEY";
      }

      // Unique
      if ($column->isUnique()) {
         $definition .= " UNIQUE";
      }

      // Index
      if ($column->isIndex()) {
         $definition .= " INDEX";
      }

      // Comment
      if ($column->getComment()) {
         $comment = addslashes($column->getComment());
         $definition .= " COMMENT '{$comment}'";
      }

      // Charset
      if ($column->getCharset()) {
         $definition .= " CHARACTER SET {$column->getCharset()}";
      }

      // Collation
      if ($column->getCollation()) {
         $definition .= " COLLATE {$column->getCollation()}";
      }

      // Generated/Stored column
      if ($column->getStoredAs()) {
         $definition .= " GENERATED ALWAYS AS ({$column->getStoredAs()}) STORED";
      }

      return $definition;
   }

   /**
    * Get SQL column type
    */
   private function getColumnType(ColumnDefinition $column): string
   {
      $type = $column->getType();
      $options = $column->getOptions();

      switch ($type) {
         case 'string':
            $length = $options['length'] ?? 255;
            return "VARCHAR({$length})";

         case 'char':
            $length = $options['length'] ?? 255;
            return "CHAR({$length})";

         case 'text':
            return "TEXT";

         case 'mediumtext':
            return "MEDIUMTEXT";

         case 'longtext':
            return "LONGTEXT";

         case 'int':
            if ($options['unsigned'] ?? false) {
               return "INT UNSIGNED";
            }
            return "INT";

         case 'bigint':
            if ($options['unsigned'] ?? false) {
               return "BIGINT UNSIGNED";
            }
            return "BIGINT";

         case 'smallint':
            if ($options['unsigned'] ?? false) {
               return "SMALLINT UNSIGNED";
            }
            return "SMALLINT";

         case 'decimal':
            $precision = $options['precision'] ?? 8;
            $scale = $options['scale'] ?? 2;
            return "DECIMAL({$precision},{$scale})";

         case 'float':
            return "FLOAT";

         case 'double':
            return "DOUBLE";

         case 'boolean':
            return "TINYINT(1)";

         case 'json':
            return "JSON";

         case 'jsonb':
            // SQLite/PostgreSQL support
            return $this->platform === 'pgsql' ? "JSONB" : "JSON";

         case 'enum':
            $values = $options['values'] ?? [];
            $values = array_map(function ($v) {
               return "'{$v}'";
            }, $values);
            return "ENUM(" . implode(",", $values) . ")";

         case 'set':
            $values = $options['values'] ?? [];
            $values = array_map(function ($v) {
               return "'{$v}'";
            }, $values);
            return "SET(" . implode(",", $values) . ")";

         case 'date':
            return "DATE";

         case 'time':
            return "TIME";

         case 'datetime':
            return "DATETIME";

         case 'timestamp':
            return "TIMESTAMP";

         case 'binary':
            return "BINARY";

         case 'blob':
            return "BLOB";

         default:
            return "VARCHAR(255)";
      }
   }

   /**
    * Compile index definition
    */
   private function compileIndex(array $index, ?string $table = null): string
   {
      $type = $index['type'];
      $columns = $index['columns'];
      $columnStr = implode("`,`", $columns);

      switch ($type) {
         case 'primary':
            return "PRIMARY KEY (`{$columnStr}`)";
         case 'unique':
            $indexName = "uq_" . implode("_", $columns);
            return "UNIQUE KEY `{$indexName}` (`{$columnStr}`)";
         case 'index':
            $indexName = "idx_" . implode("_", $columns);
            return "KEY `{$indexName}` (`{$columnStr}`)";
         case 'fulltext':
            $indexName = "ft_" . implode("_", $columns);
            return "FULLTEXT KEY `{$indexName}` (`{$columnStr}`)";
         default:
            return "";
      }
   }

   /**
    * Check if column type is a TIMESTAMP type
    */
   private function isTimestampType(string $type): bool
   {
      return stripos($type, 'TIMESTAMP') !== false || stripos($type, 'DATETIME') !== false;
   }
}
