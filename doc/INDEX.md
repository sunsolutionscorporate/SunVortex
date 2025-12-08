# SunVortex Framework — Dokumentasi Master Index

**Versi:** 2.0.0 | **Last Updated:** Desember 2025

Ini adalah dokumentasi komprehensif untuk SunVortex framework, PHP ORM/framework production-grade dengan migration system, seeder system, middleware pipeline, dan query builder fluent API.

---

## 🚀 Quick Start

Untuk memulai cepat, baca dalam urutan ini:

1. **Instalasi & Setup** → `doc/USAGE.md` (Bagian: Installation & Environment)
2. **Your First App** → `doc/EXAMPLES.md` (Bagian: Complete CRUD Example)
3. **API Basics** → `doc/API.md` (Method Signature Reference)

---

## 📚 Dokumentasi Lengkap

### Core Framework

#### [CORE_BASEMODEL.md](./CORE_BASEMODEL.md) — ORM Model Layer

Dokumentasi lengkap BaseModel, class dasar untuk semua ORM models.

**Topics:**

- Konfigurasi tabel (fillable, guarded, timestamps, soft delete)
- Mass assignment dan property binding
- Type casting (int, float, bool, array, json, date)
- CRUD operations (Create, Read, Update, Delete)
- Query builder integration via `$model->query()`
- Lifecycle events (before/after save/create/update/delete)
- Timestamps & soft delete management
- Attribute mutators & accessors (getter/setter)
- Relations & nested data loading
- Transactions support
- Advanced: refresh(), updateOrCreate(), firstOrCreate()

**Ideal untuk:** Developers yang ingin menggunakan ORM untuk database operations.

**Start:** [Baca CORE_BASEMODEL.md →](./CORE_BASEMODEL.md)

---

#### [CORE_CONTROLLER.md](./CORE_CONTROLLER.md) — HTTP Request Handler

Dokumentasi lengkap Controller, base class untuk semua HTTP handlers.

**Topics:**

- Controller structure & lifecycle
- Dependency injection (constructor & magic getter)
- Type hints & automatic resolution
- Method routing & parameter binding
- Type coercion (automatic int/bool conversion)
- Request object access (input, headers, user, IP)
- Response object (HTML, JSON, XML, CSV, downloads)
- Fluent/chainable API
- CRUD pattern implementation
- API controller patterns
- Form handling with middleware

**Ideal untuk:** Developers yang ingin handle HTTP requests dan generate responses.

**Start:** [Baca CORE_CONTROLLER.md →](./CORE_CONTROLLER.md)

---

### HTTP Layer

#### [HTTP_REQUEST_RESPONSE.md](./HTTP_REQUEST_RESPONSE.md) — Request & Response

Dokumentasi lengkap Request dan Response objects untuk HTTP abstraction.

**Topics:**

- Request singleton pattern
- Input methods (get, post, all, has)
- Request metadata (method, URI, path, IP, host)
- Headers handling
- File uploads & multipart form data
- Authentication user info
- Response status codes & content types
- HTML, JSON, XML, CSV responses
- File downloads & streaming
- Error & success helpers
- Headers, cookies, caching headers
- ETag & Last-Modified
- Cache-Control directives
- Content compression

**Ideal untuk:** Developers yang perlu advanced HTTP features (file downloads, caching headers, content negotiation).

**Start:** [Baca HTTP_REQUEST_RESPONSE.md →](./HTTP_REQUEST_RESPONSE.md)

---

#### [HTTP_MIDDLEWARE.md](./HTTP_MIDDLEWARE.md) — Middleware Pipeline

Dokumentasi lengkap 6 built-in middleware dan custom middleware creation.

**Topics:**

- Middleware pipeline order & architecture
- CORS middleware (cross-origin requests)
- CSRF middleware (token validation)
- Auth middleware (JWT authentication)
- Throttle middleware (rate limiting)
- PageCache middleware (response caching)
- Route middleware (URI routing)
- Custom middleware creation (BaseMw interface)
- Conditional middleware
- Middleware configuration via .env

**Ideal untuk:** Developers yang perlu API security, rate limiting, caching, atau custom request processing.

