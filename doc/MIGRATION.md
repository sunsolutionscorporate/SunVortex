# Database Migrations

> **Panduan Lengkap untuk Database Migrations di SunVortex**
>
> Fitur migration memungkinkan Anda mengelola skema database dengan kontrol versi, mendukung create/alter/drop table, rollback, dan seeding data.

---

## Daftar Isi

1. [Overview](#overview)
2. [Setup & Struktur](#setup--struktur)
3. [Membuat Migration](#membuat-migration)
4. [API Blueprint](#api-blueprint)
5. [CLI Commands](#cli-commands)
6. [Seeding Data](#seeding-data)
7. [Best Practices](#best-practices)
8. [Contoh Praktis](#contoh-praktis)

---

## Overview

Migration adalah file PHP yang menjelaskan perubahan struktur database. Setiap migration memiliki dua method:

- **`up()`**: menjalankan perubahan (create/alter table)
- **`down()`**: rollback perubahan

### Keuntungan

- ✅ Versionable: semua perubahan database tercatat dalam git
- ✅ Repeatable: dapat di-run di environment berbeda (dev, staging, production)
- ✅ Reversible: dapat di-rollback ke state sebelumnya
- ✅ Team-friendly: kolaborasi tanpa konflik schema manual
- ✅ Multi-database: support MySQL, SQLite, PostgreSQL

---

## Setup & Struktur

### Folder Struktur

```
storage/
├── database/
│   ├── migrations/          # Folder untuk migration files
│   │   ├── 2024_01_01_000001_create_users_table.php
│   │   ├── 2024_01_02_000002_create_posts_table.php
│   │   └── 2024_01_03_000003_alter_posts_add_slug.php
│   └── seeders/            # Folder untuk seeder files
│       ├── UserSeeder.php
│       └── PostSeeder.php
```

### Penamaan File

Format: `{YYYY}_{MM}_{DD}_{HHmmss}_{description}.php`

Contoh:

- `2024_01_15_143022_create_users_table.php`
- `2024_01_15_143045_create_posts_table.php`
- `2024_01_16_090030_alter_posts_add_slug.php`

**Catatan**: Timestamp memastikan urutan eksekusi yang konsisten.

---

## Membuat Migration

### 1. Generate Migration File

```bash
# Buat migration untuk create table
php sun migrate make:create create_users_table

# Buat migration untuk alter table
php sun migrate make:alter alter_posts_add_slug

# Buat migration dengan custom name
php sun migrate make:create create_residents_table
```

CLI secara otomatis membuat file di `storage/database/migrations/` dengan timestamp.

### 2. Edit Migration File

Contoh file `2024_01_15_143022_create_users_table.php`:

```php
<?php

class CreateUsersTable extends Migration
{
    public function up()
    {
        $this->create('users', function(Blueprint $table) {
            $table->id();                              // PRIMARY KEY auto-increment
            $table->string('name', 100);              // VARCHAR(100)
            $table->string('email')->unique();        // VARCHAR(255) UNIQUE
            $table->string('password');               // VARCHAR(255)
            $table->enum('role', ['user', 'admin']);  // ENUM
            $table->boolean('is_active')->default(true);
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamps();                      // created_at, updated_at

            // Index
            $table->index('email');
            $table->index('role');
        });
    }

    public function down()
    {
        $this->dropIfExists('users');
    }
}
```

---

## API Blueprint

Ketika membuat/mengubah table, Anda menggunakan object `$table` (Blueprint) dengan method-method berikut:

### Columns (Tipe Data)

```php
$table->id();                                    // BIGINT PRIMARY KEY AUTO_INCREMENT
$table->uuid('id');                             // UUID PRIMARY KEY
$table->increments('id');                       // INT AUTO_INCREMENT
$table->integer('count');                       // INT
$table->bigInteger('number');                   // BIGINT
$table->smallInteger('age');                    // SMALLINT
$table->tinyInteger('status');                  // TINYINT
$table->float('price', 8, 2);                   // FLOAT(8,2)
$table->double('rating', 5, 2);                 // DOUBLE(5,2)
$table->decimal('amount', 10, 2);               // DECIMAL(10,2)

$table->string('name');                         // VARCHAR(255)
$table->string('slug', 100);                    // VARCHAR(100)
$table->char('code', 5);                        // CHAR(5)
$table->text('description');                    // TEXT
$table->mediumText('content');                  // MEDIUMTEXT
$table->longText('article');                    // LONGTEXT

$table->binary('data');                         // BLOB
$table->json('metadata');                       // JSON
$table->jsonb('config');                        // JSONB (PostgreSQL)

$table->date('birth_date');                     // DATE
$table->dateTime('created_at');                 // DATETIME
$table->timestamp('published_at');              // TIMESTAMP
$table->time('start_time');                     // TIME
$table->year('founded_year');                   // YEAR

$table->boolean('is_active');                   // BOOLEAN/TINYINT(1)
$table->enum('status', ['draft', 'published']); // ENUM

$table->softDeletes();                          // deleted_at (TIMESTAMP, nullable)
$table->timestamps();                           // created_at, updated_at
```

### Column Modifiers

```php
$table->string('email')
    ->nullable()                                // NULL
    ->default('guest@example.com')              // DEFAULT value
    ->unique()                                  // UNIQUE KEY
    ->index()                                   // INDEX
    ->change();                                 // ALTER (modify column)

$table->integer('age')
    ->unsigned()                                // UNSIGNED
    ->default(0);

$table->string('status')
    ->default('active')
    ->comment('User status: active, inactive, banned');
```

### Indexes & Constraints

```php
$table->primary('id');                          // PRIMARY KEY
$table->unique('email');                        // UNIQUE
$table->unique(['email', 'deleted_at']);        // Composite UNIQUE
$table->index('name');                          // INDEX
$table->index(['category_id', 'status']);       // Composite INDEX
$table->fullText('description');                // FULLTEXT INDEX (MySQL)

$table->foreign('user_id')
    ->references('id')
    ->on('users')
    ->onDelete('cascade')                       // Cascade delete
    ->onUpdate('cascade');
```

### Key Methods

```php
$table->id();                                   // Shortcut untuk BIGINT PK
$table->timestamps();                           // created_at, updated_at
$table->softDeletes();                          // deleted_at (nullable timestamp)
$table->rememberToken();                        // token column untuk "remember me"
```

---

## CLI Commands

### Create & Alter Migrations

```bash
# Create migration untuk table baru
php sun migrate make:create create_users_table

# Create migration untuk alter table
php sun migrate make:alter alter_users_add_phone
```

### Run Migrations

```bash
# Jalankan semua pending migrations
php sun migrate run

# Alias untuk run
php sun migrate migrate
```

### Rollback

```bash
# Rollback 1 migration terakhir
php sun migrate rollback

# Rollback 3 migration terakhir
php sun migrate rollback 3

# Rollback semua migrations (reset database)
php sun migrate reset
```

### Refresh & Fresh

```bash
# Rollback semua dan jalankan ulang
# Mempertahankan data asli jika tersimpan di seeder
php sun migrate refresh

# Drop semua table dan jalankan ulang migrations
# Menghapus semua data
php sun migrate fresh
```

### List & Status

```bash
# List semua migrations (run/pending)
php sun migrate list
```

---

## Seeding Data

Seeder digunakan untuk mengisi data awal ke database (contoh: user default, kategori, dll).

### Generate Seeder

```bash
# Buat seeder file
php sun migrate make:seed UserSeeder

# Buat seeder dengan data generator
php sun migrate make:seed PostSeeder
```

### Contoh Seeder

File `storage/database/seeders/UserSeeder.php`:

```php
<?php

class UserSeeder extends Seeder
{
    public function run()
    {
        // Insert single row
        Database::init()->table('users')->insert([
            'name'  => 'Admin User',
            'email' => 'admin@example.com',
            'password' => password_hash('secret', PASSWORD_BCRYPT),
            'role'  => 'admin',
        ]);

        // Insert multiple rows
        $users = [
            ['name' => 'John Doe', 'email' => 'john@example.com', 'role' => 'user'],
            ['name' => 'Jane Smith', 'email' => 'jane@example.com', 'role' => 'user'],
        ];

        foreach ($users as $user) {
            Database::init()->table('users')->insert($user);
        }
    }
}
```

### Run Seeder

```bash
# Seed specific seeder
php sun migrate seed UserSeeder

# Seed all seeders
php sun migrate seed all

# Refresh with seeding
php sun migrate refresh && php sun migrate seed all
```

---

## Best Practices

### 1. **Satu Change Per File**

Buat file migration terpisah untuk setiap perubahan logis:

```
✅ create_users_table.php
✅ create_posts_table.php
✅ alter_posts_add_slug.php

❌ 2024_01_15_000001_create_all_tables.php  (terlalu banyak dalam satu file)
```

### 2. **Rollback Harus Sempurna**

Pastikan method `down()` sepenuhnya membatalkan `up()`:

```php
public function up()
{
    $this->create('posts', function(Blueprint $table) {
        $table->id();
        $table->string('title');
    });
}

public function down()
{
    $this->dropIfExists('posts');  // Harus drop table, bukan rename
}
```

### 3. **Hindari Menyimpan Data di Migration**

Jangan gunakan migration untuk insert data. Gunakan seeder:

```php
// ❌ SALAH: Menyimpan data di migration
public function up()
{
    $this->create('users', ...);
    // DON'T: Database::init()->table('users')->insert([...]);
}

// ✅ BENAR: Gunakan seeder
// storage/database/seeders/UserSeeder.php
```

### 4. **Dokumentasi Column**

Berikan comment pada kolom yang tidak jelas:

```php
$table->enum('status', ['draft', 'published', 'archived'])
    ->comment('Content status: draft=under review, published=live, archived=hidden');

$table->tinyInteger('retry_count')
    ->default(0)
    ->comment('Number of retry attempts, max 255');
```

### 5. **Test Sebelum Production**

Selalu test migrations di development sebelum production:

```bash
# Di development
php sun migrate run
# Verifikasi table, columns, indexes
php sun migrate rollback
php sun migrate run  # Jalankan ulang, pastikan idempotent
```

### 6. **Jangan Ubah File Migration Lama**

Jika migration sudah di-run di production, jangan ubah file-nya. Buat migration baru:

```php
// ❌ SALAH: Mengubah migration yang sudah di-run
// 2024_01_15_create_users_table.php (jangan ubah setelah di-run)

// ✅ BENAR: Buat migration baru
// 2024_01_20_alter_users_add_phone.php
public function up()
{
    $this->table('users', function(Blueprint $table) {
        $table->string('phone', 20)->nullable()->after('email');
    });
}
```

---

## Contoh Praktis

### Contoh 1: Membuat Aplikasi Blog

```bash
# Step 1: Create migrations
php sun migrate make:create create_users_table
php sun migrate make:create create_posts_table
php sun migrate make:create create_comments_table
```

**2024_01_15_create_users_table.php:**

```php
<?php
class CreateUsersTable extends Migration
{
    public function up()
    {
        $this->create('users', function(Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
            $table->index('email');
        });
    }

    public function down()
    {
        $this->dropIfExists('users');
    }
}
```

**2024_01_15_create_posts_table.php:**

```php
<?php
class CreatePostsTable extends Migration
{
    public function up()
    {
        $this->create('posts', function(Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->unsigned();
            $table->string('title');
            $table->text('content');
            $table->enum('status', ['draft', 'published']);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->index('user_id');
            $table->index('status');
        });
    }

    public function down()
    {
        $this->dropIfExists('posts');
    }
}
```

**2024_01_15_create_comments_table.php:**

```php
<?php
class CreateCommentsTable extends Migration
{
    public function up()
    {
        $this->create('comments', function(Blueprint $table) {
            $table->id();
            $table->bigInteger('post_id')->unsigned();
            $table->bigInteger('user_id')->unsigned();
            $table->text('content');
            $table->timestamps();

            $table->foreign('post_id')
                ->references('id')
                ->on('posts')
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        $this->dropIfExists('comments');
    }
}
```

```bash
# Step 2: Run migrations
php sun migrate run

# Step 3: Create seeders
php sun migrate make:seed UserSeeder
php sun migrate make:seed PostSeeder

# Step 4: Fill seeders with data
# (lihat contoh di atas)

# Step 5: Run seeders
php sun migrate seed all

# Selesai! Database siap dengan data.
```

### Contoh 2: Alter Table (Tambah Kolom)

```bash
php sun migrate make:alter alter_posts_add_slug
```

**2024_01_20_alter_posts_add_slug.php:**

```php
<?php
class AlterPostsAddSlug extends Migration
{
    public function up()
    {
        $this->table('posts', function(Blueprint $table) {
            $table->string('slug', 150)
                ->unique()
                ->after('title')
                ->comment('URL slug');
        });
    }

    public function down()
    {
        $this->table('posts', function(Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
}
```

---

## Testing Migrations

### Workflow Testing

```bash
# 1. Jalankan migrations
php sun migrate run

# 2. Verifikasi table di database (gunakan tools seperti MySQL Workbench, DBeaver)

# 3. Rollback
php sun migrate rollback

# 4. Jalankan ulang (test idempotency)
php sun migrate run

# 5. Rollback lagi
php sun migrate rollback

# 6. Jika semuanya OK, commit ke git
git add storage/database/migrations/
git commit -m "Add migrations for blog: users, posts, comments"
```

### Error Handling

Jika migration gagal, pesan error biasanya menunjukkan SQL syntax atau constraint problem:

```
ERROR: UNIQUE constraint failed: users.email
// → Email sudah ada atau uniqueness violation

ERROR: Foreign key constraint failed
// → Referenced table/column tidak ada

ERROR: Column already exists
// → Kolom sudah ada di table
```

Perbaiki dan jalankan `php sun migrate run` ulang.

---

## Kesimpulan

Migrations adalah fondasi **infrastructure as code** untuk database. Dengan menggunakannya:

- ✅ Semua perubahan schema terversi (git)
- ✅ Mudah collaborate dengan team
- ✅ Production rollback jika ada masalah
- ✅ Reproducible di environment berbeda (dev → staging → production)

Untuk pertanyaan lebih lanjut, lihat:

- [`DATABASE_CORE.md`](./DATABASE_CORE.md) - Query builder & database API
- [`File.md`](./File.md) - File utilities
- Contoh aplikasi di `app/models/` dan `storage/database/migrations/`

---

**Status**: ✅ Lengkap | Terakhir diupdate: 2024-01-15
