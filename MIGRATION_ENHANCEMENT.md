# Perubahan dan Peningkatan pada Migration System

## ✨ Fitur Baru yang Ditambahkan

### 1. **Seeder System**

- File: `system/database/Seeder.php`
- Base class untuk membuat seeder
- Helper methods: `insert()`, `insertBulk()`, `truncate()`, `delete()`
- Support memanggil seeder dari seeder lain dengan `call()`

### 2. **Faker Helper**

- Generate dummy data otomatis
- Support types: name, email, phone, address, company, text, number, boolean, date, uuid
- Contoh: `$this->faker('email')` → generates random email

### 3. **Seeder Manager**

- File: `system/database/SeederManager.php`
- Menjalankan seeders
- Support run single seeder atau semua seeder

### 4. **Enhanced CLI Commands**

- File: `system/database/Migration/MigrationCLIEnhanced.php`
- New commands untuk seeder
- New commands untuk migration management

### 5. **Dokumentasi Lengkap**

- File: `doc/SEEDER_GUIDE.md`
- Contoh penggunaan seeder
- Workflow lengkap

### 6. **Contoh Seeder**

- `storage/database/seeders/TestingSeeder.php`
- `storage/database/seeders/AdvancedTestingSeeder.php`

## 📋 Perintah Baru

```bash
# Seeder commands
php sun migrate make:seed <name>        # Buat seeder
php sun migrate seed <name>             # Jalankan seeder
php sun migrate seed:all                # Jalankan semua
php sun migrate seed:refresh            # Reset migrations & seed

# Migration improvements
php sun migrate fresh                   # Drop all & re-migrate (confirm)
php sun migrate list                    # List semua migrations
```

## 🎯 Penggunaan Seeder

```php
<?php

class UserSeeder extends Seeder
{
    public function run()
    {
        // Kosongkan tabel
        $this->truncate('users');

        // Insert data
        $this->insertBulk('users', [
            [
                'name' => 'Admin',
                'email' => 'admin@example.com',
            ],
        ]);

        // Generate dummy data
        for ($i = 0; $i < 10; $i++) {
            $this->insert('users', [
                'name' => $this->faker('name'),
                'email' => $this->faker('email'),
            ]);
        }

        echo "✓ Users seeded\n";
    }
}
```

## 🚀 Next Steps

Untuk menggunakan sistem ini:

1. **Buat migration** → `php sun migrate make:create create_table`
2. **Edit migration file** → Define schema
3. **Jalankan migration** → `php sun migrate run`
4. **Buat seeder** → `php sun migrate make:seed table_seeder`
5. **Edit seeder file** → Define data
6. **Jalankan seeder** → `php sun migrate seed`

Atau gunakan single command untuk setup lengkap:

```bash
php sun migrate seed:refresh    # Reset migrations dan run seeders
```