**Start:** [Baca HTTP_MIDDLEWARE.md →](./HTTP_MIDDLEWARE.md)

---

### Database Layer

#### [DATABASE_QUERYBUILDER.md](./DATABASE_QUERYBUILDER.md) — QueryBuilder Fluent API

Dokumentasi lengkap QueryBuilder untuk SQL query building secara programmatic.

**Topics:**

- SELECT queries (columns, aliases, expressions)
- WHERE conditions (operators, IN, NULL, LIKE, BETWEEN)
- JOINs (INNER, LEFT, RIGHT, self joins)
- GROUP BY & HAVING aggregation
- ORDER BY & LIMIT/OFFSET
- INSERT, UPDATE, DELETE operations
- Bulk insert (insertBatch)
- Increment/Decrement helpers
- Query caching strategy
- Query profiling & performance analysis
- Raw SQL fallback
- Pagination
- N+1 problem prevention
- SQL injection prevention via placeholders

**Ideal untuk:** Developers yang perlu powerful database query building.

**Start:** [Baca DATABASE_QUERYBUILDER.md →](./DATABASE_QUERYBUILDER.md)

---

#### [DATABASE_CORE.md](./DATABASE_CORE.md) — Database Manager & Connections

Dokumentasi lengkap Database singleton, connections management, dan transactions.

**Topics:**

- Database singleton pattern & initialization
- Multi-database configuration
- Multiple connections (switch between databases)
- Raw query execution
- Transactions (ACID compliance)
- Transaction isolation levels
- Savepoints (nested transactions)
- Query profiling & performance monitoring
- Query result caching (file/Redis)
- Cache invalidation strategy
- Database error handling
- Connection error recovery
- Connection pooling strategies

**Ideal untuk:** Developers yang perlu multi-database support, transactions, atau advanced caching.

**Start:** [Baca DATABASE_CORE.md →](./DATABASE_CORE.md)

---

#### [MIGRATION.md](./MIGRATION.md) — Database Migrations & Seeding

Dokumentasi lengkap Database migrations untuk version control skema database, dan data seeding untuk initial data.

**Topics:**

- Migration overview & workflow
- Setup & folder structure
- Membuat migration files (create/alter/drop)
- Blueprint API (columns, indexes, constraints, foreign keys)
- CLI commands (make, run, rollback, refresh, fresh)
- Column types & modifiers
- Indexes & primary keys
- Foreign key relationships & cascade
- Rollback & revert strategies
- Seeding data with seeders
- Best practices (one change per file, immutability, testing)
- Practical examples (blog app: users, posts, comments)
- Error handling & recovery

**Ideal untuk:** Developers yang perlu mengelola database schema dengan version control, team collaboration, dan deployment automation.

**Start:** [Baca MIGRATION.md →](./MIGRATION.md)

---

### Support & Utilities

#### [SUPPORT_UTILITIES.md](./SUPPORT_UTILITIES.md) — Helper Classes & Functions

Dokumentasi lengkap Collection, Pipeline, Helpers, dan File utilities.

**Topics:**

- Collection class (array manipulation)
  - Filter, map, pluck, chunk, groupBy
  - Aggregation (count, sum, unique, sort)
  - Combination operations (merge, diff)
- Pipeline class (middleware queue execution)
  - Middleware pipeline pattern
  - Skip middleware
  - Event listeners
  - Real-world API pipeline example
- Helper functions
  - Token encode/decode
  - Byte formatting
  - String utilities (starts_with, ends_with, contains)
  - Array utilities (is_assoc, isJson)
  - File operations
  - URL & config access
  - Reflection helpers
  - Logging (slog)
- File class — [File.md](./File.md)
  - MIME type detection
  - File operations (exists, size, extension, directory)

**Ideal untuk:** Developers yang perlu data transformation, pipeline patterns, atau utility functions.

**Start:** [Baca SUPPORT_UTILITIES.md →](./SUPPORT_UTILITIES.md)

---

### Cache, Security & Advanced

#### [CACHE_SECURITY_ADVANCED.md](./CACHE_SECURITY_ADVANCED.md) — Caching, Security, Optimization

