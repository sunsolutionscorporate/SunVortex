# Database Features & Usage Guide

Panduan lengkap semua fitur database dalam framework, dengan contoh penggunaan praktis.

---

## Table of Contents

1. [QueryBuilder Fundamentals](#querybuilder-fundamentals)
2. [Model & ORM Features](#model--orm-features)
3. [Relationships](#relationships)
4. [Pagination](#pagination)
5. [Query Scopes](#query-scopes)
6. [Timestamps & Soft Deletes](#timestamps--soft-deletes)
7. [Mass Assignment & Validation](#mass-assignment--validation)
8. [Accessors & Mutators](#accessors--mutators)
9. [Events & Hooks](#events--hooks)
10. [Collections](#collections)
11. [Transactions](#transactions)
12. [Query Caching](#query-caching)
13. [Query Profiling & Logging](#query-profiling--logging)
14. [Advanced Querying](#advanced-querying)
15. [Best Practices](#best-practices)

---

## QueryBuilder Fundamentals

### Basic Select

```php
// Get all columns
$users = Database::init()->table('users')->getResultArray();

// Select specific columns
$users = Database::init()->table('users')
    ->select(['id', 'name', 'email'])
    ->getResultArray();

// Select dengan alias
$users = Database::init()->table('users')
    ->select('id, username as name, email')
    ->getResultArray();
```

### Where Conditions

```php
$db = Database::init();

// Single where
$users = $db->table('users')->where('status', 'active')->getResultArray();

// Multiple where (AND)
$users = $db->table('users')
    ->where('status', 'active')
    ->where('role', 'admin')
    ->getResultArray();

// Associative array where
$users = $db->table('users')
    ->where(['status' => 'active', 'role' => 'admin'])
    ->getResultArray();

// Custom expression
$users = $db->table('users')
    ->where('age > 18')
    ->getResultArray();

// OR conditions
$users = $db->table('users')
    ->where('status', 'active')
    ->orWhere('status', 'pending')
    ->getResultArray();

// WHERE IN
$users = $db->table('users')
    ->whereIn('role', ['admin', 'moderator'])
    ->getResultArray();

// WHERE NOT IN
$users = $db->table('users')
    ->whereNotIn('role', ['banned', 'suspended'])
    ->getResultArray();

// WHERE NULL
$users = $db->table('users')
    ->whereNull('deleted_at')
    ->getResultArray();

// WHERE NOT NULL
$users = $db->table('users')
    ->whereNotNull('last_login')
    ->getResultArray();

// LIKE
$users = $db->table('users')
    ->like('email', 'gmail', 'after')
    ->getResultArray();

// LIKE dengan side options: 'before', 'after', 'both' (default)
$users = $db->table('users')
    ->like('name', 'john', 'both')
    ->orLike('name', 'jane')
    ->getResultArray();
```

### Joins

```php
$db = Database::init();

// INNER JOIN (default)
$result = $db->table('posts p')
    ->join('users u', 'u.id = p.user_id')
    ->select('p.id, p.title, u.name')
    ->getResultArray();

// LEFT JOIN
$result = $db->table('users u')
    ->leftJoin('profiles p', 'p.user_id = u.id')
    ->select('u.id, u.name, p.bio')
    ->getResultArray();

// RIGHT JOIN
$result = $db->table('orders o')
    ->rightJoin('users u', 'u.id = o.user_id')
    ->getResultArray();

// Multiple JOINs
$result = $db->table('posts p')
    ->join('users u', 'u.id = p.user_id')
    ->join('categories c', 'c.id = p.category_id')
    ->select('p.id, p.title, u.name, c.name as category')
    ->getResultArray();
```

### Grouping & Aggregation

```php
$db = Database::init();

// GROUP BY
$result = $db->table('orders')
    ->select('user_id, COUNT(*) as total_orders')
    ->groupBy('user_id')
    ->getResultArray();

// GROUP BY dengan HAVING
$result = $db->table('orders')
    ->select('user_id, COUNT(*) as total, SUM(amount) as total_amount')
    ->groupBy('user_id')
    ->having('COUNT(*) > 5')
    ->getResultArray();

// COUNT dengan kondisi
$activeUsers = $db->table('users')
    ->where('status', 'active')
    ->countAllResults();
```

### Ordering & Limiting

```php
$db = Database::init();

// ORDER BY
$users = $db->table('users')
    ->orderBy('created_at DESC')
    ->getResultArray();

// Multiple ORDER BY
$users = $db->table('users')
    ->orderBy('role ASC, created_at DESC')
    ->getResultArray();

// LIMIT
$users = $db->table('users')
    ->limit(10)
    ->getResultArray();

// LIMIT dengan OFFSET
$users = $db->table('users')
    ->limit(10)
    ->offset(20)
    ->getResultArray();
```

---

## Model & ORM Features

### Creating a Model

```php
// File: app/models/User.php
class User extends BaseModel
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $fillable = ['username', 'email', 'password'];
    protected $timestamps = true;  // auto created_at, updated_at
}
```

### CRUD Operations

```php
// CREATE
$user = new User();
$user->username = 'john_doe';
$user->email = 'john@example.com';
$user->password = 'secret'; // akan di-hash oleh mutator
$user->save();

// atau gunakan static create
$user = User::create([
    'username' => 'jane_doe',
    'email' => 'jane@example.com',
    'password' => 'secret'
]);

// READ
$user = User::find(1);
$user->email; // 'john@example.com'

// Get all
$users = User::all(); // returns Collection

// First
$user = User::first();

// Last
$user = User::last();

// UPDATE
$user = User::find(1);
$user->email = 'newemail@example.com';
$user->save();

// atau updateOrCreate
$user = User::updateOrCreate(
    ['email' => 'john@example.com'],  // conditions
    ['username' => 'john_updated']     // data
);

// DELETE
$user = User::find(1);
$user->delete();

// atau soft delete (jika enabled)
// Record disimpan dengan deleted_at timestamp
```

### Finding Records

```php
// Find by primary key
$user = User::find(1);

// Find with conditions
$user = User::first(); // first record
$user = User::last();  // last record

// Count records
$total = User::count();

// Check existence
$exists = User::existsWhere(['email' => 'john@example.com']);
```

---

## Relationships

### Define Relationships

```php
// In User model:
class User extends BaseModel
{
    // HasMany: User punya banyak Posts
    public function relationPosts()
    {
        $posts = Post::query()->where('user_id', $this->id)->getResultArray();
        $collection = [];
        foreach ($posts as $post) {
            $collection[] = new Post($post);
        }
        return new Collection($collection);
    }

    // HasOne: User punya satu Profile
    public function relationProfile()
    {
        $data = Profile::query()
            ->where('user_id', $this->id)
            ->limit(1)
            ->first();
        return $data ? new Profile($data) : null;
    }
}

// In Post model:
class Post extends BaseModel
{
    // BelongsTo: Post milik User
    public function relationAuthor()
    {
        return User::find($this->user_id);
    }
}
```

### Using Relationships

```php
// Lazy loading (load relasi saat diakses)
$user = User::find(1);
$posts = $user->relationPosts();  // queries DB saat ini
$profile = $user->relationProfile();

// Access via with() untuk eager loading
$user = User::find(1)->with('posts,profile');
$posts = $user->getRelation('posts');

// Through relationships
$post = Post::find(1);
$author = $post->relationAuthor();
$authorName = $author->username;
```

### N+1 Query Prevention

```php
// BURUK: N+1 queries (10 posts = 11 queries)
$posts = Post::all();
foreach ($posts as $post) {
    echo $post->relationAuthor()->username; // query per post!
}

// BAIK: Eager loading (2 queries total)
$posts = Post::all()->with('author'); // hypothetical
foreach ($posts as $post) {
    echo $post->getRelation('author')->username;
}
```

---

## Pagination

### Basic Pagination

```php
// Get page & per_page dari request
$page = (int)($_GET['page'] ?? 1);
$perPage = (int)($_GET['per_page'] ?? 10);

// Paginate static method
$result = User::paginate($page, $perPage, ['status' => 'active']);

// Result structure
$result['data'];      // Collection of User models
$result['meta'] = [
    'total'        => 100,
    'per_page'     => 10,
    'current_page' => 1,
    'last_page'    => 10,
    'from'         => 1,
    'to'           => 10,
];
```

### Using with QueryBuilder

```php
$db = Database::init();

$page = (int)($_GET['page'] ?? 1);
$perPage = (int)($_GET['per_page'] ?? 10);
$offset = ($page - 1) * $perPage;

$qb = $db->table('users')->where('status', 'active');

// Get total COUNT sebelum limit
$total = $qb->countAllResults(false);

// Apply limit/offset untuk halaman ini
$rows = $qb->limit($perPage)->offset($offset)->getResultArray();

$lastPage = ceil($total / $perPage);
$from = ($total > 0) ? ($offset + 1) : 0;
$to = ($total > 0) ? ($offset + count($rows)) : 0;

$response = [
    'data' => $rows,
    'meta' => [
        'total' => $total,
        'per_page' => $perPage,
        'current_page' => $page,
        'last_page' => $lastPage,
        'from' => $from,
        'to' => $to,
    ]
];
```

---

## Query Scopes

Query Scopes adalah reusable query patterns yang membuat code lebih clean.

### Define Scopes

```php
class User extends BaseModel
{
    // Scope method: scope{Name}
    public function scopeActive($qb)
    {
        return $qb->where('status', 'active');
    }

    public function scopeByRole($qb, $role)
    {
        return $qb->where('role', $role);
    }

    public function scopeRecentDays($qb, $days = 7)
    {
        $date = date('Y-m-d', strtotime("-{$days} days"));
        return $qb->where('created_at >=', $date);
    }

    public function scopeLatest($qb)
    {
        return $qb->orderBy('created_at DESC');
    }
}
```

### Using Scopes

```php
// Dynamic scope calling
$model = new User();

// Single scope
$qb = $model->active();  // calls scopeActive()
$users = $qb->getResultArray();

// Chaining scopes
$qb = $model->active()->byRole('admin')->latest();
$adminUsers = $qb->getResultArray();

// Scope dengan parameter
$recentAdmins = $model->active()->byRole('admin')->recentDays(30)->getResultArray();
```

---

## Timestamps & Soft Deletes

### Timestamps

Automatic `created_at` dan `updated_at` setiap record.

```php
class User extends BaseModel
{
    protected $timestamps = true;  // enable
    protected $createdAtColumn = 'created_at';
    protected $updatedAtColumn = 'updated_at';
}

// created_at & updated_at auto-set saat create/update
$user = new User(['name' => 'John']);
$user->save();
// created_at = current timestamp
// updated_at = current timestamp

$user->name = 'Jane';
$user->save();
// updated_at = new timestamp
```

### Soft Deletes

Record tidak benar-benar dihapus, hanya ditandai dengan `deleted_at` timestamp.

```php
class Post extends BaseModel
{
    protected $softDelete = true;
    protected $deletedAtColumn = 'deleted_at';
}

// Delete (soft)
$post = Post::find(1);
$post->delete();  // sets deleted_at = now

// Query otomatis exclude soft-deleted
$posts = Post::all();  // tidak include yang deleted

// Access soft-deleted
$post = Post::query()->withTrashed()->first();

// Restore
$post->restore();  // sets deleted_at = null
```

---

## Mass Assignment & Validation

### Fillable & Guarded

```php
class User extends BaseModel
{
    // Whitelist fields yang bisa di-fill
    protected $fillable = ['username', 'email', 'password'];

    // Atau blacklist fields
    protected $guarded = ['id', 'created_at', 'updated_at'];
}

// Safe: hanya fillable fields
$user = new User();
$user->fill([
    'username' => 'john',
    'email' => 'john@example.com',
    'password' => 'secret',
    'admin' => true  // IGNORED, bukan di fillable
]);
$user->save();

// Safe di create
$user = User::create([
    'username' => 'jane',
    'email' => 'jane@example.com',
    'password' => 'secret'
]);
```

---

## Accessors & Mutators

Transform data saat get/set.

### Mutators (Setters)

```php
class User extends BaseModel
{
    // Method: set{FieldName}Attribute
    public function setPasswordAttribute($value)
    {
        // Otomatis dipanggil saat set password
        return password_hash($value, PASSWORD_BCRYPT);
    }

    public function setEmailAttribute($value)
    {
        return strtolower(trim($value));
    }
}

// Usage
$user = new User();
$user->password = 'plaintext';  // auto-hashed
$user->email = 'John@EXAMPLE.COM';  // auto-lowercased
```

### Accessors (Getters)

```php
class User extends BaseModel
{
    // Method: get{FieldName}Attribute
    public function getFullNameAttribute($value)
    {
        return ucwords(strtolower($value));
    }

    public function getEmailAttribute($value)
    {
        return strtolower($value);
    }
}

// Usage
echo $user->full_name;  // "John Doe" (formatted)
```

### Casting

```php
class Post extends BaseModel
{
    protected $casts = [
        'id' => 'int',
        'user_id' => 'int',
        'published' => 'bool',
        'metadata' => 'json',  // auto json_decode
        'created_at' => 'datetime',
    ];
}

$post = Post::find(1);
$post->published;  // true (boolean, bukan "1")
$post->metadata;   // array (decoded JSON)
```

---

## Events & Hooks

React to model lifecycle events.

```php
class User extends BaseModel
{
    public function __construct($attributes = [])
    {
        parent::__construct($attributes);

        // Register listeners
        $this->on('before:save', function ($model) {
            // validate, transform, etc
        });

        $this->on('after:create', function ($model) {
            // send welcome email
        });

        $this->on('after:update', function ($model) {
            // log changes
        });

        $this->on('before:delete', function ($model) {
            // backup data
        });
    }
}

// Global listeners
User::listen('after:create', function ($user) {
    // fired untuk semua User::create()
});
```

### Available Events

- `before:save` - sebelum create atau update
- `after:save` - sesudah create atau update
- `after:create` - sesudah create baru
- `after:update` - sesudah update
- `before:delete` - sebelum delete
- `after:delete` - sesudah delete

---

## Collections

Hasil query mengembalikan `Collection` untuk manipulation yang lebih mudah.

```php
$users = User::all();  // Collection

// Filter
$active = $users->filter(function ($user) {
    return $user->status === 'active';
});

// Map
$names = $users->map(function ($user) {
    return $user->name;
});

// Pluck (extract column)
$emails = $users->pluck('email');
$emailsById = $users->pluck('email', 'id');

// Chunk
$chunks = $users->chunk(10);

// Group
$byRole = $users->groupBy('role');

// First / Last
$first = $users->first();
$last = $users->last();

// Count
$count = $users->count();

// Unique
$unique = $users->unique('email');

// Sort
$sorted = $users->sort(function ($a, $b) {
    return strcmp($a->name, $b->name);
});

// Reduce
$total = $users->reduce(function ($carry, $item) {
    return $carry + $item->age;
}, 0);

// Chain operations
$result = $users
    ->filter(fn ($u) => $u->status === 'active')
    ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name])
    ->toArray();

// Convert
$array = $users->toArray();
$json = $users->toJson();
```

---

## Transactions

Handle database transactions dengan atomic operations.

```php
$db = Database::init();

// Manual transaction
try {
    $db->transaction(function ($tm) {
        // semua operations di sini atomic
        User::create(['email' => 'john@example.com']);
        Post::create(['title' => 'Hello', 'user_id' => 1]);
        // auto-commit jika sukses
    });
} catch (Exception $e) {
    // auto-rollback jika error
    echo $e->getMessage();
}

// Nested transactions (savepoints)
$tm = $db->transaction;
$tm->begin();
try {
    User::create([...]);
    $tm->begin('nested');  // savepoint
    Post::create([...]);
    $tm->commit();  // release savepoint
} catch (Exception $e) {
    $tm->rollback();  // rollback to savepoint
}
$tm->commit();  // commit main transaction
```

---

## Query Caching

Cache query results untuk performance improvement.

```php
$cache = new QueryCache('file', 3600);  // TTL 1 jam

// Put result di cache
$users = Database::init()->table('users')->getResultArray();
$cache->put('users_all', $users);

// Get dari cache (atau null jika expired)
$users = $cache->get('users_all');

// Forget (invalidate)
$cache->forget('users_all');

// Flush semua cache
$cache->flush();

// Disable cache temporarily
$cache->disable();
// queries...
$cache->enable();
```

---

## Query Profiling & Logging

Monitor dan debug queries.

```php
$profiler = new QueryProfiler();

// Log queries otomatis di development
$profiler->enable();

// Log kustom
$profiler->log(
    'SELECT * FROM users WHERE id = ?',
    [1],
    12.5  // duration in ms
);

// Get all queries
$queries = $profiler->getQueries();

// Get slow queries (> 1000ms)
$slow = $profiler->getSlowQueries(1000);

// Get stats
$totalTime = $profiler->getTotalTime();
$count = $profiler->getQueryCount();

// Reset
$profiler->reset();

// Disable
$profiler->disable();
```

---

## Advanced Querying

### Subqueries

```php
$db = Database::init();

// Subquery di WHERE
$activePosts = $db->table('posts')
    ->where('user_id IN (SELECT id FROM users WHERE status = "active")')
    ->getResultArray();

// Subquery di SELECT
$result = $db->getQueryBuilder()
    ->select('*, (SELECT COUNT(*) FROM posts WHERE user_id = users.id) as post_count')
    ->from('users')
    ->getResultArray();
```

### Raw Queries

```php
$db = Database::init();

// Parameterized queries
$result = $db->query(
    'SELECT * FROM users WHERE status = :status AND created_at > :date',
    [
        'status' => 'active',
        'date' => '2024-01-01'
    ]
)->getResultArray();
```

### Complex Joins

```php
$db = Database::init();

$result = $db->table('posts p')
    ->select('p.id, p.title, u.name, COUNT(c.id) as comment_count')
    ->join('users u', 'u.id = p.user_id')
    ->leftJoin('comments c', 'c.post_id = p.id')
    ->where('p.status', 'published')
    ->groupBy('p.id')
    ->having('COUNT(c.id) > 0')
    ->getResultArray();
```

---

## Best Practices

1. **Use Models for Business Logic**

   - Keep QueryBuilder untuk simple queries
   - Use Models untuk complex operations

2. **Always Paginate Large Results**

   - Gunakan pagination untuk queries yang bisa besar
   - Jangan fetch semua tanpa limit

3. **Eager Load Relationships**

   - Hindari N+1 queries
   - Use `with()` untuk load relasi

4. **Use Transactions untuk Multiple Operations**

   - Jamin atomicity dan consistency
   - Otomatis rollback saat error

5. **Leverage Query Scopes**

   - Reuse query patterns
   - Lebih readable dan maintainable

6. **Use Caching Wisely**

   - Cache heavy queries
   - Invalidate saat data berubah

7. **Enable Query Profiling di Development**

   - Find slow queries
   - Optimize dengan indexes

8. **Respect Mass Assignment**

   - Define fillable/guarded fields
   - Protect dari unintended updates

9. **Use Soft Deletes untuk Audit Trail**

   - Keep deleted data untuk history
   - Easy restore jika perlu

10. **Validate Before Save**
    - Validate data sebelum model->save()
    - Handle validation errors properly

---

## Summary

Database framework menyediakan:

- ✅ Powerful QueryBuilder dengan fluent API
- ✅ ORM-like Models dengan auto timestamps, casting, mutators/accessors
- ✅ Relationships (belongsTo, hasMany, hasOne) dengan eager loading
- ✅ Query Scopes untuk reusable patterns
- ✅ Soft Deletes untuk data retention
- ✅ Collections untuk easy data manipulation
- ✅ Transactions dengan nested savepoint support
- ✅ Query Caching untuk performance
- ✅ Query Profiling untuk debugging
- ✅ Events & Hooks untuk lifecycle management

Dengan fitur-fitur ini, framework setara dengan Laravel Eloquent dan Doctrine ORM.
