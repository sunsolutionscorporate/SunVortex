# Enhanced Migration & Seeder System

## 📋 Overview

Sistem migration dan seeder yang diperluas dengan fitur:

- ✅ **Migration** - Versi control untuk database schema
- ✅ **Seeder** - Mengisi data awal/dummy ke database
- ✅ **Faker** - Generate data dummy otomatis
- ✅ **Multiple seeders** - Jalankan banyak seeder sekaligus

---

## 🚀 Migration Commands

### Membuat Migration

```bash
# Create migration (membuat tabel baru)
php sun migrate make:create create_users_table

# Alter migration (mengubah tabel)
php sun migrate make:alter add_phone_to_users_table
```

### Menjalankan Migration

```bash
# Jalankan pending migrations
php sun migrate run

# Lihat status
php sun migrate status

# List semua migrations
php sun migrate list
```

### Rollback & Reset

```bash
# Rollback 1 step terakhir
php sun migrate rollback

# Rollback 3 steps
php sun migrate rollback 3

# Rollback semua
php sun migrate reset

# Reset dan run ulang semua
php sun migrate refresh

# Drop semua dan re-migrate (perlu confirm)
php sun migrate fresh
```

---

## 🌱 Seeder Commands (BARU!)

### Membuat Seeder

```bash
# Buat seeder baru
php sun migrate make:seed user_seeder
```

Hasilnya file di: `storage/database/seeders/UserSeeder.php`

### Menjalankan Seeder

```bash
# Jalankan seeder spesifik
php sun migrate seed user_seeder

# Jalankan semua seeders
php sun migrate seed
php sun migrate seed:all

# Reset migrations dan jalankan seeders
php sun migrate seed:refresh
```

---

## 📝 Contoh: Membuat Tabel Testing dengan Data

### Step 1: Buat Migration

```bash
php sun migrate make:create create_testing_table
```

Edit file migration:

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

### Step 2: Buat Seeder

```bash
php sun migrate make:seed testing_seeder
```

Edit file seeder:

```php
<?php

class TestingSeeder extends Seeder
{
    public function run()
    {
        // Kosongkan tabel
        $this->truncate('testing');

        // Insert data
        $this->insertBulk('testing', [
            [
                'nama' => 'Item 1',
                'jenis' => 'Tipe A',
                'lokasi' => 'Jakarta',
            ],
            [
                'nama' => 'Item 2',
                'jenis' => 'Tipe B',
                'lokasi' => 'Surabaya',
            ],
            [
                'nama' => 'Item 3',
                'jenis' => 'Tipe C',
                'lokasi' => 'Bandung',
            ],
        ]);

        echo "✓ Testing table seeded\n";
    }
}
```

### Step 3: Jalankan

```bash
# Jalankan migration
php sun migrate run

# Isi data dengan seeder
php sun migrate seed testing_seeder
```

**Result**: Tabel `testing` dibuat dengan 3 data baris.

---

## 🎲 Menggunakan Faker (Generate Dummy Data)

Seeder menyediakan helper `faker()` untuk generate data otomatis:

```php
<?php

class UserSeeder extends Seeder
{
    public function run()
    {
        $this->truncate('users');

        // Generate 10 users otomatis
        $users = [];
        for ($i = 0; $i < 10; $i++) {
            $users[] = [
                'nama' => $this->faker('name'),
                'email' => $this->faker('email'),
                'phone' => $this->faker('phone'),
                'alamat' => $this->faker('address'),
                'company' => $this->faker('company'),
            ];
        }

        $this->insertBulk('users', $users);
        echo "✓ 10 users generated\n";
    }
}
```

### Available Faker Types

```php
$this->faker('name')                    // Random nama: "John Smith"
$this->faker('email')                   // Random email: "john.smith@gmail.com"
$this->faker('phone')                   // Random telepon: "+62812345678"
$this->faker('address')                 // Random alamat
$this->faker('company')                 // Random perusahaan
$this->faker('text', ['length' => 100]) // Random text
$this->faker('number', ['min' => 1, 'max' => 100])  // Random number 1-100
$this->faker('boolean')                 // Random true/false
$this->faker('date')                    // Random tanggal
$this->faker('uuid')                    // Random UUID
```

