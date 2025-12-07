# Panduan Migration untuk Pemula

> **Dokumen ini dibuat untuk Anda yang benar-benar baru mengenal Database Migration**

---

## 🤔 Apa itu Migration? (Analogi Sederhana)

### Bayangkan seperti ini:

Anda sedang mengerjakan project bersama teman. Kalian ingin mengembangkan database aplikasi.

**Tanpa Migration (Cara Lama - Bermasalah):**

```
Anda: "Halo, kita perlu tabel users dan posts"
Teman: "Baik, bagaimana strukturnya?"
Anda: "Kirim via chat... kolom id, name, email, password, created_at..."
Teman: "Oke, saya buat manual di phpmyadmin"
Anda: "Tunggu, ada yang kurang... role dan status"
Teman: "Aduh, saya harus buat lagi manual? Ribet!"

Hasilnya: Database bisa berbeda di komputer Anda vs teman Anda ❌
```

**Dengan Migration (Cara Baru - Benar):**

```
Anda: "Saya buat file migration untuk users table"
→ Tulis di file: php migrate make:create create_users_table

Teman: "Saya jalankan migration dari file yang kamu buat"
→ Jalankan: php migrate run

Hasilnya: Database sama persis di komputer Anda dan teman Anda ✅
```

---

## 🎯 Kegunaan Migration Sebenarnya

### 1. **Version Control untuk Database**

Seperti Git untuk kode, tapi untuk database schema.

```
Kamu punya riwayat lengkap perubahan database:

1. 2024-01-15: Create users table
2. 2024-01-20: Add status column ke users
3. 2024-02-01: Create posts table
4. 2024-02-05: Add foreign key ke posts

Setiap perubahan bisa di-track, siapa yang buat, kapan, untuk apa
```

### 2. **Collaboration (Kerja Tim)**

Semua orang bisa punya database yang sama:

```
Tim 1 (Frontend):
- Clone project
- Run: php migrate run
- Database mereka jadi sama dengan database di server

Tim 2 (Backend):
- Clone project
- Run: php migrate run
- Database mereka juga sama dengan database di server
```

### 3. **Deployment Mudah**

Saat upload ke production server:

```
Tanpa migration:
- Anda harus punya backup database
- Harus manually create table
- Rawan salah struktur ❌

Dengan migration:
- Jalankan: php migrate run
- Database production otomatis update dengan benar ✅
```

### 4. **Rollback (Membatalkan Perubahan)**

Jika ada kesalahan, bisa dibatalkan:

```
Anda: "Eh, struktur users table salah!"
→ Jalankan: php migrate rollback
→ Database kembali ke state sebelumnya (undo)
→ Fix migration file
→ Jalankan lagi: php migrate run
```

### 5. **Database History (Audit Trail)**

Tabel `migrations` mencatat semua perubahan:

```sql
SELECT * FROM migrations;

| id | migration                              | batch | executed_at         |
|----|----------------------------------------|-------|---------------------|
| 1  | 2024_01_15_093000_create_users_table   | 1     | 2024-01-15 09:30:00 |
| 2  | 2024_01_20_120000_create_posts_table   | 1     | 2024-01-20 12:00:00 |
| 3  | 2024_02_05_140000_add_status_to_users  | 2     | 2024-02-05 14:00:00 |

Ini seperti Git log, tapi untuk database!
```

---

## 📚 Cara Kerjanya (Langkah-Langkah)

### Step 1: Membuat Migration File

**Command:**

```bash
php migrate make:create create_users_table
```

**Output:**

```
✓ Migration created: database/migrations/2024_01_15_093000_create_users_table.php
```

**File yang dibuat:**

```php
<?php

class CreateUsersTable extends Migration {

    public function up()
    {
        // Kode untuk membuat table saat migration dijalankan
        Schema::create('users', function(Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('name');
            $table->string('password');
            $table->timestamps();
        });
    }

    public function down()
    {
        // Kode untuk membatalkan jika rollback
        Schema::dropIfExists('users');
    }
}
```

### Step 2: Jalankan Migration

**Command:**

```bash
php migrate run
```

**Yang terjadi:**

1. Framework cek file di `database/migrations/`
2. Jalankan semua migration yang belum dijalankan
3. Catat di tabel `migrations` yang sudah dijalankan
4. Table `users` terbuat di database

**Output:**

```
Executing migrations...
  ✓ 2024_01_15_093000_create_users_table

Migration complete!
```

### Step 3: Verifikasi di Database

Buka phpmyadmin atau SQL client:

