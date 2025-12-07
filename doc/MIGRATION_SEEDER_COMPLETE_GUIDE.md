# 📚 Panduan Lengkap: Migration + Seeder System yang Enhanced

## 🎯 Apa yang Sudah Ditambahkan?

### Core Files (Framework)

1. **`system/database/Seeder.php`**

   - Base class untuk membuat seeder
   - Helper methods untuk insert data
   - Faker class untuk generate dummy data

2. **`system/database/SeederManager.php`**

   - Manager untuk menjalankan seeders
   - Auto-detect seeder files
   - Run single atau all seeders

3. **`system/database/Migration/MigrationCLIEnhanced.php`**
   - Extended CLI commands
   - Lebih lengkap dan fleksibel

### Documentation

4. **`doc/SEEDER_GUIDE.md`** - Dokumentasi seeder lengkap
5. **`doc/CONTOH_MIGRATION.md`** - Contoh penggunaan migration
6. **`MIGRATION_ENHANCEMENT.md`** - Ringkasan perubahan

### Examples

7. **`storage/database/seeders/TestingSeeder.php`** - Seeder simple
8. **`storage/database/seeders/AdvancedTestingSeeder.php`** - Seeder dengan faker

---

## 📖 Penggunaan Praktis

### Scenario: Buat Tabel Testing + Isi Data

#### Step 1: Buat Migration

```bash
php sun migrate make:create create_testing_table
```

File: `storage/database/migrations/2025_12_07_XXXXXX_create_testing_table.php`

```php
<?php

class CreateTestingTableMigration extends Migration
{
    public function up()
    {
        $this->create('testing', function(Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('jenis');
            $table->string('lokasi');
            $table->timestamps();
        });
    }

    public function down()
    {
        $this->dropIfExists('testing');
    }
}
```

#### Step 2: Buat Seeder

```bash
php sun migrate make:seed testing_seeder
```

File: `storage/database/seeders/TestingSeeder.php`

```php
<?php

class TestingSeeder extends Seeder
{
    public function run()
    {
        $this->truncate('testing');

        $this->insertBulk('testing', [
            ['nama' => 'Item 1', 'jenis' => 'Tipe A', 'lokasi' => 'Jakarta'],
            ['nama' => 'Item 2', 'jenis' => 'Tipe B', 'lokasi' => 'Surabaya'],
            ['nama' => 'Item 3', 'jenis' => 'Tipe C', 'lokasi' => 'Bandung'],
        ]);

        echo "✓ Testing table seeded\n";
    }
}
```

#### Step 3: Jalankan

```bash
# Buat tabel
php sun migrate run

# Isi data
php sun migrate seed testing_seeder
```

**Hasil**: Tabel `testing` dengan 3 data baris!

---

## 🔧 Semua Commands

### Migration Commands

```bash
php sun migrate make:create <name>      # Buat migration create
php sun migrate make:alter <name>       # Buat migration alter
php sun migrate run                     # Jalankan pending
php sun migrate rollback [steps]        # Batalkan (default 1)
php sun migrate refresh                 # Reset + run
php sun migrate reset                   # Rollback semua
php sun migrate fresh                   # Drop + run (confirm)
php sun migrate status                  # Lihat status
php sun migrate list                    # List migrations
```

### Seeder Commands

```bash
php sun migrate make:seed <name>        # Buat seeder
php sun migrate seed [name]             # Jalankan seeder
php sun migrate seed:all                # Jalankan semua seeder
php sun migrate seed:refresh            # Reset + seed
```

### Help

```bash
php sun migrate help
```

---

## 🎲 Fitur Faker

Generate data dummy otomatis dalam seeder:

```php
$this->faker('name')        // "John Smith"
$this->faker('email')       // "john.smith@gmail.com"
$this->faker('phone')       // "+62812345678"
$this->faker('address')     // "123 Jl. Main, Jakarta"
$this->faker('company')     // "Tech Solutions"
$this->faker('text', ['length' => 100])     // Random text
$this->faker('number', ['min' => 1, 'max' => 100])  // Random 1-100
$this->faker('boolean')     // true/false
$this->faker('date')        // "2025-01-15"
$this->faker('uuid')        // "550e8400-e29b-41d4-a716-446655440000"
```

