# SunVortex — Referensi API Lengkap (API.md)

Dokumen ini merupakan referensi API komprehensif untuk SunVortex, mencakup kelas inti, method publik, parameter, dan type hints. Tingkat detail dirancang untuk developer yang ingin memahami dan mengintegrasikan setiap layer framework.

**Struktur:** API disusun per layer (Core/Bootstrap, Database, ORM, Migration/Seeder, HTTP) dengan penjelasan singkat dan contoh kode nyata.

# SunVortex — Referensi API Lengkap (API.md)

Dokumen ini merupakan referensi API komprehensif untuk SunVortex, mencakup kelas inti, method publik, parameter, dan type hints. Tingkat detail dirancang untuk developer yang ingin memahami dan mengintegrasikan setiap layer framework.

**Struktur:** API disusun per layer (Core/Bootstrap, Database, ORM, Migration/Seeder, HTTP) dengan penjelasan singkat dan contoh kode nyata.

---

## 1. Autoload dan Bootstrap

### Autoload (`system/Autoload.php`)

Sistem autoloading berbasis blacklist. Secara otomatis memuat kelas dari subdirektori `system/` yang didaftarkan:

```php
Autoload::from('system/Interfaces');
Autoload::from('system/Exceptions');
Autoload::from('system/Cache');
Autoload::from('system/Core');
Autoload::from('system/Support');
Autoload::from('system/Http');
Autoload::from('system/database');
```

**Blacklist:** file dengan nama/pola `.bak`, `migration`, `Migration` dilewati.

### Bootstrap / Kernel (`system/Bootstrap.php`)

Kernel adalah jantung framework. Fitur utama:

#### Parsing `.env`

```php
// Di Kernel::insertEnv()
// Mendukung:
// - Booleans: true/false, yes/no, on/off → PHP boolean
// - Numbers: 123 → PHP int
// - JSON: {"key":"value"} → PHP array (json_decode)
// - Multiline: nilai dengan \n diparsing sebagai string lengkap
```

**Env constants yang diset otomatis:**

- `ROOT` — root path proyek
- `PATH_APP` — path folder `app/`
- `DISK_PATH` — path folder `storage/`
- `VERSIONS` — array versi library (dari composer.json)
- `LANGUAGE_DICT` — array HTTP codes dari `system/language/http_codes.json`

#### Middleware Pipeline

**Order eksekusi middleware:**

1. `cors` — CORS headers dan early response untuk OPTIONS
2. `pageCache` — cache page-level jika enable
3. `throttle` — rate limiting
4. `auth` — autentikasi (setup request->user jika ada)
5. `route` — routing (utama)
6. `csrf` — CSRF protection (skip untuk API routes)

Middleware pipeline dapat dihentikan early (contoh: CORS preflight returns 204).

#### Request Routing

```php
// Di Kernel::routeToController()
// 1. Parsing URI → ['controller' => 'ExampleCrud', 'method' => 'index', 'params' => [1,2,...]]
// 2. Resolve file: app/controllers/{Controller}.php atau app/controllers/api/{Controller}.php
// 3. Load file via require_once
// 4. Reflection: validasi method signature, tipe parameter
// 5. buildArgs: coerce scalar types (string→int, string→bool)
// 6. Invoke controller method dengan dependency injection
```

**Type coercion rules:**

- Union types: dicoba sesuai order
- Scalar `int`: integer casting
- Scalar `bool`: boolval() atau "true"/"1" → true
- Scalar `string`: no cast
- Non-scalar: error jika tipe tidak match

---

## 2. Core Layer

### Controller (`system/Core/Controller.php`)

Base class untuk semua controller. Fitur:

```php
class Controller {
    protected $request; // Injected otomatis via setAttribute

    // Magic getter untuk akses attribute
    public function __get($name) { ... }
}
```

**Methods:**

- `setAttribute(string $name, $value)` — set attribute (used internally by Kernel)
- Magic `__get()` — akses attribute via `$this->request`, dll

**Pengguna jarang call methods ini langsung; Kernel menangani injection.**

### BaseModel (`system/Core/BaseModel.php`)

ORM-style base class untuk semua model. Fitur lengkap:

#### Tabel Auto-Detection