```sql
SHOW TABLES;
-- Anda akan melihat: users, posts, migrations

DESCRIBE users;
-- Anda akan melihat structure:
-- id (INT, PRIMARY KEY)
-- email (VARCHAR, UNIQUE)
-- name (VARCHAR)
-- password (VARCHAR)
-- created_at, updated_at (TIMESTAMP)
```

---

## 💻 Cara Menggunakan via Terminal (Step-by-Step)

**YA, HARUS VIA TERMINAL.** Tapi jangan khawatir, sangat mudah! Berikut langkah praktisnya:

### Membuka Terminal

**Pilih salah satu:**

#### Opsi 1: Windows Command Prompt / PowerShell

```
1. Buka Start Menu
2. Ketik: cmd  atau  powershell
3. Tekan Enter
4. Anda akan lihat: C:\Users\YourName>
```

#### Opsi 2: VS Code Terminal (Recommended)

```
1. Buka VS Code
2. Tekan: Ctrl + `  (backtick)
3. Terminal otomatis terbuka di folder project
```

#### Opsi 3: XAMPP Control Panel

```
1. Buka XAMPP Control Panel
2. Klik "Shell" di bawah
3. Terminal akan terbuka dengan path ke C:\xampp
```

### Navigasi ke Folder Project

```bash
# Lihat Anda berada di folder mana
C:\> dir

# Pindah ke folder project SUN
C:\> cd xampp\htdocs\sun

# Verifikasi sudah di folder yang benar
C:\xampp\htdocs\sun> dir
# Anda akan melihat: app, database, public, system, vendor, composer.json, migrate

# Sekarang siap jalankan perintah migration!
```

### Perintah-Perintah Migration

**1. Cek Status Migration**

```bash
C:\xampp\htdocs\sun> php migrate status
```

Output:

```
Executed Migrations:
  ✓ 2024_01_15_000000_create_users_table (Batch 1)

Pending Migrations:
  (none)
```

**2. Buat Migration Baru**

```bash
C:\xampp\htdocs\sun> php migrate make:create create_products_table
```

Output:

```
✓ Migration created: database/migrations/2024_12_07_143500_create_products_table.php
```

**3. Jalankan Migration**

```bash
C:\xampp\htdocs\sun> php migrate run
```

Output:

```
Executing migrations...
  ✓ 2024_12_07_143500_create_products_table

Migration complete! (1 migration executed)
```

**4. Batalkan Migration Terakhir (Rollback)**

```bash
C:\xampp\htdocs\sun> php migrate rollback
```

Output:

```
Rolling back migrations...
  ✓ 2024_12_07_143500_create_products_table

Rollback complete! (1 migration rolled back)
```

**5. Refresh Database (Rollback + Run semua)**

```bash
C:\xampp\htdocs\sun> php migrate refresh
```

Output:

```
Rolling back all migrations...
  ✓ 2024_12_07_143500_create_products_table
  ✓ 2024_01_15_000000_create_users_table

Running all migrations...
  ✓ 2024_01_15_000000_create_users_table
  ✓ 2024_12_07_143500_create_products_table

Database refreshed!
```

**6. Reset Database (Hapus semua, jangan run)**

```bash
C:\xampp\htdocs\sun> php migrate reset
```

Output:

```
Rolling back all migrations...
  ✓ 2024_12_07_143500_create_products_table
  ✓ 2024_01_15_000000_create_users_table

All migrations rolled back!
```

**7. Tampilkan Help / Bantuan**

```bash
C:\xampp\htdocs\sun> php migrate help
```

---

## 🚀 Penggunaan Sehari-hari

### Scenario 1: Membuat Table Baru

```bash
# 1. Buat migration file
php migrate make:create create_products_table

# 2. Edit file: database/migrations/2024_01_15_xxx_create_products_table.php
# Tulis struktur table products

# 3. Jalankan migration
php migrate run

# Result: Table products terbuat di database
```

### Scenario 2: Menambah Kolom ke Table Existing

```bash
# 1. Buat migration (bukan create, tapi update)
php migrate make:create add_status_to_users

# 2. Edit file, gunakan alter bukan create:
Schema::table('users', function(Blueprint $table) {
    $table->enum('status', ['active', 'inactive'])->default('active');
    $table->string('phone')->nullable();
});

# 3. Jalankan
php migrate run

# Result: Kolom status dan phone ditambah ke table users
```

### Scenario 3: Ada Kesalahan, Ingin Rollback

```bash
# Anda: "Oops, struktur salah, mau dibatalkan"

# Jalankan rollback
php migrate rollback

# Database kembali seperti sebelum migration terakhir dijalankan
# Table yang baru dibuat akan dihapus
# Kolom yang ditambah akan dihapus

# Setelah fix migration file, jalankan lagi
php migrate run
```

### Scenario 4: Cek Status Migration

```bash
php migrate status