Dokumentasi lengkap cache system, security best practices, performance optimization.

**Topics:**

- Cache configuration (file/Redis driver)
- File driver operations
- Redis driver operations
- Query caching strategy
- Cache invalidation patterns
- Security best practices
  - Input validation
  - XSS prevention (HTML escaping)
  - SQL injection prevention (parameterized queries)
  - CSRF protection (token validation)
  - CORS security
  - Password hashing (bcrypt)
  - JWT authentication
  - Rate limiting
- Error handling
  - Custom exception classes
  - Error response patterns
  - Database exceptions
  - Connection error handling
- Performance optimization
  - N+1 query problem
  - Database indexing
  - Query optimization
  - Pagination
  - Cache strategies
  - Response compression
- Logging & monitoring
  - Application logging
  - Query logging
  - Error logging
  - Performance monitoring
- Advanced patterns
  - Dependency injection container
  - Repository pattern

**Ideal untuk:** Developers yang perlu production-grade security, caching, monitoring, dan optimization.

**Start:** [Baca CACHE_SECURITY_ADVANCED.md →](./CACHE_SECURITY_ADVANCED.md)

---

## 📖 Usage & Examples

### [USAGE.md](./USAGE.md) — Step-by-Step Guide

Panduan praktis dari installation hingga deployment.

**Contents:**

- Installation & environment setup
- .env configuration
- Database connections
- Project structure
- Creating models & controllers
- Creating views
- Database migrations
- Data seeding
- Running the application
- Deployment considerations
- Troubleshooting

**Read this when:** You need step-by-step instructions for common tasks.

---

### [EXAMPLES.md](./EXAMPLES.md) — Code Examples & Patterns

Koleksi lengkap code examples untuk berbagai use cases.

**Contents:**

- Complete CRUD implementation (products)
- RESTful API implementation
- Advanced queries & relationships
- Input validation patterns
- Event handling
- Caching patterns
- Pagination
- File uploads
- Testing patterns

**Read this when:** You need concrete code examples to learn from.

---

### [API.md](./API.md) — Method Signature Reference

Referensi lengkap semua method signatures dan quick-reference guide.

**Contents:**

- Bootstrap/Kernel methods
- Controller methods
- BaseModel methods
- Request methods
- Response methods
- Middleware methods
- Database methods
- QueryBuilder methods
- Migration/Seeder methods
- Collection methods
- Cache methods

**Read this when:** You need to quickly check method signatures or available methods.

---

## 🏗️ Architecture & Patterns

### Directory Structure

```
sun/
├── app/
│   ├── controllers/        # HTTP request handlers
│   │   ├── ExampleCrud.php
│   │   └── api/           # API endpoints
│   ├── models/            # Database models (extend BaseModel)
│   │   ├── example/
│   │   └── resident/
│   ├── views/             # PHP templates
│   │   ├── example/
│   │   └── resident/
│   └── middleware/        # Custom middleware
├── system/                # Core framework (don't modify)
│   ├── Autoload.php       # Class autoloader
│   ├── Bootstrap.php      # Kernel & DI container
│   ├── index.php          # Entry point
│   ├── Core/              # Core classes
│   │   ├── BaseModel.php  # ORM base
│   │   ├── Controller.php # Request handler base
│   │   ├── View.php       # Template renderer
│   │   ├── Relationship.php  # Eager loading
│   │   └── Results.php    # Pagination wrapper
│   ├── Http/              # HTTP abstraction
│   │   ├── Request.php
│   │   ├── Response.php
│   │   └── Middleware/    # 6 built-in middleware
│   ├── database/          # Database layer
│   │   ├── Database.php
│   │   ├── QueryBuilder.php
│   │   ├── QueryResult.php
│   │   ├── QueryManager.php
│   │   └── Migration/     # Migration & seeder system
│   ├── Support/           # Utility classes
│   │   ├── Collection.php
│   │   ├── Pipeline.php
│   │   ├── Helpers.php
│   │   └── File.php
│   ├── Cache/             # Caching layer
│   ├── Exceptions/        # Exception classes
│   └── Interfaces/        # Contracts
├── public/
│   └── index.php         # Web entry point
├── storage/
│   ├── database/         # Generated files
│   │   ├── migrations/
│   │   └── seeders/
│   ├── logs/            # Application logs
│   └── cache/           # Cache files
├── doc/                 # Documentation
│   ├── USAGE.md
│   ├── API.md
│   ├── EXAMPLES.md
│   ├── CORE_BASEMODEL.md
│   ├── CORE_CONTROLLER.md
│   ├── HTTP_REQUEST_RESPONSE.md
│   ├── HTTP_MIDDLEWARE.md
│   ├── DATABASE_QUERYBUILDER.md
│   ├── DATABASE_CORE.md
│   ├── SUPPORT_UTILITIES.md
│   └── CACHE_SECURITY_ADVANCED.md
├── .env                 # Environment variables
└── composer.json        # Composer dependencies
```

