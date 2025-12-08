<?php

/**
 * Database Migration Base Class
 * 
 * Fitur:
 * - Create/Alter/Drop tables dengan fluent API
 * - Column definition yang lengkap
 * - Indexes dan constraints
 * - Rollback support
 * - Migration versioning dengan timestamp
 * - Transaction support
 * 
 * Usage:
 *   class CreateUsersTable extends Migration {
 *       public function up() {
 *           $this->create('users', function(Blueprint $table) {
 *               $table->id();
 *               $table->string('email')->unique();
 *               $table->timestamps();
 *           });
 *       }
 *       
 *       public function down() {
 *           $this->dropIfExists('users');
 *       }
 *   }
 */

abstract class Migration
{
   /**
    * Database instance
    */
   protected $db;

   /**
    * Schema manager
    */
   protected $schema;

   /**
    * Konstruktor
    */
   public function __construct()
   {
      $this->db = Database::init();
      $this->schema = new Schema($this->db);
   }
   /**
    * Run migration up
    */
   abstract public function up();

   /**
    * Roll back migration
    */
   abstract public function down();

   /**
    * Create new table
    * 
    * @param string $table
    * @param callable $callback
    * @return void
    */
   protected function create(string $table, callable $callback): void
   {
      $blueprint = new Blueprint($table, 'create');
      call_user_func($callback, $blueprint);
      $this->schema->create($blueprint);
   }

   /**
    * Create table only if not exists (idempotent helper)
    */
   protected function createIfNotExists(string $table, callable $callback): void
   {
      if (!$this->schema->hasTable($table)) {
         $this->create($table, $callback);
      } else {
         // Helpful feedback when running via CLI
         if (class_exists('CLI')) {
            CLI::print("Skipping create: table `{$table}` already exists.", CLI::YELLOW);
         }
      }
   }

   /**
    * Modify existing table
    * 
    * @param string $table
    * @param callable $callback
    * @return void
    */
   protected function table(string $table, callable $callback): void
   {
      $blueprint = new Blueprint($table, 'alter');
      call_user_func($callback, $blueprint);
      $this->schema->alter($blueprint);
   }

   /**
    * Drop table
    * 
    * @param string $table
    * @return void
    */
   protected function drop(string $table): void
   {
      $this->schema->drop($table);
   }

   /**
    * Drop table if exists
    * 
    * @param string $table
    * @return void
    */
   protected function dropIfExists(string $table): void
   {
      $this->schema->dropIfExists($table);
   }

   /**
    * Rename table
    * 
    * @param string $from
    * @param string $to
    * @return void
    */
   protected function rename(string $from, string $to): void
   {
      $this->schema->rename($from, $to);
   }

   /**
    * Get database name
    */
   public function getConnection(): string
   {
      return 'default';
   }
}

/**
 * Blueprint - Column definition builder
 */
class Blueprint
{
   private $table;
   private $action; // 'create' atau 'alter'
   private $columns = [];
   private $indexes = [];
   private $changes = []; // For alter operations
   private $primary = null;
   private $foreign = [];
   private $unique = [];

   public function __construct(string $table, string $action = 'create')
   {
      $this->table = $table;
      $this->action = $action;
   }

    // ===== ID COLUMNS =====

   /**
    * Add auto-incrementing ID column
    */
   public function id(): ColumnDefinition
   {
      return $this->unsignedBigInteger('id')->autoIncrement()->primary();
   }

   /**
    * Add UUID column (VARCHAR 36)
    */
   public function uuid(string $name = 'id'): ColumnDefinition
   {
      return $this->string($name, 36)->unique();
   }

    // ===== STRING COLUMNS =====

   /**
    * Add string column
    */
   public function string(string $name, int $length = 255): ColumnDefinition
   {
      return $this->addColumn('string', $name, ['length' => $length]);
   }

   /**
    * Add text column (VARCHAR MAX)
    */
   public function text(string $name): ColumnDefinition
   {
      return $this->addColumn('text', $name);
   }

   /**
    * Add mediumText column
    */
   public function mediumText(string $name): ColumnDefinition
   {
      return $this->addColumn('mediumtext', $name);
   }

   /**
    * Add longText column
    */
   public function longText(string $name): ColumnDefinition
   {
      return $this->addColumn('longtext', $name);
   }

   /**
    * Add char column
    */
   public function char(string $name, int $length = 255): ColumnDefinition
   {
      return $this->addColumn('char', $name, ['length' => $length]);
   }

   /**
    * Add email column (VARCHAR 255)
    */
   public function email(string $name = 'email'): ColumnDefinition
   {
      return $this->string($name, 255);
   }

   /**
    * Add phone column (VARCHAR 20)
    */
   public function phone(string $name = 'phone'): ColumnDefinition
   {
      return $this->string($name, 20);
   }

