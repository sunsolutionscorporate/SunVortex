# SunVortex — QueryBuilder Dokumentasi Lengkap

**File:** `system/database/QueryBuilder.php` (783 baris)

QueryBuilder menyediakan fluent API untuk membangun SQL queries secara programmatic tanpa perlu menulis raw SQL.

---

## Daftar Isi

1. [Dasar Konsep](#dasar-konsep)
2. [SELECT Queries](#select-queries)
3. [WHERE Conditions](#where-conditions)
4. [Joins](#joins)
5. [Grouping & Aggregation](#grouping-aggregation)
6. [Ordering & Limiting](#ordering-limiting)
7. [INSERT/UPDATE/DELETE](#insert-update-delete)
8. [Caching & Performance](#caching-performance)
9. [Pagination](#pagination)
10. [Raw Queries](#raw-queries)
11. [Best Practices](#best-practices)

---

## Dasar Konsep

### Getting QueryBuilder Instance

```php
use System\database\QueryBuilder;

// Via Database singleton
$qb = Database::init()->table('products');

// Via BaseModel
$model = new Product_model();
$qb = $model->query();  // Alias to Database::init()->table($model->table)

// Manual instantiation
$qb = new QueryBuilder(Database::init(), 'products');
```

### Fluent API

```php
// Chain methods for readability
$results = Database::init()->table('products')
    ->select('id', 'name', 'price')
    ->where('status', 'active')
    ->where('price', '>', 100)
    ->orderBy('name')
    ->limit(10)
    ->get();  // Execute query
```

### Query Execution

```php
// Get results (QueryResult object)
$result = $qb->get();

// Get array
$array = $qb->getResultArray();

// Get first row
$first = $qb->first();

// Get single column value
$count = $qb->countAllResults();

// Execute without fetching
$affected = $qb->update(['name' => 'New']);
```

---

## SELECT Queries

### Basic SELECT

```php
// Select all columns
$results = Database::init()->table('products')
    ->get();  // SELECT * FROM products

// Select specific columns
$results = Database::init()->table('products')
    ->select('id', 'name', 'price')
    ->get();  // SELECT id, name, price FROM products

// Select with aliases
$results = Database::init()->table('products')
    ->select('id', 'name', 'price as product_price')
    ->get();  // SELECT id, name, price as product_price FROM products

// Select expressions
$results = Database::init()->table('products')
    ->select('id', 'name', 'LOWER(name) as name_lower')
    ->get();  // SELECT id, name, LOWER(name) as name_lower FROM products
```

### FROM Multiple Tables

```php
// Implicit (used for joins)
$results = Database::init()->table('products')
    ->select('p.id', 'p.name', 'c.name as category')
    ->leftJoin('categories c', 'c.id = p.category_id')
    ->get();

// Explicit from (used for subqueries/multiple sources)
$results = Database::init()
    ->from('products p')
    ->select('p.id', 'p.name')
    ->get();
```

### DISTINCT

```php
// Get unique values
$results = Database::init()->table('products')
    ->select('DISTINCT category_id')
    ->get();

// Or use PHP array_unique after fetch
$results = Database::init()->table('products')
    ->select('category_id')
    ->get();
$unique = array_unique(array_column($results->getResultArray(), 'category_id'));
```

---

## WHERE Conditions

### Basic WHERE

```php
// Single condition
$results = Database::init()->table('products')
    ->where('status', 'active')
    ->get();  // WHERE status = 'active'

// Multiple conditions (AND)
$results = Database::init()->table('products')
    ->where('status', 'active')
    ->where('price', '>', 100)
    ->where('stock', '>', 0)
    ->get();
// WHERE status = 'active' AND price > 100 AND stock > 0

// Operators
$qb->where('price', '>', 100);        // price > 100
$qb->where('price', '>=', 100);       // price >= 100
$qb->where('price', '<', 100);        // price < 100
$qb->where('price', '<=', 100);       // price <= 100
$qb->where('price', '!=', 100);       // price != 100
$qb->where('price', '<>', 100);       // price <> 100 (same as !=)
$qb->where('name', 'LIKE', '%laptop%');  // name LIKE '%laptop%'
```

### OR Conditions

```php
// Mix AND/OR
$results = Database::init()->table('products')
    ->where('status', 'active')
    ->where(function($qb) {
        $qb->where('price', '>', 100)
           ->orWhere('stock', '>', 50);
    })
    ->get();
// WHERE status = 'active' AND (price > 100 OR stock > 50)

// Simple OR (less common)
$results = Database::init()->table('products')
    ->where('status', 'active')
    ->orWhere('featured', 1)
    ->get();
// WHERE status = 'active' OR featured = 1
```

### IN & NOT IN

```php
// IN clause
$results = Database::init()->table('products')
    ->whereIn('category_id', [1, 2, 3])
    ->get();
// WHERE category_id IN (1, 2, 3)

// NOT IN
$results = Database::init()->table('products')
    ->whereNotIn('category_id', [1, 2, 3])
    ->get();
// WHERE category_id NOT IN (1, 2, 3)

// With array
$ids = [1, 2, 3];
$results = Database::init()->table('products')
    ->whereIn('id', $ids)
    ->get();
```

### NULL Checks

```php
// IS NULL
$results = Database::init()->table('products')
    ->whereNull('deleted_at')
    ->get();
// WHERE deleted_at IS NULL

// IS NOT NULL
$results = Database::init()->table('products')
    ->whereNotNull('deleted_at')
    ->get();
// WHERE deleted_at IS NOT NULL
```

### LIKE Search

```php
// Simple LIKE
$results = Database::init()->table('products')
    ->like('name', 'laptop')
    ->get();
// WHERE name LIKE '%laptop%'

// Different positions
$qb->like('name', 'laptop', 'before');   // '%laptop'
$qb->like('name', 'laptop', 'after');    // 'laptop%'
$qb->like('name', 'laptop', 'both');     // '%laptop%' (default)

// NOT LIKE
$results = Database::init()->table('products')
    ->notLike('name', 'cheap')
    ->get();
// WHERE name NOT LIKE '%cheap%'
```

### BETWEEN

```php
// BETWEEN range
$results = Database::init()->table('products')
    ->whereBetween('price', [100, 1000])
    ->get();
// WHERE price BETWEEN 100 AND 1000

// NOT BETWEEN
$results = Database::init()->table('products')
    ->whereNotBetween('price', [100, 1000])
    ->get();
// WHERE price NOT BETWEEN 100 AND 1000
```

---

## Joins

### INNER JOIN

```php
// Join related table
$results = Database::init()->table('products')
    ->select('p.id', 'p.name', 'p.price', 'c.name as category')
    ->innerJoin('categories c', 'c.id = p.category_id')
    ->get();
// SELECT p.id, p.name, p.price, c.name as category
// FROM products p
// INNER JOIN categories c ON c.id = p.category_id

// Multiple joins
$results = Database::init()->table('products')
    ->select('p.id', 'p.name', 'c.name as category', 'b.name as brand')
    ->innerJoin('categories c', 'c.id = p.category_id')
    ->innerJoin('brands b', 'b.id = p.brand_id')
    ->get();
```

### LEFT/RIGHT JOIN

```php
// LEFT JOIN (include all from left table)
$results = Database::init()->table('users')
    ->select('u.id', 'u.name', 'COUNT(p.id) as post_count')
    ->leftJoin('posts p', 'p.user_id = u.id')
    ->groupBy('u.id')
    ->get();
// SELECT u.id, u.name, COUNT(p.id) as post_count
// FROM users u
// LEFT JOIN posts p ON p.user_id = u.id
// GROUP BY u.id

// RIGHT JOIN
$results = Database::init()->table('products')
    ->rightJoin('categories c', 'c.id = p.category_id')
    ->get();
// RIGHT JOIN categories c ON c.id = p.category_id
```

### FULL OUTER JOIN

```php
// FULL OUTER JOIN (MySQL: UNION of LEFT + RIGHT)
$left = Database::init()->table('users')
    ->leftJoin('products p', 'p.user_id = u.id');

$right = Database::init()->table('products')
    ->rightJoin('users u', 'u.id = p.user_id');

// Combine with UNION (workaround for FULL OUTER)
// This requires raw SQL or manual union
```

### Self Join

```php
// Join table to itself (e.g., manager-employee relation)
$results = Database::init()->table('employees e')
    ->select('e.id', 'e.name', 'm.name as manager_name')
    ->leftJoin('employees m', 'm.id = e.manager_id')
    ->get();
// SELECT e.id, e.name, m.name as manager_name
// FROM employees e
// LEFT JOIN employees m ON m.id = e.manager_id
```

---

## Grouping & Aggregation

### GROUP BY

```php
// Group results
$results = Database::init()->table('products')
    ->select('category_id', 'COUNT(*) as total', 'AVG(price) as avg_price')
    ->groupBy('category_id')
    ->get();
// SELECT category_id, COUNT(*) as total, AVG(price) as avg_price
// FROM products
// GROUP BY category_id

// Multiple grouping
$results = Database::init()->table('sales')
    ->select('year', 'month', 'SUM(amount) as total')
    ->groupBy('year', 'month')
    ->get();
// GROUP BY year, month
```

### HAVING

```php
// Filter groups
$results = Database::init()->table('products')
    ->select('category_id', 'COUNT(*) as total')
    ->groupBy('category_id')
    ->having('COUNT(*)', '>', 5)
    ->get();
// HAVING COUNT(*) > 5

// Multiple having conditions
$results = Database::init()->table('products')
    ->select('category_id', 'AVG(price) as avg_price')
    ->groupBy('category_id')
    ->having('AVG(price)', '>', 1000)
    ->having('COUNT(*)', '>=', 10)
    ->get();
```

### Aggregate Functions

```php
// Count
$count = Database::init()->table('products')
    ->countAllResults();  // SELECT COUNT(*) FROM products

// Count with WHERE
$count = Database::init()->table('products')
    ->where('status', 'active')
    ->countAllResults(false);  // Don't reset query
// Returns: integer

// Sum
$total = Database::init()->table('sales')
    ->select('SUM(amount) as total')
    ->first();  // SELECT SUM(amount) as total FROM sales

// Average
$avg = Database::init()->table('products')
    ->select('AVG(price) as avg_price')
    ->first();

// Min/Max
$min = Database::init()->table('products')
    ->select('MIN(price) as min_price, MAX(price) as max_price')
    ->first();
```

---

## Ordering & Limiting

### ORDER BY

```php
// Sort ascending (default)
$results = Database::init()->table('products')
    ->orderBy('name')
    ->get();
// ORDER BY name ASC

// Sort descending
$results = Database::init()->table('products')
    ->orderBy('price', 'DESC')
    ->get();
// ORDER BY price DESC

// Multiple columns
$results = Database::init()->table('products')
    ->orderBy('category_id', 'ASC')
    ->orderBy('price', 'DESC')
    ->get();
// ORDER BY category_id ASC, price DESC

// Raw ordering
$results = Database::init()->table('products')
    ->orderByRaw('FIELD(status, "featured", "active", "inactive")')
    ->get();
```

### LIMIT & OFFSET

```php
// Limit
$results = Database::init()->table('products')
    ->limit(10)
    ->get();
// LIMIT 10

// Limit with offset
$results = Database::init()->table('products')
    ->limit(10)
    ->offset(20)
    ->get();
// LIMIT 10 OFFSET 20

// Shorthand
$results = Database::init()->table('products')
    ->limit(10, 20)  // limit(limit, offset)
    ->get();
```

---

## INSERT/UPDATE/DELETE

### INSERT

```php
// Single insert
$id = Database::init()->table('products')
    ->insert([
        'name' => 'Laptop',
        'price' => 10000000,
        'stock' => 5,
        'created_at' => date('Y-m-d H:i:s')
    ]);
// Returns: insert ID (last_insert_id)

// Bulk insert
$data = [
    ['name' => 'Laptop', 'price' => 10000000],
    ['name' => 'Mouse', 'price' => 200000],
    ['name' => 'Keyboard', 'price' => 500000]
];

Database::init()->table('products')->insertBatch($data);
// INSERT multiple rows in one query
```

### UPDATE

```php
// Update with WHERE
$affected = Database::init()->table('products')
    ->where('id', 1)
    ->update([
        'name' => 'Updated Name',
        'price' => 11000000
    ]);
// Returns: number of affected rows

// Multiple conditions
$affected = Database::init()->table('products')
    ->where('status', 'active')
    ->where('price', '>', 100)
    ->update(['status' => 'featured']);

// Increment/Decrement
Database::init()->table('products')
    ->where('id', 1)
    ->increment('stock', 5);  // stock = stock + 5

Database::init()->table('products')
    ->where('id', 1)
    ->decrement('stock', 2);  // stock = stock - 2
```

### DELETE

```php
// Delete with WHERE (required)
$affected = Database::init()->table('products')
    ->where('id', 1)
    ->delete();
// Returns: number of deleted rows

// Multiple conditions
$affected = Database::init()->table('products')
    ->where('status', 'inactive')
    ->where('created_at', '<', date('Y-m-d', strtotime('-1 year')))
    ->delete();

// Truncate (remove all rows, reset ID)
Database::init()->table('products')->truncate();
// WARNING: Cannot be rolled back!
```

---

## Caching & Performance

### Query Caching

```php
// Cache query results for 1 hour
$results = Database::init()->table('products')
    ->where('status', 'active')
    ->cacheTtl(3600)
    ->get();
// First call: Query database, cache result
// Subsequent calls (within 1 hour): Return from cache

// Cache with custom key
$results = Database::init()->table('products')
    ->cacheTtl(3600, 'active_products')
    ->get();

// Disable cache for specific query
$results = Database::init()->table('products')
    ->noCache()
    ->get();
// Always query database, ignore cache

// Cache file/Redis based on CACHE_DRIVER env
// .env: CACHE_DRIVER=file or redis
```

### Profiling

```php
// Enable query profiling
Database::init()->enableProfiler();

// Run queries
$result = Database::init()->table('products')->get();

// Get profiling data
$profiles = Database::init()->getProfiler()->getProfiles();
// [
//     ['query' => 'SELECT * FROM products', 'time' => 0.0234],
//     ['query' => 'SELECT COUNT(*) FROM products', 'time' => 0.0012],
// ]

// Disable for production
Database::init()->disableProfiler();
```

### Query Efficiency Tips

```php
// ❌ N+1 Problem
$products = Database::init()->table('products')->get();
foreach ($products as $product) {
    $category = Database::init()->table('categories')
        ->where('id', $product['category_id'])
        ->first();
    // Runs 1000+ queries for 1000 products!
}

// ✅ Use JOIN instead
$products = Database::init()->table('products')
    ->select('p.*', 'c.name as category')
    ->leftJoin('categories c', 'c.id = p.category_id')
    ->get();
// Only 1 query

// ✅ Select only needed columns
$products = Database::init()->table('products')
    ->select('id', 'name', 'price')  // Skip unnecessary columns
    ->get();
```

---

## Pagination

### Paginate Method

```php
// Paginate with default limit (10)
$page = $this->request->get('page', 1);
$results = Database::init()->table('products')
    ->where('status', 'active')
    ->paginate($page);

// Returns:
// [
//     'data' => [...],           // Array of records
//     'total' => 150,            // Total records
//     'limit' => 10,             // Records per page
//     'page' => 1,               // Current page
//     'pages' => 15,             // Total pages
//     'offset' => 0              // Database offset
// ]

// Custom limit
$results = Database::init()->table('products')
    ->paginate($page, 20);  // 20 per page

// With ordering
$results = Database::init()->table('products')
    ->orderBy('name')
    ->where('status', 'active')
    ->paginate($page, 15);
```

### Usage in Controller

```php
public function index() {
    $page = $this->request->get('page', 1);

    $products = Database::init()->table('products')
        ->where('status', 'active')
        ->orderBy('name')
        ->paginate($page, 10);

    return $this->response->json([
        'products' => $products['data'],
        'pagination' => [
            'current' => $products['page'],
            'total' => $products['pages'],
            'limit' => $products['limit']
        ]
    ]);
}
```

---

## Raw Queries

### Raw SQL

```php
// When QueryBuilder is insufficient
$results = Database::init()->query(
    "SELECT p.*, c.name as category FROM products p
     LEFT JOIN categories c ON c.id = p.category_id
     WHERE p.status = ? AND p.price > ?",
    ['active', 100]
);
// Returns: QueryResult object

// Raw insert
Database::init()->query(
    "INSERT INTO products (name, price) VALUES (?, ?)",
    ['Laptop', 10000000]
);

// Get array instead of QueryResult
$array = Database::init()->query(
    "SELECT * FROM products WHERE status = ?",
    ['active']
)->getResultArray();
```

### Danger: SQL Injection

```php
// ❌ NEVER do this (SQL injection vulnerable)
$name = "'; DROP TABLE products; --";
Database::init()->query("SELECT * FROM products WHERE name = '$name'");

// ✅ Always use placeholders
$name = "'; DROP TABLE products; --";
Database::init()->query("SELECT * FROM products WHERE name = ?", [$name]);

// ✅ Use QueryBuilder
Database::init()->table('products')
    ->where('name', $name)
    ->get();
```

---

## Reset Builder

### Clear Query State

```php
$qb = Database::init()->table('products');

$qb->where('status', 'active')
   ->limit(10);

// Reset all conditions
$qb->resetBuilder();

// Now can build new query
$qb->where('price', '>', 100)
   ->get();  // Only uses new condition, not old ones
```

---

## Best Practices

✅ **Do:**

- Use QueryBuilder for safety (prevents SQL injection)
- Select only needed columns
- Use JOINs instead of multiple queries (N+1 prevention)
- Index frequently queried columns
- Use caching for expensive queries
- Enable profiling in development
- Use transactions for multi-step operations
- Paginate large result sets

❌ **Don't:**

- Build raw SQL strings (use placeholders)
- Query in loops (use JOINs)
- Select \* (select specific columns)
- Cache sensitive data
- Trust user input (always validate)
- Forget WHERE clause in UPDATE/DELETE (very dangerous!)
- Query database for simple operations (cache static data)
- Use LIMIT without ORDER BY (results unpredictable)

---

**Untuk Detail Lebih Lanjut:** Lihat `doc/API.md` untuk complete QueryBuilder method reference.