### Request Lifecycle

```
1. Browser request → public/index.php (web entry point)
           ↓
2. System/Bootstrap.php → Kernel instantiation
           ↓
3. Environment loading (.env parsing)
           ↓
4. Middleware Pipeline
   - CORS middleware
   - PageCache middleware
   - Throttle middleware
   - Auth middleware
   - Route middleware (routing)
   - CSRF middleware
           ↓
5. Reflection-based controller discovery
   - Parse URI path
   - Extract controller name
   - Extract method name
   - Extract parameters
           ↓
6. Controller instantiation (with DI resolution)
   - Auto-resolve constructor dependencies
   - Inject Request, Response, Database, etc
           ↓
7. Method execution
   - Type coercion for parameters
   - Call action method with resolved params
           ↓
8. Response generation & return
   - Controller returns Response object
   - Response rendered (HTML, JSON, etc)
   - Sent to client
```

---

## 🔗 Navigation by Task

### "I want to..."

**...create a new model**

- Read: [CORE_BASEMODEL.md](./CORE_BASEMODEL.md) → Konfigurasi Tabel
- Read: [USAGE.md](./USAGE.md) → Creating Models
- Example: [EXAMPLES.md](./EXAMPLES.md) → Complete CRUD

**...handle HTTP requests**

- Read: [CORE_CONTROLLER.md](./CORE_CONTROLLER.md)
- Read: [HTTP_REQUEST_RESPONSE.md](./HTTP_REQUEST_RESPONSE.md)
- Example: [EXAMPLES.md](./EXAMPLES.md) → CRUD Implementation

**...find files by name or content**

- Read: [File.md](./File.md) → Advanced Options & Examples (cara mencari file yang mengandung teks/regex, mis. 'tes\_')

**...query the database**

- Read: [DATABASE_QUERYBUILDER.md](./DATABASE_QUERYBUILDER.md)
- Read: [CORE_BASEMODEL.md](./CORE_BASEMODEL.md) → Query Builder Integration
- Example: [EXAMPLES.md](./EXAMPLES.md) → Advanced Queries

**...create an API endpoint**

- Read: [CORE_CONTROLLER.md](./CORE_CONTROLLER.md)
- Read: [HTTP_REQUEST_RESPONSE.md](./HTTP_REQUEST_RESPONSE.md)
- Read: [HTTP_MIDDLEWARE.md](./HTTP_MIDDLEWARE.md)
- Example: [EXAMPLES.md](./EXAMPLES.md) → RESTful API

**...authenticate users**

- Read: [HTTP_MIDDLEWARE.md](./HTTP_MIDDLEWARE.md) → Auth Middleware
- Read: [CACHE_SECURITY_ADVANCED.md](./CACHE_SECURITY_ADVANCED.md) → Authentication with JWT
- Example: [EXAMPLES.md](./EXAMPLES.md) → Authentication

**...cache data**

- Read: [CACHE_SECURITY_ADVANCED.md](./CACHE_SECURITY_ADVANCED.md) → Cache System
- Example: [EXAMPLES.md](./EXAMPLES.md) → Caching Patterns

**...migrate database**

- Read: [MIGRATION.md](./MIGRATION.md) → Overview & Setup
- Read: [MIGRATION.md](./MIGRATION.md) → CLI Commands & API Blueprint
- Example: [MIGRATION.md](./MIGRATION.md) → Contoh Praktis (Blog App)