---

## 📚 Contoh: Blog dengan 3 Tabel

### Migration 1: Create Users

```bash
php sun migrate make:create create_users_table
```

```php
public function up()
{
    $this->create('users', function(Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->string('password');
        $table->timestamps();
    });
}
```

### Migration 2: Create Posts

```bash
php sun migrate make:create create_posts_table
```

```php
public function up()
{
    $this->create('posts', function(Blueprint $table) {
        $table->id();
        $table->foreignId('user_id');
        $table->string('title');
        $table->text('content');
        $table->timestamps();
    });
}
```

### Seeder: UserSeeder

```bash
php sun migrate make:seed user_seeder
```

```php
public function run()
{
    $this->truncate('users');

    $this->insertBulk('users', [
        [
            'name' => 'Admin',
            'email' => 'admin@blog.com',
            'password' => password_hash('password', PASSWORD_BCRYPT),
        ],
        [
            'name' => 'Writer',
            'email' => 'writer@blog.com',
            'password' => password_hash('password', PASSWORD_BCRYPT),
        ],
    ]);

    echo "✓ Users seeded\n";
}
```

### Seeder: PostSeeder

```bash
php sun migrate make:seed post_seeder
```

```php
public function run()
{
    $this->truncate('posts');

    $posts = [];
    for ($i = 1; $i <= 5; $i++) {
        $posts[] = [
            'user_id' => 1,
            'title' => "Blog Post {$i}",
            'content' => $this->faker('text', ['length' => 200]),
        ];
    }

    $this->insertBulk('posts', $posts);
    echo "✓ Posts seeded\n";
}
```

### Jalankan Semuanya

```bash
# Setup database
php sun migrate run

# Isi data
php sun migrate seed
```

**Hasilnya**: Database sudah siap dengan users dan posts!

---

## 🔄 Advanced Seeder Features

### Call Seeder dari Seeder Lain

```php
class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call('UserSeeder');
        $this->call('PostSeeder');
        $this->call('CommentSeeder');
    }
}
```

### Direct Database Query

```php
public function run()
{
    // Insert custom
    $this->insert('users', [
        'name' => 'John',
        'email' => 'john@example.com',
    ]);

    // Delete dengan kondisi
    $this->delete('users', "id > 10");
}
```

---

## 📊 Workflow Lengkap

```bash
# 1. Buat migrations
php sun migrate make:create create_users_table
php sun migrate make:create create_posts_table

# 2. Buat seeders
php sun migrate make:seed user_seeder
php sun migrate make:seed post_seeder

# 3. Edit file-file sesuai kebutuhan

# 4. Jalankan migrations
php sun migrate run

# 5. Jalankan seeders
php sun migrate seed

# 6. Verifikasi
php sun migrate status
```

---

## 🛠️ Troubleshooting

### Seeder tidak ditemukan

```
Error: Seeder class UserSeeder not found
```

→ Pastikan file ada di `storage/database/seeders/`
→ Pastikan nama class sesuai dengan nama file

### Data tidak ter-insert

```php
// Pastikan tabel sudah dibuat dulu
// Jalankan migration: php sun migrate run
```

### Rollback seeder

```php
// Seeder tidak bisa auto-rollback
// Manual: truncate atau delete data
$this->delete('users', '1=1');
```

---

## 📋 Cheat Sheet

```bash
# Migration
php sun migrate make:create <table>         # Buat migration
php sun migrate run                         # Jalankan
php sun migrate rollback                    # Batalkan 1 step
php sun migrate status                      # Lihat status

# Seeder
php sun migrate make:seed <name>            # Buat seeder
php sun migrate seed <name>                 # Jalankan seeder
php sun migrate seed                        # Jalankan semua
php sun migrate seed:refresh                # Reset + seed

# Combined
php sun migrate fresh                       # Drop + migrate + seed
```