### Contoh Generate 100 Users:

```php
$users = [];
for ($i = 0; $i < 100; $i++) {
    $users[] = [
        'name' => $this->faker('name'),
        'email' => $this->faker('email'),
        'phone' => $this->faker('phone'),
        'company' => $this->faker('company'),
        'address' => $this->faker('address'),
    ];
}
$this->insertBulk('users', $users);
```

---

## 📊 Advanced: Multiple Seeders

### DatabaseSeeder (Main Seeder)

```bash
php sun migrate make:seed database_seeder
```

```php
<?php

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // Jalankan seeder lain
        $this->call('UserSeeder');
        $this->call('PostSeeder');
        $this->call('CommentSeeder');

        echo "✓ All seeders completed\n";
    }
}
```

Jalankan semua:

```bash
php sun migrate seed database_seeder
```

---

## 💡 Best Practices

### 1. Order Matters

```
User → Post → Comment
(parent) → (child dengan FK)
```

Migration user harus dijalankan sebelum post.

### 2. Always Rollback Test

```bash
php sun migrate run
php sun migrate seed
php sun migrate rollback
# Pastikan down() bekerja
```

### 3. Seeder untuk Development Only

```php
public function run()
{
    // Jangan dijalankan di production
    if (php_uname('s') === 'Linux' && getenv('APP_ENV') === 'production') {
        return;
    }

    $this->truncate('testing');
    // ... insert data
}
```

### 4. Backup Data Penting

```php
// Jangan truncate data penting
// Gunakan condition
$this->delete('users', 'id > 100');  // Hapus user id > 100
```

---

## ❌ Troubleshooting

### Q: Seeder tidak ditemukan

**A:** Pastikan:

- File ada di `storage/database/seeders/`
- Nama class sesuai: `TestingSeeder` untuk file `TestingSeeder.php`

### Q: Error "Table not found"

**A:** Jalankan migration dulu:

```bash
php sun migrate run
```

### Q: Rollback seeder

**A:** Seeder tidak auto-rollback. Manual:

```php
// Di seeder
public function run() {
    $this->truncate('testing');
    // ... insert data
}

// Untuk undo, delete atau edit database
```

### Q: Change seeder path

**A:** Edit di SeederManager:

```php
self::$seedersPath = APP_PATH . 'custom/path/seeders';
```

---

## 🚀 Workflow Lengkap: Blog Setup

```bash
# 1. Create structure
php sun migrate make:create create_users_table
php sun migrate make:create create_posts_table
php sun migrate make:create create_comments_table

# 2. Create seeders
php sun migrate make:seed user_seeder
php sun migrate make:seed post_seeder
php sun migrate make:seed comment_seeder

# 3. Edit all files dengan data schema & seed

# 4. Setup
php sun migrate run              # Buat semua tabel
php sun migrate seed             # Isi semua data

# 5. Verify
php sun migrate status           # Lihat migrations
```

---

## 📌 Summary

| Fitur         | Command          | Fungsi              |
| ------------- | ---------------- | ------------------- |
| **Migration** | `make:create`    | Buat tabel baru     |
|               | `make:alter`     | Ubah tabel          |
|               | `run`            | Jalankan migration  |
|               | `rollback`       | Batalkan migration  |
|               | `status`         | Lihat status        |
| **Seeder**    | `make:seed`      | Buat seeder         |
|               | `seed`           | Jalankan seeder     |
|               | `seed:all`       | Jalankan semua      |
|               | `seed:refresh`   | Reset + seed        |
| **Faker**     | `faker('name')`  | Generate dummy data |
|               | `faker('email')` | Generate email      |
|               | dll              | More types...       |

---

## 📚 Dokumentasi Lengkap

Baca file-file berikut untuk info lebih detail:

- `doc/SEEDER_GUIDE.md` - Seeder documentation
- `doc/CONTOH_MIGRATION.md` - Migration examples
- `doc/MIGRATION_USAGE_GUIDE.md` - Usage guide

Selamat menggunakan Enhanced Migration & Seeder System! 🚀
