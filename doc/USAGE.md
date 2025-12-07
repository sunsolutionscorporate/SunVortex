# SunVortex — Panduan Penggunaan Lengkap (USAGE.md)

Panduan operasional step-by-step untuk developer yang ingin menggunakan SunVortex dalam produksi. Dokumen ini mencakup instalasi, konfigurasi, pembuatan fitur (controller, model, view), migrasi, seeder, dan deployment.

**Target audiens:** Backend developer PHP, DevOps, QA yang perlu memahami workflow operasional framework ini.

---

## Daftar Isi

1. [Persyaratan & Instalasi](#instalasi)
2. [Konfigurasi Lingkungan](#konfigurasi)
3. [Struktur Proyek & Konvensi](#struktur)
4. [Workflow Pengembangan Fitur](#workflow)
5. [Database: Migrasi & Seeder](#database)
6. [Menjalankan Aplikasi](#menjalankan)
7. [Testing & Debugging](#testing)
8. [Deployment](#deployment)
9. [Troubleshooting](#troubleshooting)

---

## Instalasi

### Persyaratan Minimal

- **PHP:** 7.3 atau lebih tinggi
- **Extensions:** PDO + driver database (pdo_mysql, pdo_pgsql, atau pdo_sqlite)
- **Package Manager:** Composer
- **Web Server:** Apache 2.4+ atau nginx 1.10+

### Clone & Setup (Windows PowerShell)

```powershell
# Clone repository
git clone https://github.com/sunsolutionscorporate/SunVortex C:\xampp\htdocs\sun
cd C:\xampp\htdocs\sun

# Install dependencies
composer install

# Copy environment file (jika ada template)
if (Test-Path .env.example) {
    Copy-Item .env.example .env
}

# Pastikan folder storage writable
New-Item -ItemType Directory -Force -Path .\storage

# Beri permissions (Windows: skip, hanya pastikan folder accessible)
```

### Verifikasi Instalasi

```powershell
# Test PHP CLI
php --version

# Test database connection
php sun test:db

# List available commands
php sun
```

---

## Konfigurasi

### File `.env` — Template Standar

Buat file `.env` di root proyek dengan isi:

```env
# Environment & App Settings
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost:8000/sun
APP_NAME=SunVortex

# Database Configuration
# Format: Driver (mysql|pgsql|sqlite), host, port, database, username, password
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sun_dev
DB_USERNAME=root
DB_PASSWORD=

# Multi-Database (JSON format, if using)
# DB_CONFIG='[{"driver":"mysql","host":"127.0.0.1","port":3306,"database":"sun_dev","username":"root","password":""}]'

# Query Caching
ENABLE_QUERY_CACHE=true
DEFAULT_QUERY_CACHE_TTL=600

# Query Profiling (development only)
QUERY_PROFILER_ENABLED=true

# Logging
LOG_LEVEL=debug
LOG_FILE_PATH=storage/logs/app.log

# Email (if needed)
MAIL_DRIVER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=465
MAIL_USERNAME=
MAIL_PASSWORD=

# Security
CSRF_ENABLED=true
```

### Multi-Database Configuration (Opsional)

Jika menggunakan multiple database connections:

```env
DB_CONFIG='[
  {
    "driver": "mysql",
    "host": "127.0.0.1",
    "port": 3306,
    "database": "sun_main",
    "username": "root",
    "password": ""
  },
  {
    "driver": "mysql",
    "host": "127.0.0.1",
    "port": 3306,
    "database": "sun_reporting",
    "username": "root",
    "password": ""
  }
]'
```

### Database Setup (MySQL Example)

```sql
-- Login ke MySQL
mysql -u root -p

-- Create database
CREATE DATABASE sun_dev CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create user (optional)
CREATE USER 'sun_user'@'127.0.0.1' IDENTIFIED BY 'strong_password';
GRANT ALL PRIVILEGES ON sun_dev.* TO 'sun_user'@'127.0.0.1';
FLUSH PRIVILEGES;

-- Verify
SHOW DATABASES;
```

---

## Struktur Proyek & Konvensi

```
sun/
├── app/                      # Aplikasi (controllers, models, views, middleware)
│   ├── controllers/          # HTTP request handlers
│   │   ├── ExampleCrud.php
│   │   └── api/              # API endpoints
│   ├── models/               # Database models (extend BaseModel)
│   │   └── example/
│   │       └── Example_model.php
│   ├── views/                # Template PHP/HTML
│   │   └── example/
│   │       ├── index.php
│   │       └── form.php
│   └── middleware/           # HTTP middleware
│
├── system/                   # Core framework (jangan dimodifikasi)
│   ├── Core/                 # Base classes (Controller, BaseModel)
│   ├── database/             # Database layer
│   ├── Cache/                # Caching system
│   ├── Http/                 # Request/Response
│   ├── Support/              # Helper functions
│   └── Bootstrap.php         # Kernel / entry point
│
├── storage/                  # Generated files (logs, cache, migrations)
│   ├── database/
│   │   ├── migrations/       # Migration files
│   │   └── seeders/          # Seeder files
│   ├── logs/                 # Application logs
│   └── images/               # Uploaded files
│
├── public/                   # Web root (document root)
│   ├── index.php             # Entry point
│   └── html/                 # Static assets (CSS, JS)
│
├── doc/                      # Internal documentation
│   ├── USAGE.md              # Ini (usage guide)
│   ├── API.md                # API reference
│   ├── EXAMPLES.md           # Code examples
│   └── gpt/                  # Generated docs
│
├── .env                      # Configuration (local, not in git)
├── .gitignore                # VCS ignore list
├── composer.json             # PHP dependencies
└── sun                       # CLI entry point (shebang: #!/usr/bin/php)
```

### Naming Conventions

| Item           | Pattern                         | Example                                    |
| -------------- | ------------------------------- | ------------------------------------------ |
| **Controller** | PascalCase                      | `UserController.php`, `ExampleCrud.php`    |
| **Model**      | PascalCase + `_model`           | `User_model.php`, `Example_model.php`      |
| **View**       | snake_case                      | `user/index.php`, `post/create.php`        |
| **Migration**  | `YYYY_MM_DD_HHMMSS_description` | `2025_12_07_101500_create_users_table.php` |
| **Seeder**     | PascalCase                      | `UserSeeder.php`, `PermissionSeeder.php`   |
| **Table**      | plural, snake_case              | `users`, `user_roles`, `posts`             |

---

## Workflow Pengembangan Fitur

### Skenario: Membuat CRUD Produk

#### Step 1: Design Database (Migration)

```bash
php sun migrate make:create create_products_table
```

Buka `storage/database/migrations/2025_MM_DD_HHMMSS_create_products_table.php`:

```php
<?php

use System\Database\Migration\Migration;

class CreateProductsTable extends Migration {
    public function up() {
        $this->schema->create('products', function(Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->integer('stock')->default(0);
            $table->enum('status', ['active', 'inactive', 'discontinued'])->default('active');
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['status']);
            $table->unique(['name']);
        });
    }

    public function down() {
        $this->schema->dropIfExists('products');
    }
}
```

Jalankan migrasi:

```bash
php sun migrate run
```

#### Step 2: Buat Model

File: `app/models/product/Product_model.php`

```php
<?php

class Product_model extends BaseModel {
    protected $table = 'products';

    // Whitelist mass-assignment
    protected $fillable = ['name', 'description', 'price', 'stock', 'status'];

    // Type casting
    protected $casts = [
        'price' => 'float',
        'stock' => 'int',
        'status' => 'string',
    ];

    // Soft delete
    protected $softDelete = true;

    // Custom queries
    public function active() {
        return $this->db->table($this->table)
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->getResultArray();
    }

    public function getByCategory($catId) {
        return $this->db->table($this->table)
            ->where('category_id', $catId)
            ->orderBy('name')
            ->getResultArray();
    }
}
```

#### Step 3: Buat Controller

File: `app/controllers/ProductController.php`

```php
<?php

class ProductController extends Controller {
    public function index() {
        $model = new Product_model();
        $products = $model->active();
        return $this->view('product/index', ['products' => $products]);
    }

    public function create() {
        return $this->view('product/form', ['product' => null]);
    }

    public function store() {
        $data = $this->request->all();
        $model = new Product_model($data);

        if ($model->save()) {
            return Response::redirect('/products')->with('success', 'Product created');
        }

        return Response::redirect('/products/create')->with('error', 'Creation failed');
    }

    public function edit($id) {
        $model = new Product_model();
        $product = $model->find($id);

        if (!$product) {
            return Response::status(404)->text('Not Found');
        }

        return $this->view('product/form', ['product' => $product]);
    }

    public function update($id) {
        $model = new Product_model();
        $product = $model->find($id);

        if (!$product) {
            return Response::status(404)->text('Not Found');
        }

        $product->fill($this->request->all());
        $product->save();

        return Response::redirect("/products/{$id}")->with('success', 'Updated');
    }

    public function delete($id) {
        $model = new Product_model();
        $product = $model->find($id);

        if (!$product) {
            return Response::status(404)->text('Not Found');
        }

        $product->delete();  // Soft delete

        return Response::redirect('/products')->with('success', 'Deleted');
    }

    public function show($id) {
        $model = new Product_model();
        $product = $model->find($id);

        if (!$product) {
            return Response::status(404)->text('Not Found');
        }

        return Response::json(['data' => $product->toArray()]);
    }
}
```

#### Step 4: Buat Views

File: `app/views/product/index.php`

```html
<h1>Products</h1>
<a href="/products/create" class="btn btn-primary">Add Product</a>

<table class="table">
  <thead>
    <tr>
      <th>Name</th>
      <th>Price</th>
      <th>Stock</th>
      <th>Status</th>
      <th>Actions</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($products as $p): ?>
    <tr>
      <td><?= htmlspecialchars($p['name']) ?></td>
      <td>
        Rp
        <?= number_format($p['price'], 2) ?>
      </td>
      <td><?= $p['stock'] ?></td>
      <td>
        <span class="badge"><?= $p['status'] ?></span>
      </td>
      <td>
        <a href="/products/<?= $p['id'] ?>/edit" class="btn btn-sm">Edit</a>
        <a
          href="/products/<?= $p['id'] ?>/delete"
          class="btn btn-sm btn-danger"
          onclick="return confirm('Sure?')"
          >Delete</a
        >
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
```

File: `app/views/product/form.php`

```html
<h1><?= $product ? 'Edit' : 'Create' ?> Product</h1>

<form method="POST" action="<?= $product ? "/products/{$product['id']}" : '/products' ?>">
    <input type="text" name="name" placeholder="Product name" value="<?= $product['name'] ?? '' ?>" required>
    <textarea name="description" placeholder="Description"><?= $product['description'] ?? '' ?></textarea>
    <input type="number" name="price" placeholder="Price" step="0.01" value="<?= $product['price'] ?? '' ?>" required>
    <input type="number" name="stock" placeholder="Stock" value="<?= $product['stock'] ?? 0 ?>" required>

    <select name="status">
        <option value="active" <?= ($product['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
        <option value="inactive" <?= ($product['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
        <option value="discontinued" <?= ($product['status'] ?? '') === 'discontinued' ? 'selected' : '' ?>>Discontinued</option>
    </select>

    <button type="submit">Save</button>
</form>
```

---

## Database: Migrasi & Seeder

### Creating & Running Migrations

```bash
# Create migration (auto-generates timestamp)
php sun migrate make:create create_users_table
php sun migrate make:create add_age_to_users_table

# Run all pending migrations
php sun migrate run

# Rollback last batch
php sun migrate rollback

# Rollback all migrations
php sun migrate reset

# Rollback & re-run
php sun migrate refresh

# Check status
php sun migrate status
```

### Writing Effective Migrations

**Do's:**

- ✅ Always provide `down()` method untuk rollback safety
- ✅ Use `Schema` helpers untuk DB-agnostic code
- ✅ Include indexes untuk frequently-queried columns
- ✅ Test migration rollback sebelum push

**Don'ts:**

- ❌ Jangan hard-code data di migrasi (gunakan seeder)
- ❌ Jangan include complex business logic
- ❌ Jangan skip `down()` method
- ❌ Jangan modify `.env` dalam migrasi

Contoh kompleks:

```php
<?php

use System\Database\Migration\Migration;

class CreateComplexSchema extends Migration {
    public function up() {
        // Users table
        $this->schema->create('users', function($table) {
            $table->id();
            $table->string('email', 255)->unique();
            $table->string('password', 255);
            $table->enum('role', ['admin', 'user', 'guest'])->default('user');
            $table->timestamp('last_login')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Posts table with foreign key
        $this->schema->create('posts', function($table) {
            $table->id();
            $table->foreignId('user_id');  // Reference users.id
            $table->string('title', 255);
            $table->text('content');
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->timestamps();

            // Indexes
            $table->index(['user_id']);
            $table->index(['status']);
        });
    }

    public function down() {
        $this->schema->dropIfExists('posts');
        $this->schema->dropIfExists('users');
    }
}
```

### Creating & Running Seeders

```bash
# Create seeder
php sun migrate make:seed UserSeeder
php sun migrate make:seed ProductSeeder

# Run seeder
php sun migrate seed UserSeeder

# Run all seeders (if DatabaseSeeder exists)
php sun migrate seed DatabaseSeeder
```

Contoh seeder kompleks:

```php
<?php

use System\Database\Migration\Seeder;

class UserSeeder extends Seeder {
    public function run() {
        // Clear existing data
        $this->truncate('users');

        // Add admin user
        $this->insert('users', [
            'email' => 'admin@example.com',
            'password' => password_hash('admin123', PASSWORD_BCRYPT),
            'role' => 'admin',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // Add test users
        for ($i = 0; $i < 50; $i++) {
            $this->insert('users', [
                'email' => $this->faker('email'),
                'password' => password_hash('password', PASSWORD_BCRYPT),
                'role' => rand(0, 10) > 8 ? 'admin' : 'user',  // 20% admin
                'created_at' => date('Y-m-d H:i:s', strtotime("-{$i} days")),
            ]);
        }
    }
}

class ProductSeeder extends Seeder {
    public function run() {
        $this->truncate('products');

        $categories = ['Electronics', 'Clothing', 'Books', 'Home'];

        for ($i = 1; $i <= 100; $i++) {
            $this->insert('products', [
                'name' => 'Product ' . $i,
                'description' => $this->faker('text', ['length' => 200]),
                'price' => rand(10000, 1000000) / 100,
                'stock' => rand(0, 100),
                'status' => rand(0, 100) > 10 ? 'active' : 'inactive',
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }
}

// Master seeder to run all
class DatabaseSeeder extends Seeder {
    public function run() {
        $this->call('UserSeeder');
        $this->call('ProductSeeder');
        // ... call other seeders
    }
}
```

---

## Menjalankan Aplikasi

### Development Server

```bash
# Using PHP built-in server
cd public
php -S localhost:8000

# Then open: http://localhost:8000/
```

### Web Server (Apache/nginx)

**Apache** - Set document root ke folder `public/`:

```apache
<VirtualHost *:80>
    ServerName localhost
    DocumentRoot /path/to/sun/public

    <Directory /path/to/sun/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

**nginx** - Configure server block:

```nginx
server {
    listen 80;
    server_name localhost;
    root /path/to/sun/public;

    location / {
        index index.php;
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### CLI Commands

```bash
# Available commands
php sun

# Migrate
php sun migrate run
php sun migrate rollback
php sun migrate status

# Seeder
php sun migrate seed UserSeeder

# Test
php sun test:db

# Help
php sun help command_name
```

---

## Testing & Debugging

### Query Profiling

Set di `.env`:

```env
QUERY_PROFILER_ENABLED=true
```

Access profiler:

```php
$db = Database::init();
$profiler = $db->getProfiler();

// Get executed queries
$queries = $profiler->getQueries();
foreach ($queries as $q) {
    echo "SQL: " . $q['sql'] . " | Time: " . $q['duration'] . "ms\n";
}
```

### Logging

Enable logging:

```php
// In controller or model
class ProductController extends Controller {
    public function index() {
        if (class_exists('Logger')) {
            Logger::info('Product list accessed');
        }
        // ...
    }
}
```

Logs written to `storage/logs/app.log`.

### Manual Testing

```bash
# Test database connection
php sun test:db

# Test CLI
php sun migrate status
```

---

## Deployment

### Pre-Deployment Checklist

```bash
# 1. Run tests
php sun test:db
php sun migrate status

# 2. Verify all migrations
php sun migrate status

# 3. Check file permissions
ls -la storage/

# 4. Review .env (staging/production)
cat .env

# 5. Test migrations on staging
php sun migrate run  # On staging DB first
```

### Production Deployment Steps

```bash
# On server:
cd /var/www/sun

# 1. Pull latest code
git pull origin main

# 2. Install dependencies
composer install --no-dev --optimize-autoloader

# 3. Set permissions
chmod -R 755 public
chmod -R 777 storage

# 4. Run migrations
php sun migrate run

# 5. (Optional) Run seeders if needed
php sun migrate seed DatabaseSeeder

# 6. Clear cache
rm -rf storage/cache/*

# 7. Restart PHP-FPM
systemctl restart php-fpm
```

### Environment Configuration (Production)

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://example.com

DB_HOST=db.production.com
DB_DATABASE=prod_sun
DB_USERNAME=prod_user
DB_PASSWORD=strong_production_password

ENABLE_QUERY_CACHE=true
DEFAULT_QUERY_CACHE_TTL=3600

QUERY_PROFILER_ENABLED=false

CSRF_ENABLED=true
```

---

## Troubleshooting

### Database Connection Errors

**Error:** `PDOException: SQLSTATE[HY000] [2002] Connection refused`

```bash
# Solution: Check MySQL is running
mysql -u root -p -h 127.0.0.1 -e "SELECT 1"

# Verify .env
cat .env | grep DB_

# Test connection via PHP
php sun test:db
```

### Migration Errors

**Error:** `Migration class not found`

```bash
# Cause: Class name mismatch with filename
# Solution: Ensure naming matches convention:
# File: 2025_12_07_HHMMSS_create_products_table.php
# Class: CreateProductsTableMigration
```

### Permission Errors

**Error:** `storage/: Permission denied`

```bash
# Solution: Set writable
chmod -R 777 storage

# Or (safer):
chmod -R 755 storage
chown -R www-data:www-data storage  # nginx/Apache user
```

### Missing Classes

**Error:** `Class 'Database' not found`

```bash
# Solution: Check Autoload is properly initialized
# In system/Bootstrap.php, verify Autoload::from() calls

# Or include manually:
require_once 'system/database/Database.php';
```

### Query Cache Issues

**Error:** Stale data after update

```bash
# Solution: Disable cache for debugging
ENABLE_QUERY_CACHE=false

# Or clear cache
rm -rf storage/cache/*
```

---

**Next Steps:**

- Review `doc/API.md` untuk referensi API lengkap
- Check `doc/EXAMPLES.md` untuk kode-kode nyata
- Read `system/Bootstrap.php` untuk routing details