```php
class Example_model extends BaseModel {}
// Otomatis table: 'examples' (lowercase, strip '_model'/'Model', naive pluralize)
```

#### Properti Konfigurasi

```php
protected $table = 'examples';        // Tabel DB (override auto-detection)
protected $primaryKey = 'id';         // Primary key (default 'id')
protected $fillable = [];             // Whitelist kolom mass-assignment
protected $guarded = [];              // Blacklist kolom mass-assignment
protected $casts = [                  // Type casting
    'status' => 'bool',               // 'int', 'float', 'bool', 'array', 'json', 'date'
    'settings' => 'json'
];
protected $timestamps = true;         // Auto timestamps created_at, updated_at
protected $softDelete = false;        // Soft delete (use deleted_at)
protected $createdAtColumn = 'created_at';
protected $updatedAtColumn = 'updated_at';
protected $deletedAtColumn = 'deleted_at';
```

#### Method CRUD Standar

```php
// Find by primary key
$model = $this->find(1);              // → BaseModel|null

// Find by attribute
$model = $this->findBy('email', 'user@example.com');  // → BaseModel|null

// Create baru
$model = new Example_model(['name' => 'John']);
$model->save();                       // Returns insert ID

// Update
$model->name = 'Jane';
$model->save();                       // Returns true/false

// Delete
$model->delete();                     // Soft delete jika softDelete=true

// Batch
$all = $this->db->table($this->table)->getResultArray();

// Paginate
$result = $this->paginate($page=1, $limit=10, $conditions=[]);
// Returns: ['data' => [...], 'total' => N, 'limit' => 10, 'page' => 1]
```

#### Attribute Methods

```php
// fill() — mass assign dengan whitelist/blacklist
$model->fill(['name' => 'John', 'email' => 'john@example.com']);

// isFillable() — check if column is allowed
if ($model->isFillable('name')) { ... }

// syncOriginal() — mark attributes as saved (internal use)
$model->syncOriginal();

// getAttribute/setAttribute — get/set dengan casting
$value = $model->getAttribute('status');
$model->setAttribute('status', true);

// getChangedAttributes() — array attribute yang berubah sejak save terakhir
$changes = $this->getChangedAttributes();
```

#### Events / Lifecycle

```php
// Register event handler (instance)
$model->on('before:create', function($model) {
    $model->slug = str_slug($model->name);
});

$model->on('after:update', function($model) {
    // Clear cache, log changes, dll
});

// Off/remove handler
$model->off('before:create', $callback);
$model->off('before:create');  // Clear all handlers for event

// Global events (static)
BaseModel::listen('before:save', function($model) { ... });

// Built-in events:
// - before:save, after:save, after:save:failed
// - before:create, after:create, after:create:failed
// - before:update, after:update, after:update:failed
// - before:delete, after:delete, after:delete:failed
```

#### Logging

```php
// Enable standard logging (log ke file)
$model->enableStandardLogging();
// Logs: CREATE, READ, UPDATE, DELETE events dengan attribute changes
```

#### Attribute Accessors/Mutators

```php
// Accessors (auto-called saat ambil attribute)
protected function getNameAttribute($value) {
    return ucfirst($value);
}

// Mutators (auto-called saat set attribute)
protected function setNameAttribute($value) {
    $this->attributes['name'] = strtolower($value);
}

// Usage:
$model->name = "JOHN";   // Mutator dipanggil
echo $model->name;       // "john", accessor dipanggil
```

#### Query Builder Helper

```php
// Get QueryBuilder untuk tabel ini
$query = $this->query();
$results = $query->where('status', 'active')->orderBy('created_at DESC')->getResultArray();
```

#### Relasi & Nested Data

```php
// setRelation() — attach nested data (untuk eager loading)
$model->setRelation('permissions', $permissionCollection);

// toArray() — serialize model + relations ke array bersarang
$array = $model->toArray();

// toJson() — serialize ke JSON string
$json = $model->toJson();
```

#### Transactions

```php
$model->beginTransaction();
try {
    $model->save();
    $model->commit();
} catch (Exception $e) {
    $model->rollBack();
}
```

#### Utility Methods

