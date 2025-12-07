# SunVortex — BaseModel Dokumentasi Lengkap

**File:** `system/Core/BaseModel.php` (883 baris)

BaseModel adalah ORM (Object-Relational Mapping) base class yang menyediakan fitur-fitur lengkap untuk interaksi database. Semua model aplikasi harus extend class ini.

---

## Daftar Isi

1. [Konfigurasi Tabel](#konfigurasi-tabel)
2. [Mass Assignment & Fillable](#mass-assignment)
3. [Type Casting](#type-casting)
4. [CRUD Operations](#crud-operations)
5. [Query Builder Integration](#query-builder)
6. [Lifecycle Events](#lifecycle-events)
7. [Timestamps & Soft Delete](#timestamps)
8. [Attribute Mutators & Accessors](#mutators-accessors)
9. [Relations & Nested Data](#relations)
10. [Advanced Features](#advanced-features)

---

## Konfigurasi Tabel

### Properti Dasar

```php
class User_model extends BaseModel {
    // Nama tabel database
    protected $table = 'users';

    // Primary key column (default: 'id')
    protected $primaryKey = 'id';

    // Columns yang boleh mass-assigned (whitelist)
    protected $fillable = ['name', 'email', 'password', 'status'];

    // Columns yang tidak boleh diubah (blacklist)
    protected $guarded = ['id', 'created_at'];

    // Soft delete support
    protected $softDelete = true;
    protected $deletedAtColumn = 'deleted_at';

    // Auto timestamps
    protected $timestamps = true;
    protected $createdAtColumn = 'created_at';
    protected $updatedAtColumn = 'updated_at';
}
```

### Auto-Detection Table Name

Jika `$table` tidak didefinisikan, BaseModel otomatis menentukan nama tabel:

```php
class Product_model extends BaseModel {
    // $table akan otomatis menjadi 'products'
    // (lowercase, strip '_model', naive pluralize)
}

class UserProfile_model extends BaseModel {
    // $table akan otomatis menjadi 'userprofiles'
}
```

**Rules auto-detection:**

- Strip suffix `_model` atau `Model`
- Convert ke lowercase
- Naive pluralization (tambah 's')

---

## Mass Assignment & Fillable

### Fillable vs Guarded

```php
class Product_model extends BaseModel {
    // Whitelist approach (recommended)
    protected $fillable = ['name', 'price', 'stock', 'description'];

    // Data yang masuk akan difilter
    $product = new Product_model([
        'name' => 'Laptop',
        'price' => 10000000,
        'stock' => 5,
        'description' => 'Gaming laptop',
        'admin' => true  // ❌ IGNORED (not in fillable)
    ]);
}
```

```php
class User_model extends BaseModel {
    // Blacklist approach
    protected $guarded = ['id', 'admin', 'created_at'];

    // Semua field diisi kecuali di guarded
    $user = new User_model([
        'name' => 'John',
        'email' => 'john@example.com',
        'id' => 999,  // ❌ IGNORED (in guarded)
        'admin' => 1  // ❌ IGNORED (in guarded)
    ]);
}
```

### Fill Method

```php
$model = new Product_model();
$model->fill([
    'name' => 'New Name',
    'price' => 15000000
]);

// Hanya field di fillable yang diset
echo $model->name;   // "New Name"
echo $model->price;  // 15000000
```

### Cek Fillable Status

```php
$model = new Product_model();

if ($model->isFillable('name')) {
    // Column 'name' adalah fillable
}

if (!$model->isFillable('id')) {
    // Column 'id' tidak fillable
}
```

---

## Type Casting

### Supported Casts

```php
class Product_model extends BaseModel {
    protected $casts = [
        'price' => 'float',           // String → float
        'stock' => 'int',             // String → int
        'is_active' => 'bool',        // String/int → bool
        'tags' => 'array',            // JSON → array
        'metadata' => 'json',         // JSON → array (alias)
        'published_at' => 'date',     // String → DateTime object
    ];
}

// Auto-casting saat access:
$product = Product_model::find(1);
echo gettype($product->price);     // "double" (float)
echo gettype($product->stock);     // "integer"
echo gettype($product->is_active); // "boolean"
echo is_array($product->tags);     // true
```

### Built-in Cast Types

| Cast Type | Input             | Output                   |
| --------- | ----------------- | ------------------------ |
| `int`     | `"123"`           | `123` (int)              |
| `float`   | `"45.67"`         | `45.67` (float)          |
| `bool`    | `"1"`, `"true"`   | `true` (bool)            |
| `string`  | `123`             | `"123"` (string)         |
| `array`   | `'[1,2,3]'`       | `[1,2,3]` (array)        |
| `json`    | `'{"key":"val"}'` | `["key"=>"val"]` (array) |
| `date`    | `'2025-12-07'`    | DateTime object          |

---

## CRUD Operations

### Create

```php
// Method 1: Constructor + save()
$product = new Product_model([
    'name' => 'Laptop Dell',
    'price' => 12000000,
    'stock' => 5
]);
$id = $product->save();  // Returns insert ID

// Method 2: Direct insert
$product = new Product_model();
$product->name = 'Laptop HP';
$product->price = 11000000;
$product->stock = 3;
$product->save();

// Method 3: Create (instance method)
$product = new Product_model();
$created = $product->create([
    'name' => 'Laptop Lenovo',
    'price' => 10000000
]);
```

### Read / Find

```php
$model = new Product_model();

// Find by primary key
$product = $model->find(1);

// Find by attribute
$product = $model->findBy('email', 'user@example.com');

// Get all (via QueryBuilder)
$all = $model->db->table($model->table)->getResultArray();

// Query with conditions
$results = $model->db->table($model->table)
    ->where('status', 'active')
    ->orderBy('name')
    ->getResultArray();

// Paginate
$page = $model->paginate($page = 1, $limit = 10);
// Returns: ['data' => [...], 'total' => N, 'limit' => 10, 'page' => 1]
```

### Update

```php
$product = $model->find(1);

// Method 1: Set properties + save()
$product->name = 'Updated Name';
$product->price = 13000000;
$product->save();

// Method 2: Fill + save()
$product->fill([
    'name' => 'New Name',
    'price' => 14000000,
    'stock' => 10
]);
$product->save();

// Track changes
$changes = $product->getChangedAttributes();
// Returns: ['name' => 'New Name', 'price' => 14000000, 'stock' => 10]
```

### Delete

```php
$product = $model->find(1);

// Soft delete (sets deleted_at timestamp)
if ($model->softDelete) {
    $product->delete();  // Sets deleted_at = NOW()
}

// Hard delete (actually removes from DB)
if (!$model->softDelete) {
    $product->delete();  // SQL: DELETE FROM products WHERE id = 1
}

// Delete by ID (helper)
$model->deleteById(1);

// Verify deletion
if ($product->existsInDb()) {
    echo "Record still exists";
}
```

---

## Query Builder Integration

### The `query()` Method

```php
class Product_model extends BaseModel {
    // Get QueryBuilder untuk tabel ini
    public function getActive() {
        return $this->query()
            ->where('status', 'active')
            ->orderBy('name')
            ->getResultArray();
    }

    public function searchByName($keyword) {
        return $this->query()
            ->like('name', $keyword)
            ->where('status', 'active')
            ->limit(20)
            ->getResultArray();
    }

    public function getByCategory($catId) {
        return $this->query()
            ->where('category_id', $catId)
            ->where('status', 'active')
            ->orderBy('price')
            ->getResultArray();
    }

    public function getPaginated($page = 1, $status = null) {
        $qb = $this->query();

        if ($status) {
            $qb->where('status', $status);
        }

        return $qb->paginate($page, 10);
    }
}
```

### Advanced Query Examples

```php
// Complex WHERE + JOINs
$results = $this->query()
    ->select('p.id, p.name, c.name as category')
    ->leftJoin('categories c', 'c.id = p.category_id')
    ->where('p.status', 'active')
    ->where('p.price', '>', 50000)
    ->like('p.name', 'laptop')
    ->orderBy('p.price ASC')
    ->limit(20)
    ->getResultArray();

// GROUP BY + HAVING
$results = $this->query()
    ->select('category_id, COUNT(*) as total, AVG(price) as avg_price')
    ->groupBy('category_id')
    ->having('COUNT(*) > 5')
    ->getResultArray();

// Subquery-like behavior
$total = $this->query()
    ->where('status', 'active')
    ->countAllResults();
```

---

## Lifecycle Events

### Available Events

**Save Lifecycle:**

- `before:save` — sebelum insert atau update
- `after:save` — setelah insert atau update berhasil
- `after:save:failed` — setelah insert/update gagal

**Create Lifecycle:**

- `before:create` — sebelum insert
- `after:create` — setelah insert berhasil
- `after:create:failed` — setelah insert gagal

**Update Lifecycle:**

- `before:update` — sebelum update
- `after:update` — setelah update berhasil
- `after:update:failed` — setelah update gagal

**Delete Lifecycle:**

- `before:delete` — sebelum delete
- `after:delete` — setelah delete berhasil
- `after:delete:failed` — setelah delete gagal

### Register Event Handler

```php
class User_model extends BaseModel {
    public function __construct($attributes = []) {
        parent::__construct($attributes);

        // Hash password sebelum save
        $this->on('before:save', function($model) {
            if (isset($model->attributes['password'])) {
                $len = strlen($model->attributes['password']);

                // Hanya hash jika belum di-hash (length < 60)
                if ($len < 60) {
                    $model->attributes['password'] =
                        password_hash($model->attributes['password'], PASSWORD_BCRYPT);
                }
            }
        });

        // Log creation
        $this->on('after:create', function($model) {
            error_log("[User Created] ID: {$model->id}, Email: {$model->email}");
        });

        // Clear cache after update
        $this->on('after:update', function($model) {
            $cache = Database::init()->getCache();
            if ($cache) {
                $cache->forget('user_' . $model->id);
            }
        });

        // Log deletion
        $this->on('after:delete', function($model) {
            error_log("[User Deleted] ID: {$model->id}");
        });
    }
}
```

### Global Events (Static)

```php
// Register global listener untuk semua instances
BaseModel::listen('after:create', function($model) {
    // Triggered untuk semua model instances
    echo "A record was created: " . get_class($model);
});

// Remove listener
$callback = function($model) { ... };
$model->off('before:save', $callback);

// Remove all listeners untuk event
$model->off('before:save');
```

---

## Timestamps & Soft Delete

### Timestamps

```php
class Product_model extends BaseModel {
    protected $timestamps = true;  // Enable auto timestamps
    protected $createdAtColumn = 'created_at';
    protected $updatedAtColumn = 'updated_at';
}

// Automatically set:
$product = new Product_model(['name' => 'Laptop']);
$product->save();

// created_at = 2025-12-07 10:30:45
// updated_at = 2025-12-07 10:30:45

// Setelah update:
$product->name = 'New Name';
$product->save();

// created_at = 2025-12-07 10:30:45  ← unchanged
// updated_at = 2025-12-07 10:35:20  ← updated
```

### Soft Delete

```php
class Product_model extends BaseModel {
    protected $softDelete = true;
    protected $deletedAtColumn = 'deleted_at';
}

$product = $model->find(1);
$product->delete();  // Sets deleted_at = NOW(), tidak hapus record

// Query otomatis exclude soft-deleted records:
$active = $model->query()->getResultArray();  // Tidak termasuk yang deleted

// Find soft-deleted record
$deleted = $model->find(1);  // Null (karena soft-deleted)

// Include soft-deleted dalam query
$all = $model->db->table($model->table)
    ->select('*')  // No whereNull('deleted_at')
    ->getResultArray();

// Force delete (hard delete)
$product->forceDelete();  // Permanently remove from DB
```

---

## Attribute Mutators & Accessors

### Accessor (Getter)

Accessor dipanggil otomatis saat read attribute:

```php
class User_model extends BaseModel {
    // Accessor: protected getNameAttribute($value)
    protected function getNameAttribute($value) {
        return strtoupper($value);
    }

    protected function getEmailAttribute($value) {
        return strtolower($value);
    }
}

$user = User_model::find(1);
echo $user->name;   // "john" di DB, tapi "JOHN" dioutput (accessor applied)
echo $user->email;  // "John@Example.Com" di DB, tapi "john@example.com" (accessor applied)
```

### Mutator (Setter)

Mutator dipanggil otomatis saat set attribute:

```php
class Product_model extends BaseModel {
    // Mutator: protected setNameAttribute($value)
    protected function setNameAttribute($value) {
        $this->attributes['name'] = trim(ucfirst($value));
    }

    protected function setPriceAttribute($value) {
        // Ensure price is always float
        $this->attributes['price'] = (float) $value;
    }
}

$product = new Product_model();
$product->name = "  laptop dell  ";  // Mutator applied
echo $product->name;  // "Laptop dell" (trimmed + ucfirst)

$product->price = "12000000";  // Mutator applied
echo gettype($product->price);  // "double" (converted to float)
```

---

## Relations & Nested Data

### Set Relations (Eager Loading)

```php
class User_model extends BaseModel {
    public function getUserWithPosts($userId) {
        $user = $this->find($userId);

        // Load posts for this user
        $posts = Database::init()->table('posts')
            ->where('user_id', $userId)
            ->getResultArray();

        // Attach posts as relation
        $user->setRelation('posts', $posts);

        return $user;
    }
}

// Usage
$user = $userModel->getUserWithPosts(1);
echo $user->posts;  // Array of posts attached to user
```

### Convert to Array & JSON

```php
$user = User_model::find(1);

// ToArray (includes relations)
$data = $user->toArray();
// [
//     'id' => 1,
//     'name' => 'John',
//     'email' => 'john@example.com',
//     'posts' => [
//         ['id' => 1, 'title' => '...', ...],
//         ['id' => 2, 'title' => '...', ...],
//     ]
// ]

// ToJSON
$json = $user->toJson();  // JSON string

// ToJSON dengan options
$json = $user->toJson(JSON_PRETTY_PRINT);
```

---

## Advanced Features

### Refresh from Database

```php
$product = $model->find(1);

// External change terjadi di database...

// Reload dari database
$product->refresh();

// Sekarang $product reflect perubahan terbaru
```

### UpdateOrCreate

```php
$user = $userModel->updateOrCreate(
    ['email' => 'john@example.com'],  // Search criteria
    ['name' => 'John Doe', 'age' => 30]  // Data to merge
);

// Jika ditemukan: update dengan data baru
// Jika tidak ditemukan: create dengan search criteria + data
```

### FirstOrCreate

```php
$user = $userModel->firstOrCreate(
    ['email' => 'john@example.com'],  // Search & set attributes
    ['name' => 'John Doe']  // Additional values
);

// Mirip updateOrCreate, tapi tidak update jika sudah ada
```

### Transaction Support

```php
$model->beginTransaction();

try {
    $product1 = new Product_model(['name' => 'Laptop', 'price' => 10000000]);
    $product1->save();

    $product2 = new Product_model(['name' => 'Mouse', 'price' => 200000]);
    $product2->save();

    $model->commit();
} catch (Exception $e) {
    $model->rollBack();
    echo "Transaction failed: " . $e->getMessage();
}
```

### Existence Check

```php
$product = new Product_model(['name' => 'Laptop']);
// Not yet saved, so:
$product->existsInDb();  // false

$product->save();
$product->existsInDb();  // true

// Via getChangedAttributes()
$changes = $product->getChangedAttributes();
// Returns array of attributes that changed since last save
```

---

## Logging

### Enable Standard Logging

```php
$model = new Product_model();
$model->enableStandardLogging();

// Logs akan ditulis ke `storage/logs/` untuk:
// - CREATE: {'event': 'create', 'id': 1, 'attributes': {...}}
// - READ: {'event': 'read', 'id': 1}
// - UPDATE: {'event': 'update', 'id': 1, 'changes': {...}}
// - DELETE: {'event': 'delete', 'id': 1}
```

---

## Best Practices

✅ **Do:**

- Gunakan `fillable` untuk whitelist columns (lebih aman)
- Leverage events untuk business logic (password hashing, cache invalidation)
- Gunakan soft delete untuk recoverable data
- Cast types untuk data integrity
- Paginate large result sets
- Use QueryBuilder untuk complex queries

❌ **Don't:**

- Simpan password plain-text (hash via mutator/event)
- Modifikasi `$original` attributes secara langsung
- Bypass `fillable`/`guarded` (gunakan `$attributes` jika urgent)
- Lakukan query dalam loops (N+1 problem)
- Abaikan error handling di CRUD operations

---

**Untuk Detail Lebih Lanjut:** Lihat `doc/API.md` untuk method signatures lengkap.