**...seed data**

- Read: [MIGRATION.md](./MIGRATION.md) → Seeding Data
- Example: [MIGRATION.md](./MIGRATION.md) → Contoh Seeder

**...deploy to production**

- Read: [USAGE.md](./USAGE.md) → Deployment Considerations
- Read: [CACHE_SECURITY_ADVANCED.md](./CACHE_SECURITY_ADVANCED.md) → Security Best Practices

**...optimize performance**

- Read: [CACHE_SECURITY_ADVANCED.md](./CACHE_SECURITY_ADVANCED.md) → Performance Optimization
- Read: [DATABASE_QUERYBUILDER.md](./DATABASE_QUERYBUILDER.md) → Best Practices

---

## 💡 Best Practices Summary

### Code Organization

- Keep business logic in models, not controllers
- Use repositories for data access patterns
- Leverage events for model lifecycle handling
- Use middleware for cross-cutting concerns

### Database

- Use QueryBuilder for SQL safety (prevents injection)
- Always select specific columns (not SELECT \*)
- Use JOINs instead of N+1 queries
- Index frequently queried columns
- Use pagination for large result sets
- Cache expensive queries

### Security

- Always validate input before processing
- Use placeholders for raw queries
- HTML-escape output (XSS prevention)
- Hash passwords with bcrypt
- Validate CSRF tokens
- Validate JWT expiration
- Rate limit API endpoints
- Use HTTPS in production

### Performance

- Cache responses & query results
- Use query profiling in development
- Enable compression for large responses
- Chunk data processing for large datasets
- Monitor slow queries
- Archive old records periodically

### Testing

- Test models independently
- Test controller action separately
- Test middleware logic
- Use sample data for consistent testing
- Test security features (CSRF, XSS, etc)

---

## 🤝 Contributing

Dokumentasi ini adalah living document. Jika anda menemukan:

- Errors atau typos
- Sections yang kurang jelas
- Missing examples
- Outdated information

Silakan report atau contribute improvements!

---

## 📞 Support & Resources

- **Framework Source:** `system/` directory
- **Examples:** `app/` directory & `doc/EXAMPLES.md`
- **API Reference:** `doc/API.md`
- **Troubleshooting:** `doc/USAGE.md` → Troubleshooting section

---

## 📋 Documentation File Index

| File                       | Purpose              | Size       | Audience            |
| -------------------------- | -------------------- | ---------- | ------------------- |
| USAGE.md                   | Step-by-step guide   | ~500 lines | Beginners           |
| API.md                     | Method signatures    | ~400 lines | All developers      |
| EXAMPLES.md                | Code samples         | ~600 lines | Learners            |
| CORE_BASEMODEL.md          | ORM documentation    | ~800 lines | Backend developers  |
| CORE_CONTROLLER.md         | Request handlers     | ~400 lines | Web developers      |
| HTTP_REQUEST_RESPONSE.md   | HTTP abstraction     | ~600 lines | Web developers      |
| HTTP_MIDDLEWARE.md         | Middleware pipeline  | ~700 lines | Advanced users      |
| DATABASE_QUERYBUILDER.md   | Query building       | ~800 lines | Database developers |
| DATABASE_CORE.md           | Database management  | ~600 lines | Advanced users      |
| MIGRATION.md               | Migrations & seeding | ~700 lines | All developers      |
| SUPPORT_UTILITIES.md       | Helper classes       | ~500 lines | All developers      |
| CACHE_SECURITY_ADVANCED.md | Cache & security     | ~900 lines | Advanced users      |

**Total Documentation:** 7,900+ lines of comprehensive, production-ready documentation.

---

## 🎯 Version Info

- **Framework:** SunVortex v2.0
- **PHP:** 7.3+
- **Database:** MySQL, PostgreSQL, SQLite
- **Documentation Version:** 2.0.0
- **Last Updated:** Desember 2025

---

**Start Reading:** [USAGE.md](./USAGE.md) for getting started, or jump to specific topics via the navigation links above.
