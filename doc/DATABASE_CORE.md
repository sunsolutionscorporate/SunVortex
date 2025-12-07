# SunVortex — Database Core & Connections Dokumentasi Lengkap

**File:** `system/database/Database.php` (622 baris)

Database adalah singleton manager yang mengelola koneksi database, QueryBuilder, transactions, profiling, dan caching.

---

## Daftar Isi

1. [Singleton Pattern](#singleton-pattern)
2. [Konfigurasi Database](#konfigurasi-database)
3. [Multiple Connections](#multiple-connections)
4. [Raw Queries](#raw-queries)
5. [Transactions](#transactions)
6. [Profiling](#profiling)
7. [Query Caching](#query-caching)
8. [Error Handling](#error-handling)
9. [Best Practices](#best-practices)

---

## Singleton Pattern

### Initialize Database

```php
use System\database\Database;

// Get singleton instance (connects to default database)
$db = Database::init();

// Subsequent calls return same instance
$db2 = Database::init();  // Same object as $db
```

### Connection Lazy Loading

```php
// Connection hanya dibuat saat pertama kali diakses
$db = Database::init();  // No connection yet

// Koneksi dibuat saat query pertama
$result = $db->table('products')->get();  // Connection now established
```

---

## Konfigurasi Database

### Environment Configuration

```php
// .env file
DB_CONFIG={
  "default": "mysql",
  "connections": {
    "mysql": {
      "driver": "mysql",
      "host": "localhost",
      "port": "3306",
      "database": "sundb",
      "user": "root",
      "password": ""
    },
    "sqlite": {
      "driver": "sqlite",
      "path": "/storage/database.sqlite"
    },
    "pgsql": {
      "driver": "pgsql",
      "host": "localhost",
      "port": "5432",
      "database": "sundb",
      "user": "postgres",
      "password": "secret"
    }
  }
}
```

### Multi-Database Setup

```php
// .env dengan multiple databases
DB_CONFIG={
  "default": "mysql",
  "connections": {
    "mysql": {
      "driver": "mysql",
      "host": "localhost",
      "database": "main_db"
    },
    "replica": {
      "driver": "mysql",
      "host": "replica.example.com",
      "database": "main_db"
    },
    "analytics": {
      "driver": "mysql",
      "host": "analytics.example.com",
      "database": "analytics_db"
    }
  }
}
```

### Connection Requirements

**MySQL/MariaDB:**

- driver: mysql
- host: Database host (default: localhost)
- port: Database port (default: 3306)
- database: Database name
- user: Username
- password: Password
- charset: Character set (default: utf8mb4)

**PostgreSQL:**

- driver: pgsql
- host: Database host
- port: Database port (default: 5432)
- database: Database name
- user: Username
- password: Password

**SQLite:**

- driver: sqlite
- path: Absolute path to .sqlite file

---

## Multiple Connections

### Switch Connections

```php
// Use default connection
$db = Database::init();
$products = $db->table('products')->get();

// Switch to different connection
$db->connectTo('replica');
$products = $db->table('products')->get();  // From replica

// Switch to analytics
$db->connectTo('analytics');
$analytics = $db->table('user_analytics')->get();

// Switch back to default
$db->connectTo('mysql');
```

### Get Specific Connection

```php
// Get PDO connection object directly
$pdo = Database::init()->getConnection('replica');

// Raw PDO operations
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([1]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

// Current active connection
$pdo = Database::init()->getConnection();
```

### Initialize Specific Connection

```php
// Initialize directly with connection name
$db = Database::init('analytics');

// Now all queries use analytics connection
$results = $db->table('events')->get();
```

---

## Raw Queries

### Query Execution

```php
// SELECT query
$result = Database::init()->query(
    "SELECT * FROM products WHERE status = ? AND price > ?",
    ['active', 100]
);
// Returns: QueryResult object

// Get array
$array = $result->getResultArray();

// Get single row
$first = $result->fetch();

// Get specific row
$row = $result->getRow(0);
```

### SELECT Shortcuts

```php
// SELECT single row
$product = Database::init()->selectOne(
    "SELECT * FROM products WHERE id = ?",
    [1]
);
// Returns: single row array or null

// SELECT all rows
$products = Database::init()->select(
    "SELECT * FROM products WHERE status = ?",
    ['active']
);
// Returns: QueryResult object
```

### INSERT/UPDATE/DELETE

```php
// INSERT
$id = Database::init()->insert(
    "INSERT INTO products (name, price) VALUES (?, ?)",
    ['Laptop', 10000000]
);
// Returns: last_insert_id

// UPDATE
$affected = Database::init()->update(
    "UPDATE products SET price = ? WHERE id = ?",
    [11000000, 1]
);
// Returns: number of affected rows

// DELETE
$affected = Database::init()->delete(
    "DELETE FROM products WHERE id = ?",
    [1]
);
// Returns: number of deleted rows

// Generic statement execution
Database::init()->statement(
    "ALTER TABLE products ADD COLUMN description TEXT"
);
```

### Parameter Binding

```php
// Positional parameters
Database::init()->query(
    "SELECT * FROM products WHERE category_id = ? AND price > ? ORDER BY name LIMIT ?",
    [5, 100, 10]
);

// Named parameters (not standard in this implementation)
// Use positional or raw strings for named params

// Escaping
$keyword = "O'Reilly";  // Contains apostrophe
Database::init()->query(
    "SELECT * FROM books WHERE title LIKE ?",
    ["%{$keyword}%"]  // Will be properly escaped
);
```

---

## Transactions

### Basic Transactions

```php
// Start transaction
Database::init()->beginTransaction();

try {
    // Execute multiple operations
    Database::init()->table('accounts')
        ->where('id', 1)
        ->update(['balance' => 900]);  // Deduct 100

    Database::init()->table('accounts')
        ->where('id', 2)
        ->update(['balance' => 1100]);  // Add 100

    // Commit if all successful
    Database::init()->commit();

} catch (Exception $e) {
    // Rollback on error
    Database::init()->rollBack();
    echo "Transaction failed: " . $e->getMessage();
}
```

### Nested Transactions (Savepoints)

```php
// Some databases support savepoints
Database::init()->beginTransaction();

try {
    // Step 1
    Database::init()->table('orders')->insert([
        'user_id' => 1,
        'total' => 1000000
    ]);

    // Step 2
    Database::init()->table('order_items')->insertBatch([
        ['order_id' => 1, 'product_id' => 1, 'qty' => 2],
        ['order_id' => 1, 'product_id' => 2, 'qty' => 1]
    ]);

    // Step 3
    Database::init()->table('inventory')
        ->where('id', 1)
        ->decrement('stock', 2);

    Database::init()->commit();
} catch (Exception $e) {
    Database::init()->rollBack();
    // Both order and items are rolled back together
}
```

### Transaction Isolation

```php
// Set isolation level (if supported by driver)
Database::init()->setTransactionIsolation('READ_COMMITTED');
// Options: READ_UNCOMMITTED, READ_COMMITTED, REPEATABLE_READ, SERIALIZABLE

Database::init()->beginTransaction();
// ... operations with specific isolation level ...
Database::init()->commit();
```

---

## Profiling

### Enable Profiling

```php
// Enable query profiling
Database::init()->enableProfiler();

// Run queries
Database::init()->table('products')->get();
Database::init()->table('categories')->get();
Database::init()->table('users')->where('status', 'active')->get();

// Get profiling data
$profiler = Database::init()->getProfiler();
$profiles = $profiler->getProfiles();

// Each profile:
// [
//     'query' => 'SELECT * FROM products',
//     'time' => 0.0234,  // milliseconds
//     'timestamp' => 1702050000
// ]
```

### Analyze Performance

```php
$profiler = Database::init()->getProfiler();
$profiles = $profiler->getProfiles();

// Find slowest queries
usort($profiles, function($a, $b) {
    return $b['time'] <=> $a['time'];
});

echo "Slowest 5 queries:\n";
foreach (array_slice($profiles, 0, 5) as $profile) {
    echo "  " . round($profile['time'], 4) . "ms: " . $profile['query'] . "\n";
}

// Total time
$totalTime = array_sum(array_column($profiles, 'time'));
echo "Total query time: " . round($totalTime, 4) . "ms\n";
```

### Disable for Production

```php
// Only enable in development
if (env('APP_ENV') === 'development') {
    Database::init()->enableProfiler();
}

// Or disable explicitly
Database::init()->disableProfiler();

// Check if enabled
if (Database::init()->isProfilerEnabled()) {
    // Profiler is active
}
```

---

## Query Caching

### Cache Configuration

```php
// .env
CACHE_DRIVER=file          // or redis
CACHE_TTL=3600             // Default TTL
CACHE_PATH=/storage/cache  // For file driver
REDIS_HOST=localhost
REDIS_PORT=6379
REDIS_PASSWORD=
```

### Using Query Cache

```php
// Cache query for 1 hour
$products = Database::init()->table('products')
    ->where('status', 'active')
    ->cacheTtl(3600)
    ->get();

// First call: Query database, store in cache
// Subsequent calls (within 1 hour): Return cached result
// After 1 hour: Query database again, update cache

// Cache with custom key
$products = Database::init()->table('products')
    ->cacheTtl(3600, 'active_products_key')
    ->get();

// Disable cache for specific query
$products = Database::init()->table('products')
    ->noCache()
    ->get();
```

### Manual Cache Management

```php
$cache = Database::init()->getCache();

// Store value
$cache->put('my_key', 'my_value', 3600);  // TTL in seconds

// Retrieve
$value = $cache->get('my_key');

// Check existence
if ($cache->has('my_key')) {
    echo "Cached!";
}

// Remove
$cache->forget('my_key');

// Clear all
$cache->flush();
```

### Cache Invalidation Strategy

```php
// ❌ Problem: Cache becomes stale after update
$products = Database::init()->table('products')
    ->where('status', 'active')
    ->cacheTtl(3600)
    ->get();  // Cached for 1 hour

// Later, a product is updated...
// But cache still returns old data!

// ✅ Solution: Invalidate cache after update
Database::init()->table('products')
    ->where('id', 1)
    ->update(['name' => 'New Name']);

// Invalidate related cache
$cache = Database::init()->getCache();
$cache->forget('active_products_key');  // Clear specific cache

// Next query will hit database and update cache
```

---

## Error Handling

### Database Exceptions

```php
use System\Exceptions\DBException;

try {
    // Query that might fail
    Database::init()->table('products')
        ->where('id', 'invalid')
        ->get();

} catch (DBException $e) {
    // Database-specific error
    echo "Database Error: " . $e->getMessage();

    // Get query that failed
    $failedQuery = $e->getQuery();

    // Log internally
    $e->logInternally();

} catch (Exception $e) {
    // Other errors
    echo "Error: " . $e->getMessage();
}
```

### Connection Errors

```php
try {
    // Connection might fail
    $db = Database::init();
    $db->table('products')->get();

} catch (PDOException $e) {
    // Connection failed
    if ($e->getCode() == 2002) {
        echo "Cannot connect to database";
    } elseif ($e->getCode() == 1045) {
        echo "Invalid credentials";
    }

    // Fallback to replica or cache
    // ... fallback logic ...
}
```

### Validation & Error Messages

```php
class ProductController extends Controller {

    public function store() {
        try {
            $product = new Product_model($this->request->all());
            $product->save();

            return $this->response->status(201)->json($product->toArray());

        } catch (DBException $e) {
            // Log error internally (not shown to user)
            $e->logInternally();

            // Return generic error to user
            return $this->response
                ->status(500)
                ->error('Failed to create product');
        }
    }
}
```

---

## Best Practices

### Performance

```php
// ❌ N+1 Queries Problem
$products = Database::init()->table('products')->get();
foreach ($products as $product) {
    $category = Database::init()->table('categories')
        ->where('id', $product['category_id'])
        ->first();
    // Runs 1000 queries for 1000 products!
}

// ✅ Use JOIN
$products = Database::init()->table('products')
    ->select('p.*', 'c.name as category')
    ->leftJoin('categories c', 'c.id = p.category_id')
    ->get();
// Only 1 query!

// ✅ Cache frequently-accessed data
$categories = Database::init()->table('categories')
    ->cacheTtl(3600 * 24)  // Cache for 1 day
    ->get();

// ✅ Index important columns
// CREATE INDEX idx_status ON products(status);
// CREATE INDEX idx_user_id ON posts(user_id);
```

### Security

```php
// ❌ NEVER concatenate user input
$id = $_GET['id'];
Database::init()->query("SELECT * FROM products WHERE id = $id");

// ✅ Use placeholders
$id = $this->request->get('id');
Database::init()->query("SELECT * FROM products WHERE id = ?", [$id]);

// ✅ Use QueryBuilder (auto-escaped)
$id = $this->request->get('id');
Database::init()->table('products')
    ->where('id', $id)
    ->first();
```

### Reliability

```php
// ✅ Always use transactions for multi-step operations
Database::init()->beginTransaction();

try {
    // Step 1: Deduct from account A
    // Step 2: Add to account B
    // Step 3: Create transaction record

    Database::init()->commit();  // All or nothing
} catch (Exception $e) {
    Database::init()->rollBack();  // Revert all changes
}

// ✅ Handle connection failures gracefully
try {
    $products = Database::init()->table('products')->get();
} catch (Exception $e) {
    // Return cached data, or empty array
    return cache_get('products') ?? [];
}
```

### Maintenance

```php
// ✅ Regular backups
// mysqldump -u root sundb > backup_2025_12_07.sql

// ✅ Monitor slow queries
if (env('APP_ENV') === 'development') {
    Database::init()->enableProfiler();
}

// ✅ Clean up cache periodically
$cache = Database::init()->getCache();
$cache->flush();  // Clear all cache

// ✅ Archieve old records
Database::init()->table('logs')
    ->where('created_at', '<', date('Y-m-d', strtotime('-1 year')))
    ->delete();
```

---

## Complete Examples

### User Registration with Transaction

```php
class AuthController extends Controller {

    public function register() {
        $data = $this->request->all();

        // Validate
        if (empty($data['email']) || empty($data['password'])) {
            return $this->response->error(422, 'Email and password required');
        }

        Database::init()->beginTransaction();

        try {
            // Create user
            $user = new User_model([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => password_hash($data['password'], PASSWORD_BCRYPT)
            ]);
            $user->save();

            // Create default profile
            Database::init()->table('user_profiles')->insert([
                'user_id' => $user->id,
                'bio' => '',
                'avatar' => null
            ]);

            // Create welcome email record
            Database::init()->table('emails')->insert([
                'user_id' => $user->id,
                'type' => 'welcome',
                'sent_at' => date('Y-m-d H:i:s')
            ]);

            Database::init()->commit();

            return $this->response->status(201)->json([
                'user_id' => $user->id,
                'message' => 'Registration successful'
            ]);

        } catch (Exception $e) {
            Database::init()->rollBack();
            return $this->response->error(500, 'Registration failed');
        }
    }
}
```

### Analytics Query with Profiling

```php
public function getAnalytics() {
    Database::init()->enableProfiler();

    // Complex analytics query
    $data = Database::init()->table('users')
        ->select('
            YEAR(created_at) as year,
            MONTH(created_at) as month,
            COUNT(*) as new_users,
            SUM(CASE WHEN status = "active" THEN 1 ELSE 0 END) as active_users
        ')
        ->groupBy('YEAR(created_at)', 'MONTH(created_at)')
        ->orderBy('year DESC', 'month DESC')
        ->cacheTtl(3600 * 24)  // Cache for 1 day
        ->get();

    // Get performance data
    $profiler = Database::init()->getProfiler();
    $duration = array_sum(array_column($profiler->getProfiles(), 'time'));

    return $this->response->json([
        'analytics' => $data,
        'query_time_ms' => round($duration, 4)
    ]);
}
```

---

**Untuk Detail Lebih Lanjut:** Lihat `doc/API.md` dan `doc/DATABASE_QUERYBUILDER.md` untuk complete reference.
