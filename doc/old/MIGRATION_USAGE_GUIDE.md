# Panduan Lengkap Menggunakan Migration System

## 📚 Apa itu Migration?

Migration adalah file PHP yang berisi kode untuk **membuat, mengubah, atau menghapus struktur tabel database**.

**Keuntungan menggunakan migration:**

- ✅ Versi control untuk struktur database
- ✅ Team dapat sinkronisasi database dengan mudah
- ✅ Rollback otomatis jika ada error
- ✅ Dokumentasi tertulis untuk perubahan schema

---

## 🎯 Langkah-Langkah Menggunakan Migration

### 1️⃣ Buat Migration Baru

Untuk membuat file migration baru:

```bash
php sun migrate make:create create_users_table
```

Hasilnya akan membuat file baru di:

```
app/database/migrations/2025_12_07_101530_create_users_table.php
```

Format filename:

- `YYYY_MM_DD_HHMMSS` = timestamp otomatis
- `create_users_table` = nama yang Anda beri

### 2️⃣ Edit Migration File

Buka file migration yang baru dibuat dan isi dengan struktur tabel:

```php
<?php

class CreateUsersTableMigration extends Migration
{
    // Dijalankan saat: php sun migrate run
    public function up()
    {
        $this->create('users', function(Blueprint $table) {
            $table->id();                              // Primary key auto increment
            $table->string('name');                    // Teks hingga 255 karakter
            $table->string('email')->unique();         // Teks, tidak boleh duplikat
            $table->string('password');                // Password
            $table->enum('role', ['user', 'admin']);   // Pilihan: user atau admin
            $table->boolean('is_active')->default(1);  // True/False
            $table->timestamps();                      // created_at, updated_at
            $table->softDeletes();                     // deleted_at
        });
    }

    // Dijalankan saat: php sun migrate rollback
    public function down()
    {
        $this->dropIfExists('users');
    }
}
```

### 3️⃣ Jalankan Migration

Jalankan migration yang belum dijalankan:

```bash
php sun migrate run
```

Output:

```
Running 1 migration(s)...
✓ 2025_12_07_101530_create_users_table: Migrated
```

### 4️⃣ Cek Status Migration

```bash
php sun migrate status
```

Output:

```
Migration Status
============================================================

Executed Migrations:
  ✓ 2025_12_07_101530_create_users_table (batch 1, 2025-12-07 10:15:30)

Pending Migrations:
  ◯ 2025_12_07_105000_create_posts_table
```

---

## 📝 Jenis-Jenis Kolom

### Text/String

```php
$table->string('name');                    // 255 karakter
$table->string('email', 100);              // Custom length
$table->text('description');               // Teks panjang
$table->longText('content');               // Teks sangat panjang
$table->mediumText('bio');                 // Teks menengah
```

### Number

```php
$table->integer('age');                    // Bilangan bulat
$table->bigInteger('views');               // Bilangan besar
$table->smallInteger('level');             // Bilangan kecil
$table->unsignedInteger('count');          // Tidak boleh negatif
$table->decimal('price', 8, 2);            // Desimal (8 digit, 2 desimal)
$table->float('rating');                   // Desimal float
```

### Date/Time

```php
$table->date('birthday');                  // YYYY-MM-DD
$table->time('start_time');                // HH:MM:SS
$table->timestamp('created_at');           // Tanggal + waktu
$table->dateTime('published_at');          // Tanggal + waktu
$table->timestamps();                      // created_at + updated_at otomatis
$table->softDeletes();                     // deleted_at untuk soft delete
```

### Special

```php
$table->id();                              // Alias: unsignedBigInteger id
$table->uuid('id');                        // UUID string
$table->boolean('is_active');              // True/False (1/0)
$table->enum('status', ['a', 'b']);        // Pilihan terbatas
$table->json('metadata');                  // JSON data
$table->binary('data');                    // Binary data
$table->foreignId('user_id');              // Foreign key
```

---

## 🔧 Modifier Kolom

```php
$table->string('email')
    ->unique()           // Tidak boleh duplikat
    ->nullable()         // Boleh kosong
    ->default('guest')   // Nilai default
    ->index()           // Membuat index untuk performa
    ->comment('Email pengguna');  // Komentar di database

// Contoh lengkap
$table->string('phone')
    ->nullable()
    ->comment('Nomor telepon');
```

---

## 🔄 Operasi Database

### Membuat Tabel Baru

