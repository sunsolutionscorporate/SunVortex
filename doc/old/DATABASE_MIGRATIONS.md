# Database Migration System

## Daftar Isi

1. [Pengenalan](#pengenalan)
2. [Konsep Dasar](#konsep-dasar)
3. [Quick Start](#quick-start)
4. [Creating Migrations](#creating-migrations)
5. [Migration Structure](#migration-structure)
6. [Table Operations](#table-operations)
7. [Column Types](#column-types)
8. [Indexes & Constraints](#indexes--constraints)
9. [Running Migrations](#running-migrations)
10. [Rollback & Reset](#rollback--reset)
11. [Best Practices](#best-practices)
12. [Examples](#examples)
13. [Troubleshooting](#troubleshooting)

---

## Pengenalan

Migration system memungkinkan Anda untuk version-control database schema dan membuat struktur database yang reproducible dan collaborative. Setiap migration adalah PHP class yang define changes ke database.

### Fitur Utama

- ✅ Version-controlled database schema
- ✅ Fluent API untuk create/alter/drop tables
- ✅ Complete column type support
- ✅ Indexes, constraints, foreign keys
- ✅ Rollback & reset support
- ✅ Transaction-based execution
- ✅ CLI commands untuk management
- ✅ Batch processing

### Inspirasi

Framework ini terinspirasi dari Laravel, CodeIgniter, dan framework migration systems lainnya.

---

## Konsep Dasar

### Migrations Directory

Semua migration files disimpan di:

```
database/
├── migrations/
│   ├── 2024_01_15_093000_create_users_table.php
│   ├── 2024_01_15_094500_create_posts_table.php
│   └── 2024_01_20_120000_add_status_to_users.php
```

### Migrations Table

Framework secara otomatis membuat `migrations` table untuk track migration history:

```sql
CREATE TABLE migrations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    migration VARCHAR(255) NOT NULL UNIQUE,
    batch INT NOT NULL,
    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Batch System

Setiap `run()` operation membuat batch baru. Ini memungkinkan rollback per-batch.

```
Batch 1:
  - create_users_table
  - create_posts_table

Batch 2:
  - add_status_to_posts
  - add_author_to_posts
```

### File Naming Convention

Format: `YYYY_MM_DD_HHMMSS_<description>`

```
2024_01_15_093000_create_users_table.php
└─ Timestamp    └─ Migration name (snake_case)
```

---

## Quick Start

### 1. Create Migration

```bash
php migrate make:create create_users_table
# Output: ✓ Migration created: database/migrations/2024_01_15_093000_create_users_table.php
```

### 2. Write Migration Code

```php
<?php

class CreateUsersTableMigration extends Migration
{
    public function up()
    {
        $this->create('users', function(Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->email();
            $table->password();
            $table->timestamps();
        });
    }

    public function down()
    {
        $this->dropIfExists('users');
    }
}
?>
```

### 3. Run Migrations

```bash
php migrate run
# Output:
# ✓ 2024_01_15_093000_create_users_table: Migrated
```

### 4. Check Status

```bash
php migrate status
# Output:
# Executed Migrations:
#   ✓ 2024_01_15_093000_create_users_table (batch 1)
```

### 5. Rollback if Needed

```bash
php migrate rollback
# Output:
# ✓ 2024_01_15_093000_create_users_table: Rolled back
```

---

## Creating Migrations

### Make Command

```bash
# Create new migration
php migrate make:create create_users_table

# Alias
php migrate make:migration create_users_table
```

### Generated File Template

```php
<?php

class CreateUsersTableMigration extends Migration
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
?>
```

### Migration Class Name

Automatic conversion dari filename ke class name:

```
Filename: 2024_01_15_093000_create_users_table.php
Class:    CreateUsersTableMigration

Filename: 2024_01_20_140000_add_status_to_posts_table.php
Class:    AddStatusToPostsTableMigration
```

---

## Migration Structure

### up() Method

Runs ketika migration di-execute. Modify database structure di sini:

```php
public function up()
{
    $this->create('users', function(Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->email()->unique();
        $table->timestamps();
    });
}
```

### down() Method

Reverses changes dari up(). Ini memungkinkan rollback:

```php
public function down()
{
    $this->dropIfExists('users');
}
```

### transaction-Safe Execution

Migrations automatically wrapped dalam transaction:

```php
BEGIN TRANSACTION;
  -- Run migration up()
COMMIT;  // atau ROLLBACK jika ada error
```

---

## Table Operations

### Creating Tables

```php
// Basic table creation
$this->create('users', function(Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->email();
    $table->timestamps();
});
```

### Modifying Tables

```php
// Add columns ke existing table
$this->table('users', function(Blueprint $table) {
    $table->string('phone')->nullable();
    $table->string('address')->nullable();
});
```

### Dropping Tables

```php
// Drop table
$this->drop('users');

// Drop if exists (safe)
$this->dropIfExists('users');
```

### Renaming Tables

```php
$this->rename('old_table_name', 'new_table_name');
```

---

## Column Types

### Numeric Columns

```php
$table->integer('count');           // INT
$table->unsignedInteger('rating');  // INT UNSIGNED
$table->bigInteger('views');        // BIGINT
$table->unsignedBigInteger('id');   // BIGINT UNSIGNED
$table->smallInteger('level');      // SMALLINT
$table->decimal('price', 8, 2);     // DECIMAL(8,2)
$table->float('rating');            // FLOAT
$table->double('value');            // DOUBLE
```

### String Columns

```php
$table->string('name');             // VARCHAR(255)
$table->string('title', 100);       // VARCHAR(100)
$table->text('description');        // TEXT
$table->mediumText('content');      // MEDIUMTEXT
$table->longText('body');           // LONGTEXT
$table->char('status', 1);          // CHAR(1)
$table->email('email');             // VARCHAR(255) - helper
$table->phone('phone');             // VARCHAR(20) - helper
$table->password('password');       // VARCHAR(255) - helper
$table->url('website');             // VARCHAR(2048) - helper
$table->slug('slug');               // VARCHAR(255) + unique
```

### Date/Time Columns

```php
$table->date('birth_date');         // DATE
$table->time('start_time');         // TIME
$table->dateTime('created_at');     // DATETIME
$table->timestamp('updated_at');    // TIMESTAMP
$table->timestamps();               // created_at, updated_at TIMESTAMP
$table->softDeletes();              // deleted_at TIMESTAMP (soft delete)
```

### Boolean & JSON Columns

```php
$table->boolean('is_active');       // TINYINT(1)
$table->json('metadata');           // JSON
$table->jsonb('data');              // JSONB (PostgreSQL) / JSON (MySQL)
```

### Special Columns

```php
$table->id();                       // bigint UNSIGNED AUTO_INCREMENT PRIMARY KEY
$table->uuid('id');                 // VARCHAR(36) + unique - for UUID
$table->enum('status', ['draft', 'published', 'archived']);  // ENUM
$table->set('permissions', ['read', 'write', 'delete']);    // SET
$table->binary('hash');             // BINARY
$table->blob('image');              // BLOB
$table->foreignId('user_id');       // bigint UNSIGNED - for foreign keys
```

### Column Modifiers

```php
$table->string('name')
    ->nullable()                // Allow NULL
    ->unique()                  // UNIQUE index
    ->index()                   // INDEX
    ->primary()                 // PRIMARY KEY
    ->default('Unknown')        // DEFAULT value
    ->comment('User full name') // COMMENT
    ->charset('utf8')           // CHARACTER SET
    ->collation('utf8_unicode'); // COLLATE
```

---

## Indexes & Constraints

### Primary Key

```php
// Auto-primary dari id()
$table->id();

// Or manual
$table->primary(['user_id', 'post_id']);  // Composite primary key
```

### Unique Constraints

```php
// Single column
$table->string('email')->unique();

// Multiple columns
$table->unique(['email', 'company_id']);
```

### Regular Indexes

```php
// Single column
$table->string('name')->index();

// Multiple columns
$table->index(['first_name', 'last_name']);
```

### Full-text Indexes

```php
$table->fulltext(['title', 'description']);
```

### Foreign Keys

```php
// Simple foreign key
$table->foreignId('user_id')
    ->references('id')
    ->on('users')
    ->onDelete('cascade')
    ->onUpdate('cascade');
```

---

## Running Migrations

### CLI Commands

```bash
# Run pending migrations
php migrate run
php migrate migrate  # alias

# Show status
php migrate status

# Rollback last batch
php migrate rollback

# Rollback N steps
php migrate rollback 3

# Rollback all
php migrate reset

# Refresh (rollback all dan run again)
php migrate refresh
```

### Programmatically

```php
// Initialize manager
$manager = new MigrationManager(Database::init());

// Get pending
$pending = $manager->getPendingMigrations();
foreach ($pending as $migration) {
    echo $migration['name'] . "\n";
}

// Run migrations
$results = $manager->run();
foreach ($results as $result) {
    echo $result['migration'] . ": " . $result['message'] . "\n";
}

// Get status
$executed = $manager->getExecutedMigrations();
echo "Batch " . $executed[0]['batch'] . ": " . count($executed) . " migrations\n";
```

---

## Rollback & Reset

### Rollback (1 Step)

```bash
php migrate rollback
```

Rollback migration dari latest batch (LIFO order).

### Rollback Multiple Steps

```bash
php migrate rollback 3
```

Rollback 3 migrations dari latest batch.

### Rollback All (Reset)

```bash
php migrate reset
```

Rollback semua migrations (dalam reverse order).

### Refresh

```bash
php migrate refresh
```

Equivalent dengan:

1. Rollback semua migrations
2. Run semua migrations

Berguna untuk reset database ke fresh state dengan latest schema.

---

## Best Practices

### 1. One Change Per Migration

```php
// ✅ Good - single responsibility
class CreateUsersTableMigration extends Migration {
    public function up() { $this->create('users', ...); }
}

class CreatePostsTableMigration extends Migration {
    public function up() { $this->create('posts', ...); }
}

// ❌ Bad - multiple unrelated changes
class SetupDatabaseMigration extends Migration {
    public function up() {
        $this->create('users', ...);
        $this->create('posts', ...);
        $this->create('comments', ...);
    }
}
```

### 2. Always Define down()

```php
// ✅ Good
public function down() {
    $this->dropIfExists('users');
}

// ❌ Bad - tidak bisa rollback
public function down() {
    // throw new Exception("Cannot rollback");
}
```

### 3. Use Helpers untuk Foreign Keys

```php
// ✅ Good - clear intention
$table->foreignId('user_id')
    ->references('id')
    ->on('users')
    ->onDelete('cascade');

// ❌ Less clear
$table->unsignedBigInteger('user_id');
$table->foreign('user_id')
    ->references('id')
    ->on('users');
```

### 4. Add Comments untuk Clarity

```php
$table->boolean('is_active')
    ->default(true)
    ->comment('User account status - active/inactive');

$table->string('status')
    ->default('draft')
    ->comment('Post status: draft, published, archived');
```

### 5. Test Migrations Locally

```bash
# Run locally
php migrate run

# Verify changes
php migrate status

# Test rollback
php migrate rollback

# Verify rollback worked
php migrate status

# Run again
php migrate run
```

### 6. Include Index Strategy

```php
// ✅ Good - include necessary indexes
$table->create('users', function(Blueprint $table) {
    $table->id();
    $table->string('email')->unique();    // Search/auth
    $table->string('name')->index();      // Search
    $table->foreignId('company_id')
        ->references('id')
        ->on('companies');                // Join perf
    $table->timestamps();
});

// ❌ Bad - missing indexes akan slow queries
$table->create('users', function(Blueprint $table) {
    $table->id();
    $table->string('email');              // No unique!
    $table->string('name');               // No index!
    $table->bigInteger('company_id');     // No FK!
});
```

---

## Examples

### Example 1: Create Users Table

```php
<?php

class CreateUsersTableMigration extends Migration
{
    public function up()
    {
        $this->create('users', function(Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->email()->unique();
            $table->password();
            $table->string('phone', 20)->nullable();
            $table->text('bio')->nullable();
            $table->string('avatar_url')->nullable();
            $table->enum('role', ['admin', 'user', 'moderator'])
                ->default('user');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('email');
            $table->index('created_at');
        });
    }

    public function down()
    {
        $this->dropIfExists('users');
    }
}
?>
```

### Example 2: Create Posts Table dengan Foreign Key

```php
<?php

class CreatePostsTableMigration extends Migration
{
    public function up()
    {
        $this->create('posts', function(Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
            $table->string('title');
            $table->slug('slug')->unique();
            $table->text('content');
            $table->text('excerpt')->nullable();
            $table->string('featured_image')->nullable();
            $table->enum('status', ['draft', 'published', 'archived'])
                ->default('draft');
            $table->integer('views_count')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes untuk performa
            $table->index('user_id');
            $table->index('slug');
            $table->index('status');
            $table->index('published_at');
            $table->fulltext(['title', 'content']);
        });
    }

    public function down()
    {
        $this->dropIfExists('posts');
    }
}
?>
```

### Example 3: Add Column ke Existing Table

```php
<?php

class AddMetadataToPostsTableMigration extends Migration
{
    public function up()
    {
        $this->table('posts', function(Blueprint $table) {
            $table->json('metadata')->nullable()
                ->comment('SEO metadata: title, description, keywords');
            $table->string('reading_time_estimate')->nullable()
                ->comment('Estimated reading time in minutes');
        });
    }

    public function down()
    {
        // Cannot easily drop columns safely, so skip
        // Or implement manual SQL
    }
}
?>
```

### Example 4: Create Comments Table (Nested Structure)

```php
<?php

class CreateCommentsTableMigration extends Migration
{
    public function up()
    {
        $this->create('comments', function(Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')
                ->references('id')
                ->on('posts')
                ->onDelete('cascade');
            $table->foreignId('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
            $table->foreignId('parent_id')
                ->nullable()
                ->references('id')
                ->on('comments')
                ->onDelete('cascade');
            $table->text('content');
            $table->integer('likes_count')->default(0);
            $table->boolean('is_approved')->default(true);
            $table->timestamps();

            $table->index('post_id');
            $table->index('user_id');
            $table->index('parent_id');
        });
    }

    public function down()
    {
        $this->dropIfExists('comments');
    }
}
?>
```

---

## Troubleshooting

### Issue: "Migration class not found"

**Problem**: Class name tidak match dengan convention

```
File: 2024_01_15_093000_create_users_table.php
Expected Class: CreateUsersTableMigration
Your Class: CreateUsersMigration ✗
```

**Solution**: Follow naming convention - convert filename to StudlyCase:

```
2024_01_15_093000_create_users_table.php
└─ CreateUsersTableMigration ✓
```

### Issue: "Cannot modify header information"

**Problem**: Migration executed bersama output (echo, var_dump)

**Solution**: Debug output setelah migration:

```php
// ❌ Bad
public function up() {
    var_dump("Debug info"); // Don't do this
    $this->create('users', ...);
}

// ✅ Good
public function up() {
    $this->create('users', ...);
}
// Debug after migration
```

### Issue: Partial Migration (Some ran, some failed)

**Problem**: Error mid-migration, partial changes applied

**Solution**: Semua migrations transaction-based. Jika error terjadi:

1. Database automatically rolled back
2. Migration record tidak di-save
3. Run again setelah fix error

```bash
# Fix your migration code
# Then run lagi
php migrate run
```

### Issue: Cannot Rollback

**Problem**: down() method tidak implement properly

```php
// ❌ Bad - irreversible
public function down() {
    // Oops, forgot to implement
}

// ✅ Good
public function down() {
    $this->dropIfExists('users');
}
```

**Solution**: Always implement down() yang exactly reverses up()

### Issue: Schema not updating

**Problem**: Migration ran tapi table tidak ada di database

```bash
# Check status
php migrate status

# Verify table exists
SELECT * FROM information_schema.tables
WHERE table_schema = DATABASE() AND table_name = 'users';
```

**Solution**: Verify:

1. Database permission untuk CREATE TABLE
2. No syntax errors di migration
3. Check error logs

---

## CLI Reference

| Command                             | Description            |
| ----------------------------------- | ---------------------- |
| `php migrate make:create <name>`    | Create new migration   |
| `php migrate make:migration <name>` | Alias for make:create  |
| `php migrate run`                   | Run pending migrations |
| `php migrate migrate`               | Alias for run          |
| `php migrate rollback [steps]`      | Rollback migrations    |
| `php migrate refresh`               | Rollback all dan run   |
| `php migrate reset`                 | Rollback all           |
| `php migrate status`                | Show status            |
| `php migrate help`                  | Show help              |

---

## API Reference

### Migration Base Class

```php
class Migration
{
    // Table operations
    protected function create(string $table, callable $callback): void
    protected function table(string $table, callable $callback): void
    protected function drop(string $table): void
    protected function dropIfExists(string $table): void
    protected function rename(string $from, string $to): void
}
```

### Blueprint Class

```php
class Blueprint
{
    // Numeric columns
    public function id(): ColumnDefinition
    public function integer(string $name): ColumnDefinition
    public function bigInteger(string $name): ColumnDefinition
    public function unsignedInteger(string $name): ColumnDefinition
    public function unsignedBigInteger(string $name): ColumnDefinition
    public function decimal(string $name, int $precision = 8, int $scale = 2): ColumnDefinition

    // String columns
    public function string(string $name, int $length = 255): ColumnDefinition
    public function text(string $name): ColumnDefinition
    public function email(string $name = 'email'): ColumnDefinition
    public function password(string $name = 'password'): ColumnDefinition
    public function slug(string $name = 'slug'): ColumnDefinition
    public function url(string $name = 'url'): ColumnDefinition

    // Date/Time columns
    public function date(string $name): ColumnDefinition
    public function time(string $name): ColumnDefinition
    public function dateTime(string $name): ColumnDefinition
    public function timestamp(string $name): ColumnDefinition
    public function timestamps(): void
    public function softDeletes(): ColumnDefinition

    // Special columns
    public function boolean(string $name): ColumnDefinition
    public function json(string $name): ColumnDefinition
    public function uuid(string $name = 'id'): ColumnDefinition
    public function enum(string $name, array $values): ColumnDefinition

    // Indexes
    public function primary(?array $columns = null): void
    public function unique(?array $columns = null): void
    public function index(array $columns): void
    public function fulltext(array $columns): void
}
```

### ColumnDefinition Modifiers

```php
class ColumnDefinition
{
    public function nullable(): self
    public function default($value): self
    public function autoIncrement(): self
    public function primary(): self
    public function unique(): self
    public function index(): self
    public function comment(string $comment): self
    public function collation(string $collation): self
    public function charset(string $charset): self
}
```

---

**Versi**: 1.0
**Kompatibilitas**: PHP 7.3+, MySQL 5.7+
**Terakhir diupdate**: 2024