```php
// Check if record exists in DB
if ($model->existsInDb()) { ... }

// Reload from DB (refresh after external changes)
$model->refresh();

// updateOrCreate — find atau create+update
$model = $this->updateOrCreate(
    ['email' => 'john@example.com'],  // search criteria
    ['name' => 'John Doe', 'age' => 30]  // merge with data
);

// firstOrCreate — find atau create dengan attrs
$model = $this->firstOrCreate(
    ['email' => 'john@example.com'],  // search + set attributes
    ['name' => 'John Doe']  // additional values
);

// results() — helper untuk Response
$collection = $this->db->table($this->table)->getResultArray();
return $this->results($collection);
```

---

## 3. Database Layer

### Database Manager (`system/database/Database.php`)

**Singleton** untuk mengelola koneksi multi-database dan caching query.

#### Init & Connection

```php
// Get singleton instance
$db = Database::init();

// Connect to specific database group (by index or name)
$pdo = $db->connect(0);           // by index
$pdo = $db->connect('reporting'); // by database name

// Get active connection
$pdo = $db->getConnection($group = null);

// Switch database
$db->switchTo($group)->table('users');

// Test connection
if ($db->testConnection(0)) { echo "OK"; }
```

#### Query Execution

```php
// Raw query with parameters
$result = $db->query("SELECT * FROM users WHERE id = ?", [$id]);
// Returns QueryResult object

// Shortcut methods
$rows = $db->select("SELECT * FROM users", []);           // → array
$row = $db->selectOne("SELECT * FROM users WHERE id = ?", [$id]);  // → object|null
$affectedRows = $db->update("UPDATE users SET status=?", ['active']);
$affectedRows = $db->delete("DELETE FROM users WHERE status=?", ['inactive']);
$insertId = $db->insert("INSERT INTO users VALUES(...)", []);

// Statement execution
$bool = $db->statement("CREATE TABLE...", []);
```

#### QueryBuilder

```php
// Get QueryBuilder for table
$query = $db->table('users');  // → QueryBuilder

// Fluent API (see QueryBuilder section)
$rows = $query->where('status', 'active')->orderBy('name')->getResultArray();
```

#### Cache & Profiler

```php
// Get cache instance (if enabled)
$cache = $db->getCache();

// Get query profiler
$profiler = $db->getProfiler();  // → QueryProfiler|null

// queryWithCache() — raw query dengan optional cache
$rows = $db->queryWithCache(
    "SELECT * FROM users WHERE status = ?",
    ['active'],
    $cacheKey = 'users_active',
    $ttl = 3600
);
```

#### Transactions

```php
$db->beginTransaction();
try {
    $db->statement("INSERT INTO ...");
    $db->commit();
} catch (Exception $e) {
    $db->rollBack();
}
```

#### Utilities

```php
// Escape value
$escaped = $db->escape("O'Reilly");  // → 'O\'Reilly'

// Escape untuk LIKE
$pattern = $db->escapeLikeString("50%", '!');

// Get all config
$configs = $db->getConfig();

// Get last insert ID
$id = $db->getLastInsertId();
```

### QueryBuilder (`system/database/QueryBuilder.php`)

**Fluent query builder** dengan API mirip CodeIgniter4.

#### Basic Query

```php
$qb = $db->table('users');

// Select
$qb->select(['id', 'name', 'email']);
$qb->select('id, name, email');

// From/table
$qb->from('users');  // alias: table()

// Result
$result = $qb->get();        // → QueryResult
$rows = $qb->getResultArray();
$row = $qb->getRow();        // first row
$row = $qb->first();         // shortcut for first row
```

#### WHERE Clauses

```php
// Simple where
$qb->where('id', 5);
$qb->where('status', 'active');

// Array where
$qb->where(['status' => 'active', 'role' => 'admin']);

// Expression where (raw SQL)
$qb->where('age > 18');
$qb->where('age > ?', [18]);  // with parameters

// OR where
$qb->where('status', 'active')->orWhere('status', 'pending');

// IN
$qb->whereIn('id', [1, 2, 3]);
$qb->whereNotIn('id', [10, 11]);
$qb->orWhereIn('status', ['active', 'pending']);

// NULL checks
$qb->whereNull('deleted_at');
$qb->whereNotNull('verified_at');
$qb->orWhereNull('supervisor_id');

// LIKE
$qb->like('name', 'john', 'both');  // %john%
$qb->like('email', 'gmail', 'after');  // gmail%
$qb->orLike('phone', '08', 'before');  // %08
```

