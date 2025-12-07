# SunVortex Framework

Framework PHP yang ringan dan siap produksi dengan ORM bawaan, migrasi database, seeder, middleware pipeline, dan fluent query builder.

![PHP Version](https://img.shields.io/badge/PHP-7.3+-blue)
![License](https://img.shields.io/badge/License-MIT-green)
![Status](https://img.shields.io/badge/Status-Production%20Ready-brightgreen)

---

## 🎯 Tentang Framework

**SunVortex** adalah framework PHP modern yang dirancang untuk pengembangan cepat aplikasi web yang scalable. Menggabungkan kesederhanaan dengan fitur-fitur powerful, termasuk:

- **Object-Relational Mapping (ORM)** — Model layer mirip Eloquent dengan CRUD, events, dan relationships
- **Database Migrations** — Version control untuk skema database
- **Data Seeding** — Pembuatan data test/dummy dengan mudah menggunakan faker
- **Middleware Pipeline** — Middleware bawaan CORS, CSRF, Auth, throttling, dan caching
- **Fluent Query Builder** — Pembangunan SQL yang aman tanpa raw SQL
- **Request/Response Abstraction** — Handling HTTP yang clean dengan automatic content negotiation
- **Built-in Caching** — Dukungan cache driver File dan Redis untuk query dan response
- **Reflection-based Routing** — Routing tanpa konfigurasi via method discovery
- **Dependency Injection** — Automatic constructor resolution dan injection

Sempurna untuk developer yang menginginkan **fitur seperti Laravel dengan overhead minimal** dan kontrol maksimal atas codebase mereka.

---

## ✨ Fitur Unggulan

### 🗄️ Database Layer

- **BaseModel ORM** dengan fillable/guarded, timestamps, soft delete, events
- **Type Casting** — Konversi otomatis (int, float, bool, array, json, date)
- **Lifecycle Events** — Hooks before/after save, create, update, delete
- **Query Builder** — Fluent API untuk SELECT, INSERT, UPDATE, DELETE
- **Multiple Connections** — Dukungan MySQL, PostgreSQL, SQLite
- **Transactions** — ACID-compliant dengan savepoint support
- **Query Profiling** — Monitoring performa bawaan

### 🔌 HTTP & Middleware

- **Request Object** — Input, headers, files, authentication, CORS data
- **Response Object** — HTML, JSON, XML, CSV, file downloads, compression
- **6 Middleware Bawaan**
  - CORS (cross-origin resource sharing)
  - CSRF (token validation)
  - Auth (JWT authentication)
  - Throttle (rate limiting)
  - PageCache (response caching)
  - Route (URI routing)
- **Custom Middleware** — Pembuatan custom middleware dengan mudah

### 🛠️ Developer Experience

- **Migration System** — Generate, run, rollback, refresh migrations
- **Seeder System** — Populate database dengan data test/dummy
- **CLI Commands** — Perintah bawaan untuk tugas umum
- **Error Handling** — Custom exception classes dengan logging
- **Support Utilities** — Collection, Pipeline, Helpers, File operations
- **Comprehensive Logging** — Application, query, dan error logging

---

## 🚀 Mulai Cepat

### Persyaratan

- PHP 7.3 atau lebih tinggi
- Composer
- MySQL, PostgreSQL, atau SQLite

### Instalasi

```bash
# Clone repository
git clone https://github.com/sunsolutionscorporate/sun.git
cd sun

# Install dependencies
composer install

# Copy environment file
cp .env.example .env

# Konfigurasi database di .env
# DB_CONFIG={"default":"mysql","connections":{"mysql":{"driver":"mysql","host":"localhost",...}}}

# Jalankan migrations
php sun migrate

# Jalankan seeders (opsional)
php sun seed
```

### Buat Model Pertama Anda

```php
// app/models/Product_model.php
<?php
namespace App\Models;

use System\Core\BaseModel;

class Product_model extends BaseModel {
    protected $fillable = ['name', 'price', 'stock'];
    protected $casts = [
        'price' => 'float',
        'stock' => 'int'
    ];
}
```

### Buat Controller

```php
// app/controllers/ProductController.php
<?php
namespace App\Controllers;

use System\Core\Controller;
use App\Models\Product_model;

class ProductController extends Controller {

    public function index() {
        $products = (new Product_model())->paginate(1, 10);
        return $this->response->json($products);
    }

    public function show($id) {
        $product = (new Product_model())->find($id);
        return $this->response->json($product->toArray());
    }

    public function store() {
        $product = new Product_model($this->request->all());
        $product->save();
        return $this->response->status(201)->json(['id' => $product->id]);
    }
}
```

### Buat Migration

```bash
php sun migrate:create create_products_table
```

```php
// storage/database/migrations/2025_12_08_HHMMSS_create_products_table.php
<?php
use System\database\Migration\Migration;

class CreateProductsTable extends Migration {

    public function up() {
        $this->create('products', function($table) {
            $table->id();
            $table->string('name');
            $table->decimal('price', 10, 2);
            $table->integer('stock');
            $table->timestamps();
        });
    }

    public function down() {
        $this->drop('products');
    }
}
```

---

## 📚 Dokumentasi

Dokumentasi lengkap tersedia di direktori `doc/`:

- **[INDEX.md](doc/INDEX.md)** — Master index dan panduan navigasi
- **[USAGE.md](doc/USAGE.md)** — Panduan step-by-step setup dan penggunaan
- **[API.md](doc/API.md)** — Referensi lengkap method signature
- **[EXAMPLES.md](doc/EXAMPLES.md)** — Contoh kode dan pattern
- **[CORE_BASEMODEL.md](doc/CORE_BASEMODEL.md)** — Dokumentasi ORM
- **[CORE_CONTROLLER.md](doc/CORE_CONTROLLER.md)** — Dokumentasi Controller
- **[HTTP_REQUEST_RESPONSE.md](doc/HTTP_REQUEST_RESPONSE.md)** — Dokumentasi HTTP layer
- **[HTTP_MIDDLEWARE.md](doc/HTTP_MIDDLEWARE.md)** — Dokumentasi Middleware
- **[DATABASE_QUERYBUILDER.md](doc/DATABASE_QUERYBUILDER.md)** — Dokumentasi QueryBuilder
- **[DATABASE_CORE.md](doc/DATABASE_CORE.md)** — Dokumentasi manajemen database
- **[SUPPORT_UTILITIES.md](doc/SUPPORT_UTILITIES.md)** — Dokumentasi utility classes
- **[CACHE_SECURITY_ADVANCED.md](doc/CACHE_SECURITY_ADVANCED.md)** — Cache, security, dan topik advanced

---

## 🏗️ Arsitektur

### Struktur Direktori

```
sun/
├── app/                    # Kode aplikasi
│   ├── controllers/        # HTTP request handlers
│   ├── models/            # ORM models
│   ├── views/             # PHP templates
│   └── middleware/        # Custom middleware
├── system/                # Core framework
│   ├── Core/              # BaseModel, Controller, View, Relationship
│   ├── Http/              # Request, Response, Middleware
│   ├── database/          # Database, QueryBuilder, Migrations
│   ├── Support/           # Utility classes
│   ├── Cache/             # Caching layer
│   ├── Exceptions/        # Exception classes
│   └── Interfaces/        # Contracts
├── public/
│   └── index.php         # Web entry point
├── storage/              # Generated files
│   ├── migrations/       # Migration files
│   ├── seeders/          # Seeder files
│   ├── logs/             # Application logs
│   └── cache/            # Cache files
├── doc/                  # Dokumentasi lengkap
└── tests/                # Unit/integration tests
```

### Lifecycle Request

1. HTTP request → `public/index.php`
2. Bootstrap/Kernel initialization
3. Environment loading
4. Middleware pipeline execution
5. Reflection-based routing
6. Controller instantiation dengan DI
7. Eksekusi action method
8. Response rendering
9. Response dikirim ke client

---

## 💡 Contoh Use Cases

### REST API

```php
class ApiProductController extends Controller {
    public function getProducts() {
        $page = $this->request->get('page', 1);
        $products = (new Product_model())->paginate($page, 20);
        return $this->response->json($products);
    }

    public function createProduct() {
        $product = new Product_model($this->request->all());
        $product->save();
        return $this->response->status(201)->json(['id' => $product->id]);
    }
}
```

### Authentication dengan JWT

```php
class AuthController extends Controller {
    public function login() {
        $user = User_model::findBy('email', $this->request->post('email'));

        if (!$user || !password_verify($this->request->post('password'), $user->password)) {
            return $this->response->error(401, 'Invalid credentials');
        }

        $token = jwt_encode(['id' => $user->id, 'exp' => time() + 3600]);
        return $this->response->json(['token' => $token]);
    }
}
```

### Data Processing dengan Collections

```php
$users = (new User_model())->getResultArray();

$activeAdults = Collection::make($users)
    ->filter(fn($u) => $u['status'] === 'active' && $u['age'] >= 18)
    ->map(fn($u) => ['id' => $u['id'], 'name' => $u['name']])
    ->toArray();
```

---

## 🔒 Fitur Keamanan

✓ **CSRF Protection** — Pencegahan cross-site request forgery berbasis token  
✓ **CORS Configuration** — Configurable cross-origin resource sharing  
✓ **JWT Authentication** — Autentikasi berbasis token yang aman  
✓ **Rate Limiting** — Middleware throttle untuk proteksi API  
✓ **Input Validation** — Built-in request validation patterns  
✓ **SQL Injection Prevention** — Parameterized queries via QueryBuilder  
✓ **Password Hashing** — Bcrypt support via mutators  
✓ **XSS Prevention** — HTML escaping helpers

---

## ⚡ Performa

- **Query Caching** — Dukungan File/Redis driver
- **Response Caching** — Full page/response caching dengan TTL
- **Query Profiling** — Built-in performance monitoring
- **N+1 Prevention** — JOIN recommendations dan examples
- **Database Indexing** — Migration support untuk indexes
- **Response Compression** — Gzip/Deflate support

---

## 🛠️ Technology Stack

| Komponen             | Teknologi                 |
| -------------------- | ------------------------- |
| Language             | PHP 7.3+                  |
| Database             | MySQL, PostgreSQL, SQLite |
| ORM                  | Custom BaseModel          |
| Query Builder        | Fluent API                |
| Cache                | File, Redis               |
| Authentication       | JWT (Firebase/JWT)        |
| CLI                  | Built-in command handler  |
| Dependency Injection | Reflection-based          |
| Logging              | File-based                |
| Package Manager      | Composer                  |

---

## 📋 Persyaratan

- PHP >= 7.3
- PDO extension untuk database support
- Composer untuk dependency management
- Web server (Apache, Nginx, dll)

---

## 📄 Lisensi

Proyek ini dilisensikan di bawah MIT License - lihat file LICENSE untuk detail.

---

## 👤 Tentang

**Pemilik Proyek:** Sun Solutions Corporation  
**Lead Developer:** [Nama Anda]  
**Kontak:** [Email/Informasi Kontak Anda]  
**Website:** [URL Website Anda]

---

## 🤝 Kontribusi

Kontribusi sangat kami sambut! Silakan submit Pull Request.

1. Fork repository
2. Buat feature branch Anda (`git checkout -b feature/amazing-feature`)
3. Commit perubahan Anda (`git commit -m 'Add amazing feature'`)
4. Push ke branch (`git push origin feature/amazing-feature`)
5. Buka Pull Request

---

## 📞 Dukungan

Untuk issues, pertanyaan, atau saran:

- **GitHub Issues:** [Report an issue](https://github.com/sunsolutionscorporate/sun/issues)
- **Dokumentasi:** [Baca dokumentasi lengkap](doc/INDEX.md)
- **Email:** [Email Dukungan Anda]

---

## 🙏 Ucapan Terima Kasih

Dibangun dengan inspirasi dari framework PHP modern seperti Laravel, dengan fokus pada kesederhanaan, performa, dan kontrol.

---

**Dibuat dengan ❤️ oleh Sun Solutions Corporation**