   /**
    * Add slug column (VARCHAR 255)
    */
   public function slug(string $name = 'slug'): ColumnDefinition
   {
      return $this->string($name, 255)->unique();
   }

   /**
    * Add password column (VARCHAR 255)
    */
   public function password(string $name = 'password'): ColumnDefinition
   {
      return $this->string($name, 255);
   }

   /**
    * Add URL column (VARCHAR 2048)
    */
   public function url(string $name = 'url'): ColumnDefinition
   {
      return $this->string($name, 2048);
   }

    // ===== NUMERIC COLUMNS =====

   /**
    * Add integer column
    */
   public function integer(string $name): ColumnDefinition
   {
      return $this->addColumn('int', $name);
   }

   /**
    * Add big integer column
    */
   public function bigInteger(string $name): ColumnDefinition
   {
      return $this->addColumn('bigint', $name);
   }

   /**
    * Add small integer column
    */
   public function smallInteger(string $name): ColumnDefinition
   {
      return $this->addColumn('smallint', $name);
   }

   /**
    * Add unsigned integer
    */
   public function unsignedInteger(string $name): ColumnDefinition
   {
      return $this->addColumn('int', $name, ['unsigned' => true]);
   }

   /**
    * Add unsigned big integer
    */
   public function unsignedBigInteger(string $name): ColumnDefinition
   {
      return $this->addColumn('bigint', $name, ['unsigned' => true]);
   }

   /**
    * Add unsigned small integer
    */
   public function unsignedSmallInteger(string $name): ColumnDefinition
   {
      return $this->addColumn('smallint', $name, ['unsigned' => true]);
   }

   /**
    * Add decimal column
    */
   public function decimal(string $name, int $precision = 8, int $scale = 2): ColumnDefinition
   {
      return $this->addColumn('decimal', $name, ['precision' => $precision, 'scale' => $scale]);
   }

   /**
    * Add double column
    */
   public function double(string $name): ColumnDefinition
   {
      return $this->addColumn('double', $name);
   }

   /**
    * Add float column
    */
   public function float(string $name): ColumnDefinition
   {
      return $this->addColumn('float', $name);
   }

    // ===== DATE/TIME COLUMNS =====

   /**
    * Add timestamp column (datetime)
    */
   public function timestamp(string $name): ColumnDefinition
   {
      return $this->addColumn('timestamp', $name);
   }

   /**
    * Add date column
    */
   public function date(string $name): ColumnDefinition
   {
      return $this->addColumn('date', $name);
   }

   /**
    * Add time column
    */
   public function time(string $name): ColumnDefinition
   {
      return $this->addColumn('time', $name);
   }

   /**
    * Add datetime column
    */
   public function dateTime(string $name): ColumnDefinition
   {
      return $this->addColumn('datetime', $name);
   }

   /**
    * Add timestamps (created_at, updated_at)
    */
   public function timestamps(): void
   {
      $this->timestamp('created_at')->default('CURRENT_TIMESTAMP');
      $this->timestamp('updated_at')->default('CURRENT_TIMESTAMP')->onUpdateCurrentTimestamp();
   }

   /**
    * Add soft delete column (deleted_at)
    */
   public function softDeletes(): ColumnDefinition
   {
      return $this->timestamp('deleted_at')->nullable();
   }

    // ===== BOOLEAN/JSON COLUMNS =====

   /**
    * Add boolean column
    */
   public function boolean(string $name): ColumnDefinition
   {
      return $this->addColumn('boolean', $name, ['default' => false]);
   }

   /**
    * Add JSON column
    */
   public function json(string $name): ColumnDefinition
   {
      return $this->addColumn('json', $name)->nullable();
   }

   /**
    * Add JSONB column
    */
   public function jsonb(string $name): ColumnDefinition
   {
      return $this->addColumn('jsonb', $name)->nullable();
   }

    // ===== ENUM/SET COLUMNS =====

   /**
    * Add enum column
    */
   public function enum(string $name, array $values): ColumnDefinition
   {
      return $this->addColumn('enum', $name, ['values' => $values]);
   }

   /**
    * Add set column
    */
   public function set(string $name, array $values): ColumnDefinition
   {
      return $this->addColumn('set', $name, ['values' => $values]);
   }

    // ===== BINARY/BLOB COLUMNS =====

   /**
    * Add binary column
    */
   public function binary(string $name): ColumnDefinition
   {
      return $this->addColumn('binary', $name);
   }

   /**
    * Add blob column
    */
   public function blob(string $name): ColumnDefinition
   {
      return $this->addColumn('blob', $name);
   }

    // ===== FOREIGN KEY =====