# Output:
# Executed Migrations:
#   ✓ 2024_01_15_093000_create_users_table (Batch 1)
#   ✓ 2024_01_20_120000_create_posts_table (Batch 1)
#
# Pending Migrations:
#   ○ 2024_02_05_140000_add_status_to_posts
```

---

## 💡 Keuntungan vs Kerugian

### ✅ Keuntungan

| Keuntungan               | Penjelasan                                             |
| ------------------------ | ------------------------------------------------------ |
| **Reproducible**         | Database bisa di-setup ulang dari awal dengan sempurna |
| **Collaborative**        | Semua dev punya database sama                          |
| **Version Control**      | Bisa track siapa yang ubah apa                         |
| **Easy Deployment**      | Upload ke production sangat mudah                      |
| **Rollback Safe**        | Bisa undo perubahan database dengan aman               |
| **Dokumentasi Otomatis** | Migration file = dokumentasi struktur database         |
| **No Manual Query**      | Tidak perlu menulis SQL, pakai PHP API                 |

### ⚠️ Kerugian

| Kerugian                        | Solusi                     |
| ------------------------------- | -------------------------- |
| **Lebih kompleks untuk pemula** | Dokumentasi dan practice   |
| **Harus jalankan command**      | Jadikan routine/habit      |
| **Jika lupa run migration**     | Punya deployment checklist |

---

## 🔄 Workflow Praktis untuk Proyekmu

### Day 1: Setup Awal

```bash
# 1. Clone project
git clone <project>

# 2. Run migration untuk setup database
php migrate run

# Done! Database sudah siap dengan struktur lengkap
```

### Day 2: Ditugasi menambah kolom

```bash
# 1. Buat migration
php migrate make:create add_phone_to_users

# 2. Edit file:
Schema::table('users', function(Blueprint $table) {
    $table->string('phone')->nullable();
});

# 3. Jalankan
php migrate run

# 4. Push ke repository
git add database/migrations/...
git commit -m "Add phone column to users table"
git push

# Teman Anda tinggal:
# - git pull
# - php migrate run
```

### Day 3: Ada bug di migration sebelumnya

```bash
# 1. Undo migration terakhir
php migrate rollback

# 2. Edit file yang salah
# database/migrations/add_phone_to_users.php

# 3. Jalankan lagi
php migrate run

# 4. Push ulang
git add database/migrations/...
git commit -m "Fix: validate phone format"
git push
```

---

## 📋 Checklist untuk Memulai

- [ ] Baca dokumentasi lengkap: `doc/DATABASE_MIGRATIONS.md`
- [ ] Coba command: `php migrate status`
- [ ] Buat migration pertama: `php migrate make:create test_table`
- [ ] Edit file dan run: `php migrate run`
- [ ] Lihat di phpmyadmin/database client
- [ ] Coba rollback: `php migrate rollback`
- [ ] Coba run lagi
- [ ] Pelajari contoh-contoh di dokumentasi

---

## ❓ FAQ Pemula

### Q: Apakah saya harus jalan migration setiap kali buat file?

**A:** Ya, setiap kali Anda buat/edit migration file, jalankan `php migrate run` untuk apply perubahan ke database.

### Q: Apakah aman di-rollback?

**A:** Ya, very safe. Itu justru keuntungan migration. Selalu ada cara untuk undo.

### Q: Bagaimana jika saya lupa migration file?

**A:** Gunakan command `php migrate status` untuk lihat ada berapa migration yang belum dijalankan.

### Q: Apakah harus pakai migration?

**A:** Tidak wajib, tapi sangat recommended untuk project serius (terutama team project atau production).

### Q: Bagaimana dengan data existing di database?

**A:** Migration untuk schema (struktur), bukan data. Data tetap aman. Ada fitur "seeders" untuk populate data (topik advanced).

### Q: Bisa rollback di production?

**A:** Bisa, tapi hati-hati. Pastikan sudah test di development dulu.

---

## 🎬 CONTOH PRAKTIS LANGSUNG (Live Demo)

Berikut adalah contoh **langkah demi langkah** yang bisa Anda coba sekarang juga!

### Contoh 1: Membuat Table Produk dari Awal

**Langkah 1: Buka Terminal**

Tekan `Ctrl + backtick` di VS Code atau buka PowerShell dan masuk ke folder:

```bash
cd C:\xampp\htdocs\sun
```

**Langkah 2: Cek Status Awal**

```bash
C:\xampp\htdocs\sun> php migrate status

Output:
Executed Migrations:
  ✓ 2024_01_15_000000_create_users_table (Batch 1)

Pending Migrations:
  (none)
