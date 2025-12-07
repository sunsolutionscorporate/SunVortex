# Migration - Quick Reference (Cheat Sheet)

> **Cukup copy-paste command dan jalankan di terminal!**

---

## ⚡ Command Cepat

### Setup Awal

```bash
# Cek status migration
php migrate status

# Jalankan semua migration pending
php migrate run
```

### Membuat Migration

```bash
# Buat migration untuk table baru
php migrate make:create create_table_name

# Buat migration untuk alter table
php migrate make:create add_column_to_table_name
```

### Menjalankan

```bash
# Jalankan semua pending migration
php migrate run

# Batalkan migration terakhir (undo)
php migrate rollback

# Rollback semua + run semua (refresh database)
php migrate refresh

# Rollback semua migration
php migrate reset
```

### Info

```bash
# Cek status migration
php migrate status

# Tampilkan bantuan
php migrate help
```

---

## 📝 Template Migration File

### Template untuk CREATE Table

```php
<?php

class CreateTableNameTable extends Migration {

    public function up()
    {
        Schema::create('table_name', function(Blueprint $table) {
            $table->id();
            $table->string('column_name');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('table_name');
    }
}
```

### Template untuk ALTER Table (Menambah Kolom)

```php
<?php

class AddColumnsToTableName extends Migration {

    public function up()
    {
        Schema::table('table_name', function(Blueprint $table) {
            $table->string('new_column');
            $table->dropColumn('old_column'); // Jika hapus
        });
    }

    public function down()
    {
        Schema::table('table_name', function(Blueprint $table) {
            $table->dropColumn('new_column');
            // Atau tambah kembali kolom yang dihapus
        });
    }
}
```

---

## 🗂️ Tipe-Tipe Kolom (Column Types)

### String/Text

```php
$table->string('name');                // VARCHAR(255)
$table->string('email', 100);         // VARCHAR(100)
$table->text('description');          // TEXT
$table->char('code', 10);             // CHAR(10)
```

### Numeric

```php
$table->integer('count');             // INT
$table->bigInteger('big_num');        // BIGINT
$table->decimal('price', 10, 2);      // DECIMAL(10,2)
$table->float('percentage');          // FLOAT
$table->double('value');              // DOUBLE
```

### Date/Time

```php
$table->date('birth_date');           // DATE
$table->time('start_time');           // TIME
$table->dateTime('published_at');     // DATETIME
$table->timestamp('created_at');      // TIMESTAMP
$table->timestamps();                 // created_at + updated_at
$table->softDeletes();                // deleted_at (soft delete)
```

### Boolean

```php
$table->boolean('is_active');         // BOOLEAN
```

### JSON

```php
$table->json('meta_data');            // JSON
```

### Special

```php
$table->id();                         // BIGINT PRIMARY KEY (auto increment)
$table->uuid('id');                   // UUID
$table->enum('status', ['a', 'b']);  // ENUM
```

---

## 🔧 Column Modifiers (Pengubah Kolom)

```php
$table->string('name')->nullable();                    // Boleh NULL
$table->string('email')->unique();                     // Unik (tidak boleh duplikat)
$table->integer('age')->default(18);                   // Nilai default
$table->string('status')->default('active');           // Default string
$table->string('notes')->nullable()->default(null);    // Nullable + default null
$table->string('slug')->comment('URL slug');           // Komentar
$table->integer('priority')->index();                  // Index untuk query cepat
$table->string('email')->primary();                    // Primary key
$table->integer('post_id')->unsigned();                // Unsigned (tidak negatif)
$table->string('name')->after('email');                // Posisi kolom
```

---

## 🔗 Indexes & Constraints

```php
// Indexes
$table->index(['email']);                              // Biasa index
$table->unique(['email']);                             // Unique index
$table->primary(['id']);                               // Primary key
$table->fullText(['title', 'body']);                   // Full text search

// Foreign Keys (Advanced)
$table->integer('user_id')->unsigned();
$table->foreign('user_id')
    ->references('id')
    ->on('users')
    ->onDelete('cascade');
```

---

## 📊 Contoh Praktis

### Users Table

```php
Schema::create('users', function(Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('email')->unique();
    $table->string('password');
    $table->string('phone')->nullable();
    $table->enum('role', ['user', 'admin'])->default('user');
    $table->boolean('is_active')->default(true);
    $table->timestamps();
    $table->softDeletes();
    $table->index(['email']);
});
```

### Posts Table

```php
Schema::create('posts', function(Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->text('content');
    $table->string('slug')->unique();
    $table->integer('user_id')->unsigned();
    $table->enum('status', ['draft', 'published'])->default('draft');
    $table->dateTime('published_at')->nullable();
    $table->timestamps();
    $table->softDeletes();

    // Foreign key
    $table->foreign('user_id')
        ->references('id')
        ->on('users')
        ->onDelete('cascade');
});
```

### Products Table

```php
Schema::create('products', function(Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->text('description')->nullable();
    $table->decimal('price', 12, 2);
    $table->string('sku')->unique();
    $table->integer('stock')->default(0);
    $table->enum('status', ['active', 'inactive'])->default('active');
    $table->integer('category_id')->unsigned();
    $table->json('attributes')->nullable();
    $table->timestamps();

    $table->index(['sku']);
    $table->index(['category_id']);
});
```

---

## 🐛 Troubleshooting

| Error                            | Solusi                                                             |
| -------------------------------- | ------------------------------------------------------------------ |
| `Migration file tidak ditemukan` | Pastikan file di folder `database/migrations/`                     |
| `Syntax error di migration file` | Cek file, ada syntax error di PHP                                  |
| `Table sudah ada`                | Gunakan `Schema::dropIfExists()` atau jalankan `php migrate reset` |
| `Foreign key error`              | Pastikan tabel reference sudah dibuat terlebih dahulu              |
| `Column sudah ada`               | Gunakan `->change()` modifier untuk alter, atau drop dulu          |

---

## 📌 Best Practices

1. **Buat migration untuk setiap perubahan schema** - Jangan edit file yang sudah dijalankan
2. **Selalu buat method down()** - Untuk rollback yang aman
3. **Nama file descriptive** - `add_phone_to_users` lebih jelas dari `modify_users`
4. **Test di development dulu** - Sebelum push ke production
5. **Jalankan migrate run** - Setelah setiap pull/merge dari git
6. **Gunakan Schema builder** - Lebih aman daripada raw SQL

---

## ✅ Workflow Harian

```bash
# 1. Mulai hari
git pull                  # Update project terbaru
php migrate run          # Jalankan migration terbaru

# 2. Fitur baru
php migrate make:create create_new_table
# Edit file...
php migrate run

# 3. Selesai hari
git add database/migrations/*
git commit -m "Add new table"
git push
```

---

**Butuh bantuan? Baca: `doc/MIGRATION_BEGINNER_GUIDE.md`**
