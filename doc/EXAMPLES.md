# SunVortex — Contoh & Tutorial Praktis (EXAMPLES.md)

Dokumen ini berisi contoh kode nyata, tutorial lengkap, dan praktik terbaik untuk implementasi fitur di SunVortex. Setiap contoh disertai dengan penjelasan bahasa Indonesia dan kode yang siap copy-paste.

---

## Daftar Contoh

1. [CRUD Lengkap: Tabel `products`](#crud-products)
2. [REST API JSON](#rest-api)
3. [Query Advanced & Pagination](#advanced-queries)
4. [Validasi & Error Handling](#validasi)
5. [Events & Hooks Lifecycle](#events)
6. [Middleware Custom](#middleware)
7. [Query Caching](#caching)

---

## CRUD Lengkap: Tabel `products`

Contoh mendalam: membuat CRUD untuk tabel `products` dengan validasi dan handling error.

### Step 1: Migration

File: `storage/database/migrations/2025_12_07_120000_create_products_table.php`

```php
<?php

use System\Database\Migration\Migration;

/**
 * Create products table migration
 *
 * Membuat tabel untuk menyimpan data produk dengan kolom-kolom umum:
 * - id (primary key, auto-increment)
 * - name, description, price
 * - stock management
 * - status (active/inactive)
 * - timestamps (created_at, updated_at)
 * - soft delete (deleted_at)
 */
class CreateProductsTable extends Migration {
    public function up() {
        $this->schema->create('products', function(Blueprint $table) {
            // ID
            $table->id();

            // Basic Info
            $table->string('name', 255)->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);

            // Stock
            $table->integer('stock')->default(0);
            $table->integer('min_stock')->default(5);

            // Status
            $table->enum('status', ['active', 'inactive', 'discontinued'])->default('active');

            // Timestamps & soft delete
            $table->timestamps();
            $table->softDeletes();

            // Indexes untuk query sering
            $table->index(['status']);
            $table->index(['created_at']);
        });
    }

    public function down() {
        $this->schema->dropIfExists('products');
    }
}
```

Jalankan:

```bash
php sun migrate run
```

### Step 2: Model dengan Custom Methods

File: `app/models/product/Product_model.php`

```php
<?php

/**
 * Product Model
 *
 * ORM-style model untuk tabel `products`.
 * Fitur: casting, validation, custom queries, events.
 */
class Product_model extends BaseModel {
    // Table configuration
    protected $table = 'products';
    protected $primaryKey = 'id';

    // Mass assignment protection
    protected $fillable = ['name', 'description', 'price', 'stock', 'min_stock', 'status'];
    protected $guarded = [];

    // Type casting
    protected $casts = [
        'price' => 'float',
        'stock' => 'int',
        'min_stock' => 'int',
    ];

    // Timestamps & soft delete
    protected $timestamps = true;
    protected $softDelete = true;

    // Events (hooks)
    public function __construct($attributes = []) {
        parent::__construct($attributes);

        // Before save: validate dan normalize data
        $this->on('before:save', function($model) {
            // Validate price >= 0
            if ($model->price < 0) {
                throw new Exception('Price cannot be negative');
            }

            // Normalize name (trim & title case)
            if (isset($model->attributes['name'])) {
                $model->attributes['name'] = trim($model->attributes['name']);
            }
        });

        // After create: log creation
        $this->on('after:create', function($model) {
            error_log("[Product Created] ID: {$model->id}, Name: {$model->name}");
        });

        // After update: log changes
        $this->on('after:update', function($model) {
            $changes = $this->getChangedAttributes();
            error_log("[Product Updated] ID: {$model->id}, Changed: " . json_encode($changes));
        });
    }

    // ===== CUSTOM QUERIES =====

    /**
     * Ambil semua produk aktif
     */
    public function getActive() {
        return $this->db->table($this->table)
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->getResultArray();
    }

    /**
     * Cari produk by kategori (jika ada foreign key)
     */
    public function getByCategory($categoryId) {
        return $this->db->table($this->table)
            ->where('category_id', $categoryId)
            ->where('status', 'active')
            ->orderBy('price')
            ->getResultArray();
    }

    /**
     * Produk dengan stock rendah
     */
    public function getLowStock() {
        return $this->db->table($this->table)
            ->where('status', 'active')
            ->whereRaw('stock <= min_stock')  // Raw SQL comparison
            ->orderBy('stock')
            ->getResultArray();
    }

    /**
     * Search produk by name atau description
     */
    public function search($keyword) {
        return $this->db->table($this->table)
            ->where('status', 'active')
            ->like('name', $keyword)
            ->orLike('description', $keyword)
            ->orderBy('name')
            ->getResultArray();
    }

    /**
     * Paginate dengan filter
     */
    public function paginate($page = 1, $limit = 10, $status = null) {
        $qb = $this->db->table($this->table);

        if ($status) {
            $qb->where('status', $status);
        }

        return $qb->paginate($page, $limit);
    }

    /**
     * Hitung total value inventory
     */
    public function getTotalValue() {
        $result = $this->db->query(
            "SELECT SUM(price * stock) as total FROM {$this->table} WHERE status = ? AND deleted_at IS NULL",
            ['active']
        );
        $row = $result->getFirstRow();
        return $row['total'] ?? 0;
    }

    /**
     * Update stock (dengan validation)
     */
    public function updateStock($id, $quantity) {
        $product = $this->find($id);

        if (!$product) {
            return false;
        }

        $newStock = $product->stock + $quantity;

        if ($newStock < 0) {
            throw new Exception('Stock cannot be negative');
        }

        $product->stock = $newStock;
        return $product->save();
    }

    /**
     * Disable/mark as discontinued
     */
    public function discontinue($id) {
        $product = $this->find($id);

        if (!$product) {
            return false;
        }

        $product->status = 'discontinued';
        return $product->save();
    }
}
```

### Step 3: Controller dengan Validasi

File: `app/controllers/ProductController.php`

```php
<?php

/**
 * ProductController
 *
 * RESTful CRUD endpoint untuk produk.
 * Includes validation, error handling, JSON responses.
 */
class ProductController extends Controller {

    /**
     * List semua produk dengan pagination
     * GET /products
     */
    public function index() {
        try {
            $model = new Product_model();
            $page = $this->request->get('page', 1);
            $status = $this->request->get('status', null);

            $result = $model->paginate($page, 10, $status);

            // Render view dengan data
            return $this->view('product/index', [
                'products' => $result['data'],
                'pagination' => [
                    'page' => $result['page'],
                    'limit' => $result['limit'],
                    'total' => $result['total'],
                ]
            ]);
        } catch (Exception $e) {
            error_log($e->getMessage());
            return Response::status(500)->json(['error' => 'Server error']);
        }
    }

    /**
     * Show form create
     * GET /products/create
     */
    public function create() {
        return $this->view('product/form', ['product' => null]);
    }

    /**
     * Store produk baru
     * POST /products/store
     */
    public function store() {
        // Validasi input
        $name = trim($this->request->post('name', ''));
        $description = trim($this->request->post('description', ''));
        $price = (float) $this->request->post('price', 0);
        $stock = (int) $this->request->post('stock', 0);

        $errors = [];

        if (empty($name)) {
            $errors[] = 'Name is required';
        }

        if ($price <= 0) {
            $errors[] = 'Price must be greater than 0';
        }

        if ($stock < 0) {
            $errors[] = 'Stock cannot be negative';
        }

        // Validasi duplicate name
        $existing = new Product_model();
        $duplicate = $existing->findBy('name', $name);
        if ($duplicate && !isset($duplicate->id)) {
            $errors[] = 'Product name already exists';
        }

        // Jika ada error
        if (!empty($errors)) {
            return Response::redirect('/products/create')
                ->with('errors', $errors);
        }

        // Create product
        try {
            $product = new Product_model([
                'name' => $name,
                'description' => $description,
                'price' => $price,
                'stock' => $stock,
                'status' => 'active'
            ]);

            $result = $product->save();

            if ($result) {
                return Response::redirect('/products')
                    ->with('success', 'Product created successfully');
            } else {
                return Response::redirect('/products/create')
                    ->with('error', 'Failed to create product');
            }
        } catch (Exception $e) {
            error_log($e->getMessage());
            return Response::redirect('/products/create')
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Show edit form
     * GET /products/{id}/edit
     */
    public function edit($id) {
        try {
            $model = new Product_model();
            $product = $model->find($id);

            if (!$product) {
                return Response::status(404)
                    ->text('Product not found');
            }

            return $this->view('product/form', ['product' => $product]);
        } catch (Exception $e) {
            return Response::status(500)->text('Error');
        }
    }

    /**
     * Update produk
     * POST /products/{id}/update
     */
    public function update($id) {
        try {
            $model = new Product_model();
            $product = $model->find($id);

            if (!$product) {
                return Response::status(404)->text('Not found');
            }

            // Fill dari request
            $product->fill([
                'name' => trim($this->request->post('name')),
                'description' => trim($this->request->post('description')),
                'price' => (float) $this->request->post('price'),
                'stock' => (int) $this->request->post('stock'),
            ]);

            // Save
            if ($product->save()) {
                return Response::redirect('/products')
                    ->with('success', 'Product updated');
            }

            return Response::redirect("/products/{$id}/edit")
                ->with('error', 'Update failed');
        } catch (Exception $e) {
            error_log($e->getMessage());
            return Response::redirect("/products/{$id}/edit")
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Delete (soft delete)
     * GET /products/{id}/delete atau POST /products/{id}
     */
    public function delete($id) {
        try {
            $model = new Product_model();
            $product = $model->find($id);

            if (!$product) {
                return Response::status(404)->text('Not found');
            }

            $product->delete();  // Soft delete

            return Response::redirect('/products')
                ->with('success', 'Product deleted');
        } catch (Exception $e) {
            error_log($e->getMessage());
            return Response::redirect('/products')
                ->with('error', 'Delete failed');
        }
    }

    /**
     * JSON API: Get single product
     * GET /api/products/{id}
     */
    public function show($id) {
        try {
            $model = new Product_model();
            $product = $model->find($id);

            if (!$product) {
                return Response::status(404)->json(['error' => 'Not found']);
            }

            return Response::json([
                'status' => 'success',
                'data' => $product->toArray()
            ]);
        } catch (Exception $e) {
            return Response::status(500)->json(['error' => $e->getMessage()]);
        }
    }

    /**
     * JSON API: List products
     * GET /api/products
     */
    public function list() {
        try {
            $model = new Product_model();
            $products = $model->getActive();

            return Response::json([
                'status' => 'success',
                'count' => count($products),
                'data' => $products
            ]);
        } catch (Exception $e) {
            return Response::status(500)->json(['error' => $e->getMessage()]);
        }
    }

    /**
     * Low stock alert
     * GET /api/products/alerts/low-stock
     */
    public function lowStock() {
        try {
            $model = new Product_model();
            $products = $model->getLowStock();

            return Response::json([
                'status' => 'warning',
                'count' => count($products),
                'message' => 'Products with low stock',
                'data' => $products
            ]);
        } catch (Exception $e) {
            return Response::status(500)->json(['error' => $e->getMessage()]);
        }
    }
}
```

### Step 4: Views

File: `app/views/product/index.php`

```html
<!DOCTYPE html>
<html>
  <head>
    <title>Products</title>
    <style>
      table {
        width: 100%;
        border-collapse: collapse;
      }
      th,
      td {
        padding: 10px;
        text-align: left;
        border-bottom: 1px solid #ddd;
      }
      th {
        background-color: #f5f5f5;
      }
      .btn {
        padding: 5px 10px;
        text-decoration: none;
      }
      .btn-primary {
        background: #007bff;
        color: white;
      }
      .btn-danger {
        background: #dc3545;
        color: white;
      }
      .alert {
        padding: 10px;
        margin: 10px 0;
        border-radius: 4px;
      }
      .alert-success {
        background: #d4edda;
        color: #155724;
      }
      .alert-error {
        background: #f8d7da;
        color: #721c24;
      }
    </style>
  </head>
  <body>
    <h1>Products</h1>

    <!-- Messages -->
    <?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success">
      <?= htmlspecialchars($_SESSION['success']) ?>
    </div>
    <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-error">
      <?= htmlspecialchars($_SESSION['error']) ?>
    </div>
    <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- Create button -->
    <a href="/products/create" class="btn btn-primary">+ Add Product</a>

    <!-- Products table -->
    <table>
      <thead>
        <tr>
          <th>ID</th>
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
          <td><?= htmlspecialchars($p['id']) ?></td>
          <td><?= htmlspecialchars($p['name']) ?></td>
          <td>
            Rp
            <?= number_format($p['price'], 2) ?>
          </td>
          <td><?= $p['stock'] ?></td>
          <td>
            <span
              style="background: <?= $p['status'] === 'active' ? '#90EE90' : '#FFB6C6' ?>; padding: 3px 8px; border-radius: 3px;"
            >
              <?= htmlspecialchars($p['status']) ?>
            </span>
          </td>
          <td>
            <a href="/products/<?= $p['id'] ?>/edit" class="btn">Edit</a>
            <a
              href="/products/<?= $p['id'] ?>/delete"
              class="btn btn-danger"
              onclick="return confirm('Delete?')"
              >Delete</a
            >
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <!-- Pagination -->
    <?php if ($pagination['total'] >
    $pagination['limit']): ?>
    <p>
      Page
      <?= $pagination['page'] ?>
      of
      <?= ceil($pagination['total'] / $pagination['limit']) ?>
      | Total:
      <?= $pagination['total'] ?>
      products
    </p>
    <?php endif; ?>
  </body>
</html>
```

File: `app/views/product/form.php`

```html
<!DOCTYPE html>
<html>
<head>
    <title><?= $product ? 'Edit' : 'Create' ?> Product</title>
    <style>
        form { max-width: 600px; }
        input, textarea, select { width: 100%; padding: 8px; margin: 8px 0; }
        button { padding: 10px 20px; background: #007bff; color: white; border: none; cursor: pointer; }
        button:hover { background: #0056b3; }
        .error { color: red; font-size: 12px; }
    </style>
</head>
<body>
    <h1><?= $product ? 'Edit Product' : 'Create Product' ?></h1>

    <form method="POST" action="<?= $product ? "/products/{$product['id']}/update" : '/products/store' ?>">
        <input type="text" name="name" placeholder="Product name" value="<?= $product['name'] ?? '' ?>" required>

        <textarea name="description" placeholder="Description (optional)"><?= $product['description'] ?? '' ?></textarea>

        <input type="number" name="price" placeholder="Price" step="0.01" value="<?= $product['price'] ?? '' ?>" required>

        <input type="number" name="stock" placeholder="Stock quantity" value="<?= $product['stock'] ?? 0 ?>" required>

        <select name="status">
            <option value="active" <?= ($product['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
            <option value="inactive" <?= ($product['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
            <option value="discontinued" <?= ($product['status'] ?? '') === 'discontinued' ? 'selected' : '' ?>>Discontinued</option>
        </select>

        <button type="submit">Save Product</button>
        <a href="/products">Cancel</a>
    </form>
</body>
</html>
```

### Step 5: Seeder dengan Data Dummy

File: `storage/database/seeders/ProductSeeder.php`

```php
<?php

use System\Database\Migration\Seeder;

/**
 * Product Seeder
 *
 * Mengisi tabel products dengan 50 data dummy untuk testing.
 */
class ProductSeeder extends Seeder {
    public function run() {
        // Clear existing
        $this->truncate('products');

        $categories = ['Electronics', 'Books', 'Clothing', 'Home', 'Sports'];

        for ($i = 1; $i <= 50; $i++) {
            $this->insert('products', [
                'name' => "Product {$i} - " . $categories[array_rand($categories)],
                'description' => $this->faker('text', ['length' => 100]),
                'price' => (float) rand(50000, 5000000) / 100,  // Rp 500 - Rp 50,000
                'stock' => rand(0, 100),
                'min_stock' => rand(3, 10),
                'status' => rand(0, 100) > 15 ? 'active' : 'inactive',  // 85% active
                'created_at' => date('Y-m-d H:i:s', strtotime("-{$i} days")),
            ]);
        }

        echo "✓ ProductSeeder completed: 50 products inserted\n";
    }
}
```

Jalankan seeder:

```bash
php sun migrate seed ProductSeeder
```

---

## REST API JSON

Jika ingin built REST API (JSON endpoints), gunakan controller yang return JSON:

```php
class ApiProductController extends Controller {
    /**
     * GET /api/products
     */
    public function index() {
        try {
            $model = new Product_model();
            $products = $model->getActive();

            return Response::json([
                'success' => true,
                'data' => $products,
                'count' => count($products)
            ]);
        } catch (Exception $e) {
            return Response::status(500)->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * POST /api/products
     */
    public function store() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);

            $product = new Product_model($data);
            $result = $product->save();

            if ($result) {
                return Response::status(201)->json([
                    'success' => true,
                    'message' => 'Created',
                    'id' => $result
                ]);
            }

            return Response::status(400)->json([
                'success' => false,
                'error' => 'Creation failed'
            ]);
        } catch (Exception $e) {
            return Response::status(500)->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * GET /api/products/{id}
     */
    public function show($id) {
        try {
            $model = new Product_model();
            $product = $model->find($id);

            if (!$product) {
                return Response::status(404)->json([
                    'success' => false,
                    'error' => 'Not found'
                ]);
            }

            return Response::json([
                'success' => true,
                'data' => $product->toArray()
            ]);
        } catch (Exception $e) {
            return Response::status(500)->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}
```

Test dengan curl:

```bash
# List
curl http://localhost:8000/api/products

# Get one
curl http://localhost:8000/api/products/1

# Create
curl -X POST http://localhost:8000/api/products \
  -H "Content-Type: application/json" \
  -d '{"name":"New Product","price":50000,"stock":10}'
```

---

## Advanced Queries & Pagination

```php
// Complex WHERE
$results = $db->table('products')
    ->where('status', 'active')
    ->where('price', '>', 50000)  // Price > 50k
    ->whereIn('category', ['Electronics', 'Books'])
    ->like('name', 'samsung')
    ->orderBy('price DESC')
    ->limit(20)
    ->get()
    ->getResultArray();

// JOIN example (jika ada category table)
$results = $db->table('products p')
    ->leftJoin('categories c', 'c.id = p.category_id')
    ->where('p.status', 'active')
    ->select('p.*, c.name as category_name')
    ->get()
    ->getResultArray();

// Pagination
$page = 2;
$limit = 20;
$offset = ($page - 1) * $limit;

$all = $db->table('products')
    ->where('status', 'active')
    ->orderBy('name')
    ->limit($limit)
    ->offset($offset)
    ->get()
    ->getResultArray();

$total = $db->table('products')
    ->where('status', 'active')
    ->countAllResults();

$pagination = [
    'data' => $all,
    'page' => $page,
    'per_page' => $limit,
    'total' => $total,
    'last_page' => ceil($total / $limit)
];
```

---

## Validasi & Error Handling

```php
// Validation class (simple)
class Validator {
    private $errors = [];

    public function validate($data, $rules) {
        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;

            if (strpos($rule, 'required') !== false && empty($value)) {
                $this->errors[$field] = "$field is required";
            }

            if (strpos($rule, 'email') !== false && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $this->errors[$field] = "$field must be valid email";
            }

            if (strpos($rule, 'numeric') !== false && !is_numeric($value)) {
                $this->errors[$field] = "$field must be numeric";
            }
        }

        return empty($this->errors);
    }

    public function getErrors() {
        return $this->errors;
    }
}

// Usage in controller
$validator = new Validator();
$valid = $validator->validate(
    $this->request->all(),
    [
        'name' => 'required|min:3',
        'email' => 'required|email',
        'age' => 'required|numeric'
    ]
);

if (!$valid) {
    return Response::status(422)->json([
        'success' => false,
        'errors' => $validator->getErrors()
    ]);
}
```

---

## Events & Lifecycle Hooks

```php
class User_model extends BaseModel {
    public function __construct($attributes = []) {
        parent::__construct($attributes);

        // Hash password sebelum save
        $this->on('before:save', function($model) {
            if (isset($model->attributes['password']) && strlen($model->attributes['password']) < 60) {
                $model->attributes['password'] = password_hash($model->attributes['password'], PASSWORD_BCRYPT);
            }
        });

        // Log changes setelah update
        $this->on('after:update', function($model) {
            Logger::info("User updated: ID={$model->id}", [
                'changed' => $this->getChangedAttributes()
            ]);
        });

        // Clear cache setelah delete
        $this->on('after:delete', function($model) {
            Cache::forget('user_' . $model->id);
        });
    }
}
```

---

## Middleware Custom

```php
// app/middleware/AuthMiddleware.php
class AuthMiddleware implements \BaseMw {
    public function handle(&$request, &$response, $next) {
        // Check token
        $token = $request->header('Authorization');

        if (!$token) {
            return $response->status(401)->json(['error' => 'Unauthorized']);
        }

        // Validate token logic here...

        // Call next middleware
        return call_user_func($next, $request, $response);
    }
}

// Register in Bootstrap.php pipeline
// Pipeline::add(new AuthMiddleware());
```

---

## Query Caching

```php
// Cache single query result
$cacheKey = 'products_active_list';
$cacheTtl = 3600;  // 1 hour

$products = $db->table('products')
    ->where('status', 'active')
    ->cacheTtl($cacheTtl)  // Enable cache for this query
    ->get()
    ->getResultArray();

// Disable cache untuk query ini
$freshData = $db->table('products')
    ->where('status', 'active')
    ->noCache()  // Force fresh query
    ->get()
    ->getResultArray();

// Manual cache clear
$cache = Database::init()->getCache();
if ($cache) {
    $cache->flushTable('products');  // Clear products table cache
}
```

---

**Tips & Best Practices:**

1. ✅ Selalu validasi input user
2. ✅ Gunakan QueryBuilder untuk SQL injection safety
3. ✅ Enable query profiling di development untuk optimize
4. ✅ Test migrations di staging sebelum production
5. ✅ Log errors untuk debugging
6. ✅ Gunakan events untuk side-effects (email, logging, cache)
7. ✅ Paginate besar result sets
8. ✅ Use soft delete jika data harus recoverable

Untuk lebih lanjut, lihat `doc/API.md` dan `doc/USAGE.md`.