#### JOIN

```php
$qb->join('roles r', 'r.id = users.role_id', 'INNER');
$qb->leftJoin('addresses a', 'a.user_id = users.id');
$qb->rightJoin('logs l', 'l.user_id = users.id');
$qb->innerJoin('companies c', 'c.id = users.company_id');
```

#### ORDER, GROUP, HAVING

```php
$qb->orderBy('created_at DESC');
$qb->orderBy('name ASC, age DESC');

$qb->groupBy('department_id');

$qb->having('COUNT(*) > 5');
$qb->having(['total_sales' => 10000]);
```

#### LIMIT & OFFSET

```php
$qb->limit(10);
$qb->offset(20);
$qb->limit(10)->offset(20);  // Fluent chaining
```

#### Aggregations

```php
// COUNT
$total = $qb->where('status', 'active')->countAllResults();
// Note: countAllResults() resets builder state by default
// Use countAllResults(false) to preserve state

// Get SQL without execution
$sql = $qb->toSql();  // "SELECT * FROM users WHERE status = ?"
```

#### Data Manipulation

```php
// INSERT
$insertId = $qb->insert(['name' => 'John', 'email' => 'john@example.com']);

// UPDATE (requires WHERE)
$affectedRows = $qb->where('id', 5)->update(['status' => 'inactive']);

// DELETE (requires WHERE)
$affectedRows = $qb->where('id', 5)->delete();

// Safety: update/delete throw exception jika no WHERE clause
```

#### Cache Control

```php
$qb->noCache();  // Disable cache for this query
$qb->cacheTtl(3600);  // Set custom TTL

$rows = $qb->where('status', 'active')->cacheTtl(1800)->get()->getResultArray();
```

#### Builder State

```php
// Reset builder (clear select, where, joins, etc)
$qb->resetBuilder();
```

### QueryResult (`system/database/QueryResult.php`)

Result wrapper untuk SELECT queries.

```php
$result = $qb->get();

// Get all rows
$rows = $result->getResultArray();  // → array of assoc array

// Get single row
$row = $result->fetch();  // first row or null
$row = $result->getFirstRow();  // alias
$row = $result->getRow(2);  // by index

// Count
$count = $result->getNumRows();

// Row count (untuk INSERT/UPDATE/DELETE)
$affected = $result->rowCount();
```

---

## 4. Migration System

### Migration Base (`system/database/Migration/Migration.php`)

Subclass `Migration` untuk create table migrations.

```php
use System\Database\Migration\Migration;

class CreateExamplesTable extends Migration {
    public function up() {
        // Define schema via $this->schema helper
        $this->schema->create('examples', function(Blueprint $table) {
            $table->id();  // Auto-increment bigint primary key
            $table->string('name', 255);
            $table->string('email', 255)->unique();
            $table->text('description')->nullable();
            $table->timestamps();  // created_at, updated_at
            $table->softDeletes();  // deleted_at
        });
    }

    public function down() {
        $this->schema->dropIfExists('examples');
    }
}
```

### Schema & Blueprint (`Schema.php`, `Blueprint`)

Column definition builder dengan fluent API.

#### Column Types

```php
// ID columns
$table->id();                           // BIGINT AUTO_INCREMENT PRIMARY KEY
$table->uuid('id');                     // VARCHAR(36) UNIQUE

// String
$table->string('name', 255);            // VARCHAR(255)
$table->text('description');            // TEXT
$table->mediumText('content');          // MEDIUMTEXT
$table->longText('article');            // LONGTEXT
$table->char('code', 10);               // CHAR(10)

// Special strings
$table->email('email');                 // VARCHAR(255)
$table->phone('phone');                 // VARCHAR(20)
$table->slug('slug');                   // VARCHAR(255) UNIQUE
$table->password('password');           // VARCHAR(255)
$table->url('website');                 // VARCHAR(2048)

// Numeric
$table->integer('count');               // INT
$table->bigInteger('big_num');          // BIGINT
$table->smallInteger('small_num');      // SMALLINT
$table->unsignedInteger('age');         // INT UNSIGNED
$table->unsignedBigInteger('user_id');  // BIGINT UNSIGNED
$table->decimal('price', 8, 2);         // DECIMAL(8,2)
$table->float('rating');                // FLOAT
$table->double('score');                // DOUBLE

// Date/Time
$table->date('birth_date');             // DATE
$table->time('start_time');             // TIME
$table->dateTime('checkout_at');        // DATETIME
$table->timestamp('created_at');        // TIMESTAMP

// Boolean
$table->boolean('is_active');           // TINYINT(1)

// JSON
$table->json('metadata');               // JSON
$table->jsonb('config');                // JSONB (PostgreSQL) or JSON

// Enum/Set
$table->enum('status', ['active', 'inactive', 'pending']);
$table->set('permissions', ['read', 'write', 'delete']);

// Binary
$table->binary('data');                 // BINARY
$table->blob('file_content');           // BLOB

// Foreign key
$table->foreignId('user_id');           // BIGINT UNSIGNED (use with ->constrained())
```