```

**Langkah 3: Buat Migration Baru**

```bash
C:\xampp\htdocs\sun> php migrate make:create create_products_table

Output:
✓ Migration created: database/migrations/2024_12_07_160530_create_products_table.php
```

**Langkah 4: Edit File yang Dibuat**

Buka file: `database/migrations/2024_12_07_160530_create_products_table.php`

Ganti isi `up()` method dengan:

```php
public function up()
{
    Schema::create('products', function(Blueprint $table) {
        $table->id();                           // ID auto increment
        $table->string('name');                 // Nama produk
        $table->text('description')->nullable();// Deskripsi (boleh kosong)
        $table->decimal('price', 10, 2);       // Harga
        $table->integer('stock');               // Stok
        $table->string('sku')->unique();        // SKU unik
        $table->enum('status', ['active', 'inactive'])->default('active');
        $table->timestamps();                   // created_at, updated_at
    });
}
```

**Langkah 5: Jalankan Migration**

```bash
C:\xampp\htdocs\sun> php migrate run

Output:
Executing migrations...
  ✓ 2024_12_07_160530_create_products_table

Migration complete! (1 migration executed)
```

**Langkah 6: Verifikasi di Database**

Buka XAMPP Control Panel → klik "Admin" di MySQL (phpMyAdmin) → Anda akan melihat table `products` dengan semua kolom yang Anda definisikan.

**Langkah 7: Cek Status Lagi**

```bash
C:\xampp\htdocs\sun> php migrate status

Output:
Executed Migrations:
  ✓ 2024_01_15_000000_create_users_table (Batch 1)
  ✓ 2024_12_07_160530_create_products_table (Batch 2)

Pending Migrations:
  (none)
```

✅ **Selesai! Table products sudah terbuat dengan struktur yang sempurna!**

---

### Contoh 2: Menambah Kolom ke Table Users

**Skenario:** Anda ingin menambah kolom `phone`, `address`, dan `gender` ke table users.

**Langkah 1: Buat Migration**

```bash
C:\xampp\htdocs\sun> php migrate make:create add_contact_info_to_users

Output:
✓ Migration created: database/migrations/2024_12_07_160545_add_contact_info_to_users.php
```

**Langkah 2: Edit File**

Buka file dan ubah method `up()`:

```php
public function up()
{
    Schema::table('users', function(Blueprint $table) {
        $table->string('phone')->nullable()->after('email');
        $table->text('address')->nullable()->after('phone');
        $table->enum('gender', ['male', 'female'])->nullable()->after('address');
    });
}

public function down()
{
    Schema::table('users', function(Blueprint $table) {
        $table->dropColumn('phone');
        $table->dropColumn('address');
        $table->dropColumn('gender');
    });
}
```

**Langkah 3: Jalankan**

```bash
C:\xampp\htdocs\sun> php migrate run

Output:
Executing migrations...
  ✓ 2024_12_07_160545_add_contact_info_to_users

Migration complete! (1 migration executed)
```

✅ **Selesai! Kolom phone, address, dan gender sudah ditambah ke users!**

---

### Contoh 3: Membatalkan (Rollback) jika Ada Kesalahan

**Skenario:** Oops! Anda salah menambahkan kolom, ingin dibatalkan.

**Langkah 1: Rollback**

```bash
C:\xampp\htdocs\sun> php migrate rollback

Output:
Rolling back migrations...
  ✓ 2024_12_07_160545_add_contact_info_to_users

Rollback complete! (1 migration rolled back)
```

**Langkah 2: Verifikasi**

```bash
C:\xampp\htdocs\sun> php migrate status

Output:
Executed Migrations:
  ✓ 2024_01_15_000000_create_users_table (Batch 1)
  ✓ 2024_12_07_160530_create_products_table (Batch 2)

Pending Migrations:
  ○ 2024_12_07_160545_add_contact_info_to_users
```

**Langkah 3: Edit File Lagi**

Buka file `2024_12_07_160545_add_contact_info_to_users.php` dan fix kesalahan.

**Langkah 4: Jalankan Lagi**

```bash
C:\xampp\htdocs\sun> php migrate run

Output:
Executing migrations...
  ✓ 2024_12_07_160545_add_contact_info_to_users

Migration complete! (1 migration executed)
```

✅ **Selesai! Migration sudah fixed dan ditjalankan ulang!**

---

## 🎓 Langkah Berikutnya

1. **Baca:** Dokumentasi lengkap di `doc/DATABASE_MIGRATIONS.md`
2. **Praktik:** Buat beberapa migration sendiri
3. **Eksperimen:** Coba rollback, run lagi, dll
4. **Master:** Pelajari fitur advanced (foreign keys, indexes, etc)

---

**Happy Migrating! 🚀**