   /**
    * Add foreign key column dengan shortcut
    */
   public function foreignId(string $name): ColumnDefinition
   {
      return $this->unsignedBigInteger($name);
   }

    // ===== INDEX METHODS =====

   /**
    * Add primary key
    */
   public function primary(?array $columns = null): void
   {
      if ($columns) {
         $this->indexes[] = [
            'type' => 'primary',
            'columns' => (array)$columns
         ];
      } else {
         // Single column primary
         $this->primary = true;
      }
   }

   /**
    * Add unique index
    */
   public function unique(?array $columns = null): void
   {
      if ($columns) {
         $this->indexes[] = [
            'type' => 'unique',
            'columns' => (array)$columns
         ];
      }
   }

   /**
    * Add index
    */
   public function index(array $columns): void
   {
      $this->indexes[] = [
         'type' => 'index',
         'columns' => (array)$columns
      ];
   }

   /**
    * Add fulltext index
    */
   public function fulltext(array $columns): void
   {
      $this->indexes[] = [
         'type' => 'fulltext',
         'columns' => (array)$columns
      ];
   }

   // ===== GETTERS =====

   public function getTable(): string
   {
      return $this->table;
   }

   public function getAction(): string
   {
      return $this->action;
   }

   public function getColumns(): array
   {
      return $this->columns;
   }

   public function getIndexes(): array
   {
      return $this->indexes;
   }

   public function getChanges(): array
   {
      return $this->changes;
   }

    // ===== PRIVATE HELPERS =====

   /**
    * Add column to blueprint
    */
   private function addColumn(string $type, string $name, array $options = []): ColumnDefinition
   {
      $column = new ColumnDefinition($name, $type, $options);
      $this->columns[$name] = $column;
      return $column;
   }
}

/**
 * Column Definition - Column properties builder
 */
class ColumnDefinition
{
   private $name;
   private $type;
   private $nullable = false;
   private $default = null;
   private $autoIncrement = false;
   private $primary = false;
   private $unique = false;
   private $index = false;
   private $comment = '';
   private $collation = null;
   private $charset = null;
   private $options = [];
   private $onUpdateCurrentTimestamp = false;
   private $storedAs = null; // For generated columns

   public function __construct(string $name, string $type, array $options = [])
   {
      $this->name = $name;
      $this->type = $type;
      $this->options = $options;
   }

   /**
    * Make column nullable
    */
   public function nullable(): self
   {
      $this->nullable = true;
      return $this;
   }

   /**
    * Set default value
    */
   public function default($value): self
   {
      $this->default = $value;
      return $this;
   }

   /**
    * Auto increment
    */
   public function autoIncrement(): self
   {
      $this->autoIncrement = true;
      return $this;
   }

   /**
    * Primary key
    */
   public function primary(): self
   {
      $this->primary = true;
      return $this;
   }

   /**
    * Unique
    */
   public function unique(): self
   {
      $this->unique = true;
      return $this;
   }

   /**
    * Index
    */
   public function index(): self
   {
      $this->index = true;
      return $this;
   }

   /**
    * Comment
    */
   public function comment(string $comment): self
   {
      $this->comment = $comment;
      return $this;
   }

   /**
    * Collation
    */
   public function collation(string $collation): self
   {
      $this->collation = $collation;
      return $this;
   }

   /**
    * Charset
    */
   public function charset(string $charset): self
   {
      $this->charset = $charset;
      return $this;
   }

   /**
    * ON UPDATE CURRENT_TIMESTAMP
    */
   public function onUpdateCurrentTimestamp(): self
   {
      $this->onUpdateCurrentTimestamp = true;
      return $this;
   }

   /**
    * Generated/Computed column
    */
   public function storedAs(string $expression): self
   {
      $this->storedAs = $expression;
      return $this;
   }

   // ===== GETTERS =====

   public function getName(): string
   {
      return $this->name;
   }
   public function getType(): string
   {
      return $this->type;
   }
   public function isNullable(): bool
   {
      return $this->nullable;
   }
   public function getDefault()
   {
      return $this->default;
   }
   public function isAutoIncrement(): bool
   {
      return $this->autoIncrement;
   }
   public function isPrimary(): bool
   {
      return $this->primary;
   }
   public function isUnique(): bool
   {
      return $this->unique;
   }
   public function isIndex(): bool
   {
      return $this->index;
   }
   public function getComment(): string
   {
      return $this->comment;
   }
   public function getCollation(): ?string
   {
      return $this->collation;
   }
   public function getCharset(): ?string
   {
      return $this->charset;
   }
   public function hasOnUpdateCurrentTimestamp(): bool
   {
      return $this->onUpdateCurrentTimestamp;
   }
   public function getStoredAs(): ?string
   {
      return $this->storedAs;
   }
   public function getOptions(): array
   {
      return $this->options;
   }
}