#### Column Modifiers

```php
// All column types support:
$table->string('name')
    ->nullable()                        // Allow NULL
    ->default('N/A')                    // DEFAULT value
    ->unique()                          // UNIQUE constraint
    ->index()                           // INDEX
    ->comment('User full name')         // COMMENT
    ->collation('utf8mb4_unicode_ci')   // COLLATION
    ->charset('utf8mb4');               // CHARACTER SET

// Special modifiers
$table->timestamp('updated_at')
    ->onUpdateCurrentTimestamp();       // ON UPDATE CURRENT_TIMESTAMP

$table->string('code')
    ->storedAs("CONCAT('PRE-', id)");   // Generated/computed column

// Auto increment
$table->id()->autoIncrement();
```

#### Timestamps & Soft Deletes

```php
// Add both created_at and updated_at
$table->timestamps();

// Add soft delete column
$table->softDeletes();  // → deleted_at TIMESTAMP NULL
```

#### Index Methods

```php
// Primary key
$table->primary(['id']);

// Unique
$table->unique(['email']);
$table->unique(['user_id', 'post_id']);

// Index
$table->index(['status']);
$table->index(['created_at']);

// Fulltext
$table->fulltext(['title', 'description']);
```

#### Schema Operations

```php
$schema = new Schema($db);

// Check if table/column exists
$exists = $schema->hasTable('users');
$hasCol = $schema->hasColumn('users', 'email');

// Drop table
$schema->drop('old_table');
$schema->dropIfExists('old_table');

// Rename table
$schema->rename('old_name', 'new_name');
```

### MigrationManager (`MigrationManager.php`)

Handles migration execution dan tracking.

```php
$manager = new MigrationManager($db, DISK_PATH . '/database/migrations');

// Run pending migrations
$results = $manager->run();  // → array of ['status', 'migration', 'message']

// Rollback last batch
$results = $manager->rollback($steps = 1);

// Rollback all
$results = $manager->reset();

// Rollback & re-run
$results = $manager->refresh();

// Get pending migrations
$pending = $manager->getPendingMigrations();  // → array

// Get executed migrations
$executed = $manager->getExecutedMigrations();  // → array

// Create migration file
$filepath = MigrationManager::create('create_users_table');
// → storage/database/migrations/2025_12_07_101500_create_users_table.php
```

**Migration filename format:**

```
YYYY_MM_DD_HHMMSS_description.php
```

**Migration class name derivation:**

```
2025_12_07_101500_create_users_table.php → CreateUsersTableMigration
```

---

## 5. Seeder System

### Seeder Base (`system/database/Migration/Seeder.php`)

Base class untuk dummy data generation.

```php
class UserSeeder extends Seeder {
    public function run() {
        $this->truncate('users');

        for ($i = 0; $i < 100; $i++) {
            $this->insert('users', [
                'name' => $this->faker('name'),
                'email' => $this->faker('email'),
                'phone' => $this->faker('phone'),
                'is_active' => $this->faker('boolean'),
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }
    }
}
```

#### Methods

```php
// Truncate table (hapus semua data)
$this->truncate('users');

// Insert single row
$this->insert('users', [
    'name' => 'John',
    'email' => 'john@example.com',
    'role_id' => 1
]);

// Insert multiple rows
$this->insertBulk('users', [
    ['name' => 'John', 'email' => 'john@example.com'],
    ['name' => 'Jane', 'email' => 'jane@example.com']
]);

// Delete rows by condition
$this->delete('users', "role_id = 99");  // Custom condition
$this->delete('users', '1=1');           // All rows

// Call another seeder
$this->call('DepartmentSeeder');
$this->call('UserSeeder');
```

