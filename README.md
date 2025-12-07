# SunVortex Framework

A lightweight, production-ready PHP framework with built-in ORM, migrations, seeders, middleware pipeline, and fluent query builder.

![PHP Version](https://img.shields.io/badge/PHP-7.3+-blue)
![License](https://img.shields.io/badge/License-MIT-green)
![Status](https://img.shields.io/badge/Status-Production%20Ready-brightgreen)

---

## 🎯 About

**SunVortex** is a modern PHP framework designed for rapid development of scalable web applications. It combines simplicity with powerful features, including:

- **Object-Relational Mapping (ORM)** — Eloquent-like model layer with CRUD, events, and relationships
- **Database Migrations** — Version control for your database schema
- **Data Seeding** — Easy test data generation with faker support
- **Middleware Pipeline** — Built-in CORS, CSRF, Auth, throttling, and caching middleware
- **Fluent Query Builder** — Type-safe SQL building without raw SQL
- **Request/Response Abstraction** — Clean HTTP handling with automatic content negotiation
- **Built-in Caching** — File and Redis support for query and response caching
- **Reflection-based Routing** — Zero-configuration routing via method discovery
- **Dependency Injection** — Automatic constructor resolution and injection

Perfect for developers who want **Laravel-like features with minimal overhead** and maximum control over their codebase.

---

## ✨ Key Features

### 🗄️ Database Layer

- **BaseModel ORM** with fillable/guarded, timestamps, soft delete, events
- **Type Casting** — Automatic conversion (int, float, bool, array, json, date)
- **Lifecycle Events** — before/after save, create, update, delete hooks
- **Query Builder** — Fluent API for SELECT, INSERT, UPDATE, DELETE
- **Multiple Connections** — MySQL, PostgreSQL, SQLite support
- **Transactions** — ACID-compliant with savepoint support
- **Query Profiling** — Built-in performance monitoring

### 🔌 HTTP & Middleware

- **Request Object** — Input, headers, files, authentication, CORS data
- **Response Object** — HTML, JSON, XML, CSV, file downloads, compression
- **6 Built-in Middleware**
  - CORS (cross-origin resource sharing)
  - CSRF (token validation)
  - Auth (JWT authentication)
  - Throttle (rate limiting)
  - PageCache (response caching)
  - Route (URI routing)
- **Custom Middleware** — Easy creation of custom request/response processors

### 🛠️ Developer Experience

- **Migration System** — Generate, run, rollback, refresh migrations
- **Seeder System** — Populate databases with test/seed data
- **CLI Commands** — Built-in commands for common tasks
- **Error Handling** — Custom exception classes with logging
- **Support Utilities** — Collection, Pipeline, Helpers, File operations
- **Comprehensive Logging** — Application, query, and error logging

---

## 🚀 Quick Start

### Requirements

- PHP 7.3 or higher
- Composer
- MySQL, PostgreSQL, or SQLite

### Installation

```bash
# Clone repository
git clone https://github.com/sunsolutionscorporate/sun.git
cd sun

# Install dependencies
composer install

# Copy environment file
cp .env.example .env

# Configure database in .env
# DB_CONFIG={"default":"mysql","connections":{"mysql":{"driver":"mysql","host":"localhost",...}}}

# Run migrations
php sun migrate

# Run seeders (optional)
php sun seed
```

### Create Your First Model

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

### Create a Controller

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

### Create a Migration

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

## 📚 Documentation

Full documentation is available in the `doc/` directory:

- **[INDEX.md](doc/INDEX.md)** — Master index and navigation guide
- **[USAGE.md](doc/USAGE.md)** — Step-by-step setup and usage guide
- **[API.md](doc/API.md)** — Complete method signature reference
- **[EXAMPLES.md](doc/EXAMPLES.md)** — Code examples and patterns
- **[CORE_BASEMODEL.md](doc/CORE_BASEMODEL.md)** — ORM documentation
- **[CORE_CONTROLLER.md](doc/CORE_CONTROLLER.md)** — Controller documentation
- **[HTTP_REQUEST_RESPONSE.md](doc/HTTP_REQUEST_RESPONSE.md)** — HTTP layer documentation
- **[HTTP_MIDDLEWARE.md](doc/HTTP_MIDDLEWARE.md)** — Middleware documentation
- **[DATABASE_QUERYBUILDER.md](doc/DATABASE_QUERYBUILDER.md)** — QueryBuilder documentation
- **[DATABASE_CORE.md](doc/DATABASE_CORE.md)** — Database management documentation
- **[SUPPORT_UTILITIES.md](doc/SUPPORT_UTILITIES.md)** — Utility classes documentation
- **[CACHE_SECURITY_ADVANCED.md](doc/CACHE_SECURITY_ADVANCED.md)** — Cache, security, and advanced topics

---

## 🏗️ Architecture

### Directory Structure

```
sun/
├── app/                    # Application code
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
├── doc/                  # Comprehensive documentation
└── tests/                # Unit/integration tests
```

### Request Lifecycle

1. HTTP request → `public/index.php`
2. Bootstrap/Kernel initialization
3. Environment loading
4. Middleware pipeline execution
5. Reflection-based routing
6. Controller instantiation with DI
7. Action method execution
8. Response rendering
9. Response sent to client

---

## 💡 Example Use Cases

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

### Authentication with JWT

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

### Data Processing with Collections

```php
$users = (new User_model())->getResultArray();

$activeAdults = Collection::make($users)
    ->filter(fn($u) => $u['status'] === 'active' && $u['age'] >= 18)
    ->map(fn($u) => ['id' => $u['id'], 'name' => $u['name']])
    ->toArray();
```

---

## 🔒 Security Features

✓ **CSRF Protection** — Token-based cross-site request forgery prevention  
✓ **CORS Configuration** — Configurable cross-origin resource sharing  
✓ **JWT Authentication** — Secure token-based authentication  
✓ **Rate Limiting** — Throttle middleware for API protection  
✓ **Input Validation** — Built-in request validation patterns  
✓ **SQL Injection Prevention** — Parameterized queries via QueryBuilder  
✓ **Password Hashing** — Bcrypt support via mutators  
✓ **XSS Prevention** — HTML escaping helpers

---

## ⚡ Performance

- **Query Caching** — File/Redis driver support
- **Response Caching** — Full page/response caching with TTL
- **Query Profiling** — Built-in performance monitoring
- **N+1 Prevention** — JOIN recommendations and examples
- **Database Indexing** — Migration support for indexes
- **Response Compression** — Gzip/Deflate support

---

## 🛠️ Technology Stack

| Component            | Technology                |
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

## 📋 Requirements

- PHP >= 7.3
- PDO extension for database support
- Composer for dependency management
- Web server (Apache, Nginx, etc.)

---

## 📄 License

This project is licensed under the MIT License - see the LICENSE file for details.

---

## 👤 About

**Project Owner:** Sun Solutions Corporation  
**Lead Developer:** [Your Name]  
**Contact:** [Your Email/Contact Information]  
**Website:** [Your Website URL]

---

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

---

## 📞 Support

For issues, questions, or suggestions:

- **GitHub Issues:** [Report an issue](https://github.com/sunsolutionscorporate/sun/issues)
- **Documentation:** [Read the full docs](doc/INDEX.md)
- **Email:** [Your Support Email]

---

## 🙏 Acknowledgments

Built with inspiration from modern PHP frameworks like Laravel, with a focus on simplicity, performance, and control.

---

**Made with ❤️ by Sun Solutions Corporation**