```php
$this->create('users', function(Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->timestamps();
});
```

### Mengubah Tabel Existsing

```php
$this->table('users', function(Blueprint $table) {
    $table->string('phone')->nullable();  // Tambah kolom baru
});
```

### Menghapus Tabel

```php
$this->drop('users');
$this->dropIfExists('users');  // Aman, tidak error jika tidak ada
```

### Rename Tabel

```php
$this->rename('users', 'app_users');
```

---

## 🚀 Workflow Praktis

### Scenario 1: Membuat Blog dari Awal

**Step 1: Buat tabel users**

```bash
php sun migrate make:create create_users_table
```

Isi dengan:

```php
$this->create('users', function(Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('email')->unique();
    $table->string('password');
    $table->timestamps();
});
```

**Step 2: Buat tabel posts**

```bash
php sun migrate make:create create_posts_table
```

Isi dengan:

```php
$this->create('posts', function(Blueprint $table) {
    $table->id();
    $table->foreignId('user_id');
    $table->string('title');
    $table->text('content');
    $table->timestamps();
});
```

**Step 3: Jalankan semua migration**

```bash
php sun migrate run
```

Sekarang database Anda sudah memiliki tabel users dan posts!

### Scenario 2: Menambahkan Fitur Baru

Anda ingin menambahkan fitur rating untuk posts:

```bash
php sun migrate make:create add_rating_to_posts_table
```

Isi dengan:

```php
$this->table('posts', function(Blueprint $table) {
    $table->integer('rating')->default(0);
    $table->integer('comment_count')->default(0);
});
```

Jalankan:

```bash
php sun migrate run
```

### Scenario 3: Rollback (Batalkan Migration)

Jika ada error atau ingin membatalkan:

```bash
php sun migrate rollback      // Batalkan 1 langkah terakhir
php sun migrate rollback 2    // Batalkan 2 langkah terakhir
php sun migrate reset         // Batalkan semua migration
```

---

## 💾 Contoh Lengkap: Sistem E-Commerce

```php
<?php

class CreateProductsTableMigration extends Migration
{
    public function up()
    {
        // Tabel produk
        $this->create('products', function(Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('sku')->unique();
            $table->text('description');
            $table->decimal('price', 10, 2);  // Harga hingga 99999.99
            $table->integer('stock');         // Stok
            $table->string('image_url');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });

        // Tabel kategori produk
        $this->create('categories', function(Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->timestamps();
        });

        // Tabel orders
        $this->create('orders', function(Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->decimal('total_price', 10, 2);
            $table->enum('status', ['pending', 'paid', 'shipped', 'delivered'])->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        // Tabel order items
        $this->create('order_items', function(Blueprint $table) {
            $table->id();
            $table->foreignId('order_id');
            $table->foreignId('product_id');
            $table->integer('quantity');
            $table->decimal('price', 10, 2);
            $table->timestamps();
        });
    }

    public function down()
    {
        $this->dropIfExists('order_items');
        $this->dropIfExists('orders');
        $this->dropIfExists('categories');
        $this->dropIfExists('products');
    }
}
```

---

## ✅ Checklist Penggunaan

- [ ] Buat migration dengan `php sun migrate make:create`
- [ ] Edit file migration di `app/database/migrations/`
- [ ] Jalankan dengan `php sun migrate run`
- [ ] Verifikasi dengan `php sun migrate status`
- [ ] Jika error, gunakan `php sun migrate rollback`
- [ ] Commit ke git untuk team bisa sinkronisasi

---

## 🎓 Tips & Tricks

1. **Selalu buat migration untuk setiap perubahan database**

   - Jangan edit database manual
   - Gunakan migration agar tertracking

2. **Naming Convention**

   - `create_table_name` untuk membuat tabel baru
   - `add_column_to_table_name` untuk menambah kolom
   - `drop_column_from_table_name` untuk hapus kolom

3. **Rollback sebelum Push**

   - Selalu test rollback sebelum push ke git
   - Pastikan down() bekerja dengan baik

4. **Foreign Key Order**
   - Buat tabel parent sebelum child
   - Contoh: users → posts → comments

---

## 📞 Bantuan

Lihat daftar command:

```bash
php sun migrate help
```

Lihat dokumentasi lengkap:

- `doc/DATABASE_MIGRATIONS.md` - Referensi teknis
- `doc/MIGRATION_QUICK_REFERENCE.md` - Cheat sheet