### FakerHelper (`FakerHelper` dalam Seeder.php)

Generate dummy data untuk testing.

```php
// Usage dalam Seeder:
$name = $this->faker('name');      // → "John Smith"
$email = $this->faker('email');    // → "john.smith@gmail.com"
$phone = $this->faker('phone');    // → "+62812345678"
$addr = $this->faker('address');   // → "123 Jl. Main, Jakarta"
$co = $this->faker('company');     // → "Acme Corp"
$text = $this->faker('text', ['length' => 100]);
$num = $this->faker('number', ['min' => 1, 'max' => 100]);
$bool = $this->faker('boolean');   // → true|false
$date = $this->faker('date');      // → "2025-01-15"
$date = $this->faker('date', ['format' => 'Y-m-d H:i:s']);
$uuid = $this->faker('uuid');      // → "550e8400-e29b-41d4-a716-446655440000"
```

---

## 6. HTTP Layer

### Request (`system/Http/Request.php`)

Singleton untuk akses input dan metadata request.

```php
$request = Request::init();

// Input access
$value = $request->get('name');      // Query parameter
$value = $request->post('email');    // POST parameter
$all = $request->all();              // Semua input
$bool = $request->has('status');     // Check if param exists

// Headers
$token = $request->header('Authorization');
$type = $request->header('Content-Type');

// Method
$method = $request->method();        // GET, POST, PUT, DELETE, etc

// URI
$uri = $request->uri();
$path = $request->path();

// User (jika autentikasi)
$user = $request->user();            // Set by auth middleware

// Server/Environment
$ip = $request->ip();
$host = $request->host();
```

### Response (`system/Http/Response.php`)

Helper untuk mengirim response dengan status dan headers.

```php
// JSON response
return Response::json(['status' => 'ok', 'data' => $data], 200);

// Redirect
return Response::redirect('/home');

// HTML/view
return Response::view('template', ['data' => $data]);

// Status code
return Response::status(404)->text('Not Found');

// Headers
return Response::header('X-Custom', 'value')->json(['status' => 'ok']);
```

### Middleware

Middleware dijalankan dalam Pipeline dalam urutan:

1. **cors** — CORS preflight & headers
2. **pageCache** — page-level caching
3. **throttle** — rate limiting
4. **auth** — authentication (set request->user)
5. **route** — URL routing
6. **csrf** — CSRF token validation (skip untuk API)

#### Implementasi Middleware Custom

```php
class CustomMiddleware implements \BaseMw {
    public function handle(&$request, &$response, $next) {
        // Pre-processing
        if (!$request->has('api_key')) {
            return $response->status(401)->json(['error' => 'Unauthorized']);
        }

        // Call next middleware
        $result = call_user_func($next, $request, $response);

        // Post-processing
        return $result;
    }
}
```

---

## Tabel Ringkasan Method Signature

| Class          | Method     | Signature                  | Return         |
| -------------- | ---------- | -------------------------- | -------------- |
| `Database`     | `init()`   | `static init()`            | `Database`     |
| `Database`     | `table()`  | `table(string $table)`     | `QueryBuilder` |
| `QueryBuilder` | `where()`  | `where($key, $value=null)` | `self`         |
| `QueryBuilder` | `get()`    | `get()`                    | `QueryResult`  |
| `QueryBuilder` | `insert()` | `insert(array $data)`      | `int`          |
| `BaseModel`    | `find()`   | `find($id)`                | `self\|null`   |
| `BaseModel`    | `save()`   | `save()`                   | `int\|bool`    |
| `BaseModel`    | `delete()` | `delete()`                 | `bool\|int`    |
| `Migration`    | `up()`     | `abstract up()`            | `void`         |
| `Migration`    | `down()`   | `abstract down()`          | `void`         |
| `Seeder`       | `run()`    | `abstract run()`           | `void`         |

---

**Catatan Akhir:** Untuk detil implementasi dan contoh lengkap, rujuk kode sumber di folder `system/` dan file contoh di `storage/database/` dan `app/`.
