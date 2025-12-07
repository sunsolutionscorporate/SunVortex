# Contoh Penggunaan Migration System

## 📌 Struktur Dasar Migration

Setiap file migration berisi 2 method utama: `up()` dan `down()`.

### up()

Dijalankan ketika: `php sun migrate run`

- Membuat/mengubah/menambah struktur database

### down()

Dijalankan ketika: `php sun migrate rollback`

- Membatalkan perubahan yang dilakukan di `up()`

---

## 🔨 Contoh 1: Membuat Tabel Baru

```php
<?php

class CreateUsersTableMigration extends Migration
{
    public function up()
    {
        // Membuat tabel 'users'
        $this->create('users', function(Blueprint $table) {
            $table->id();                           // Primary key auto increment
            $table->string('name', 100);            // VARCHAR(100)
            $table->string('email')->unique();      // VARCHAR(255), unique
            $table->string('password');             // VARCHAR(255)
            $table->string('phone')->nullable();    // VARCHAR(255), boleh NULL
            $table->enum('role', ['user', 'admin'])->default('user');
            $table->boolean('is_active')->default(true);
            $table->text('bio')->nullable();
            $table->timestamps();                   // created_at, updated_at
        });
    }

    public function down()
    {
        // Membatalkan: hapus tabel
        $this->dropIfExists('users');
    }
}
```

**Jalankan:**

```bash
php sun migrate run
```

---

## 📊 Contoh 2: Tabel dengan Foreign Key

```php
<?php

class CreatePostsTableMigration extends Migration
{
    public function up()
    {
        $this->create('posts', function(Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');           // Foreign key ke users.id
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('content');
            $table->string('category');
            $table->unsignedInteger('views')->default(0);
            $table->enum('status', ['published', 'draft'])->default('draft');
            $table->timestamps();

            // Index untuk query lebih cepat
            $table->index(['category']);
            $table->index(['status']);
        });
    }

    public function down()
    {
        $this->dropIfExists('posts');
    }
}
```

---

## ✏️ Contoh 3: Menambah Kolom ke Tabel Existing

```php
<?php

class AddRatingToPostsTableMigration extends Migration
{
    public function up()
    {
        // Ubah tabel yang sudah ada
        $this->table('posts', function(Blueprint $table) {
            $table->integer('rating')->default(0);
            $table->integer('comment_count')->default(0);
        });
    }

    public function down()
    {
        // Batalkan: (jika ada method drop column di Schema)
        // Atau biarkan kosong
    }
}
```

---

## 🎯 Jenis-Jenis Kolom (Column Types)

### Text

```php
$table->string('name');                 // VARCHAR(255)
$table->string('email', 100);           // VARCHAR(100)
$table->text('description');            // TEXT
$table->longText('content');            // LONGTEXT
$table->mediumText('bio');              // MEDIUMTEXT
$table->char('code', 10);               // CHAR(10)
```

### Number

```php
$table->integer('age');                 // INT
$table->bigInteger('count');            // BIGINT
$table->smallInteger('level');          // SMALLINT
$table->unsignedInteger('views');       // INT UNSIGNED
$table->decimal('price', 8, 2);         // DECIMAL(8,2)
$table->float('rating');                // FLOAT
```

### Date & Time

```php
$table->date('birthday');               // DATE
$table->time('start_time');             // TIME
$table->timestamp('created_at');        // TIMESTAMP
$table->dateTime('published_at');       // DATETIME
$table->timestamps();                   // created_at + updated_at
```

### Special

```php
$table->id();                           // BIGINT UNSIGNED PRIMARY KEY
$table->uuid('id');                     // UUID
$table->boolean('is_active');           // TINYINT(1)
$table->enum('status', ['a', 'b']);     // ENUM
$table->json('metadata');               // JSON
$table->foreignId('user_id');           // BIGINT UNSIGNED (FK)
```

---

## 🎀 Modifiers (Pengubah Kolom)

```php
$table->string('email')
    ->unique()              // Tidak boleh duplikat
    ->nullable()            // Boleh NULL
    ->default('guest')      // Nilai default
    ->index()              // Buat index
    ->comment('Email');     // Komentar kolom
```

---

## 📋 Command Reference

```bash
# Membuat migration baru
php sun migrate make:create create_products_table

# Menjalankan semua pending migration
php sun migrate run

# Melihat status migration
php sun migrate status

# Membatalkan 1 step terakhir
php sun migrate rollback

# Membatalkan 3 step terakhir
php sun migrate rollback 3

# Membatalkan semua migration
php sun migrate reset

# Rollback semua dan run ulang
php sun migrate refresh
```

---

## 💡 Tips Praktis

1. **Setiap perubahan struktur = buat migration baru**

   - Jangan edit database manual
   - Gunakan migration agar tertracking di git

2. **Naming Convention**

   - `create_table_name` → Membuat tabel baru
   - `add_column_to_table` → Menambah kolom
   - `drop_column_from_table` → Menghapus kolom

3. **Foreign Key Order**

   - Buat parent table dulu
   - Contoh: `users` → `posts` → `comments`

4. **Test Rollback**

   - Selalu test `php sun migrate rollback` sebelum push ke git
   - Pastikan `down()` merupakan kebalikan dari `up()`

5. **Commit ke Git**
   - Commit migration files bersama perubahan code
   - Team lain bisa auto-sync dengan `php sun migrate run`

---

## 🚀 Workflow Praktis

### Scenario: Membuat Blog dari 0

**Step 1: Buat migration users**

```bash
php sun migrate make:create create_users_table
```

Edit file, buat tabel users dengan kolom: id, name, email, password, created_at, updated_at

**Step 2: Buat migration posts**

```bash
php sun migrate make:create create_posts_table
```

Edit file, buat tabel posts dengan foreignId('user_id'), title, content, timestamps

**Step 3: Jalankan semua**

```bash
php sun migrate run
```

**Step 4: Verifikasi**

```bash
php sun migrate status
```

Selesai! Database sudah ter-setup dengan 2 tabel terstruktur.
