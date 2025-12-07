# SunVortex — Controller Dokumentasi Lengkap

**File:** `system/Core/Controller.php`

Controller adalah base class untuk semua HTTP request handlers. Setiap controller application harus extend class ini.

---

## Daftar Isi

1. [Dasar Konsep](#dasar-konsep)
2. [Dependency Injection](#dependency-injection)
3. [Magic Getter](#magic-getter)
4. [Method Routing](#method-routing)
5. [Request & Response](#request-response)
6. [Pattern & Examples](#pattern-examples)

---

## Dasar Konsep

### Struktur Controller

```php
<?php
namespace App\Controllers;

use System\Core\Controller;

class ProductController extends Controller {

    // Dependency akan di-inject otomatis oleh kernel
    public function __construct(Request $request, Response $response) {
        // Konstruktor bersifat opsional jika hanya menggunakan magic getter
        parent::__construct();
    }

    // Action method
    public function index() {
        return "List semua products";
    }

    public function show($id) {
        return "Tampilkan product dengan ID: $id";
    }

    public function create() {
        return "Form tambah product";
    }

    public function store() {
        return "Simpan product baru ke database";
    }

    public function edit($id) {
        return "Form edit product";
    }

    public function update($id) {
        return "Update product di database";
    }

    public function destroy($id) {
        return "Hapus product";
    }
}
```

### Penamaan Konvensi

| Resource   | Controller         | Method                                            |
| ---------- | ------------------ | ------------------------------------------------- |
| Products   | ProductController  | index, show, create, store, edit, update, destroy |
| Users      | UserController     | index, show, create, store, edit, update, destroy |
| Categories | CategoryController | index, show, create, store, edit, update, destroy |

---

## Dependency Injection

### Constructor Injection

```php
use System\Core\Controller;
use System\Http\Request;
use System\Http\Response;
use System\Support\Helpers;

class UserController extends Controller {

    public function __construct(
        protected Request $request,
        protected Response $response,
        protected Helpers $helpers
    ) {
        parent::__construct();
    }

    public function index() {
        // Akses injected dependencies
        $method = $this->request->method();
        $user = $this->request->user();

        return $this->response->json(['user' => $user]);
    }
}
```

### Type Hints & Automatic Resolution

Kernel menggunakan Reflection untuk resolve dependencies secara otomatis:

```php
class OrderController extends Controller {

    public function checkout(Order_model $orderModel, Payment_service $paymentService) {
        // Kernel otomatis instantiate Order_model dan Payment_service
        // Supports classes dari:
        // - system/Core/
        // - system/Http/
        // - app/models/
        // - app/services/ (jika exist)
    }
}
```

### Magic Getter

Jika tidak menggunakan constructor injection, gunakan magic getter:

```php
class ProductController extends Controller {

    public function index() {
        // Via magic getter (implicit)
        $request = $this->request;    // Get Request singleton
        $response = $this->response;  // Get Response singleton

        $method = $request->method();
        $input = $request->all();

        return $response->json(['products' => []]);
    }
}
```

**Available via Magic Getter:**

- `$this->request` → Request singleton
- `$this->response` → Response singleton
- `$this->db` → Database singleton
- Atau method name jika ada property setter

---

## Method Routing

### URI Mapping

Kernel otomatis memetakan URI ke controller method berdasarkan reflection:

```
GET /products
  ↓
ProductController::index()

GET /products/123
  ↓
ProductController::show(123)

POST /products
  ↓
ProductController::store()

GET /products/123/edit
  ↓
ProductController::edit(123)

POST /products/123
  ↓
ProductController::update(123)

DELETE /products/123
  ↓
ProductController::destroy(123)
```

### Parameter Binding

```php
class ProductController extends Controller {

    // Automatic parameter resolution
    public function show($id) {
        // $id akan diset dari URL segment
        // GET /products/123 → $id = "123"
    }

    public function update($id, $slug = null) {
        // Multiple parameters
        // Parameters dengan default value bersifat optional
    }

    public function complexAction($id, Product_model $model) {
        // Mix parameter dan dependency injection
        // $id dari URL, $model otomatis di-resolve
    }
}
```

### Type Coercion

Kernel otomatis mengkonversi parameter berdasarkan type hint:

```php
public function search($keyword, $limit = 10, $active = true) {
    // Query: /search?keyword=laptop&limit=50&active=0
    //
    // $keyword = "laptop" (string)
    // $limit = 50 (int, auto-converted dari "50")
    // $active = false (bool, auto-converted dari "0")
}
```

---

## Request & Response

### Request Object

```php
class ProductController extends Controller {

    public function store() {
        // Get request input
        $name = $this->request->get('name');           // From query string
        $price = $this->request->post('price');        // From POST body
        $all = $this->request->all();                  // All input

        // Check input existence
        if (!$this->request->has('name')) {
            return $this->response->error(400, 'Name required');
        }

        // Get request metadata
        $method = $this->request->method();            // GET, POST, etc
        $uri = $this->request->uri();                  // Full URI
        $path = $this->request->path();                // Path only
        $ip = $this->request->ip();                    // Client IP

        // Authentication
        $user = $this->request->user();                // From auth middleware

        // Headers
        $authorization = $this->request->header('Authorization');
        $contentType = $this->request->header('Content-Type');

        // File upload
        if ($this->request->has('file')) {
            $file = $this->request->get('file');
            // File object: ['name', 'type', 'tmp_name', 'size', 'error']
        }
    }
}
```

### Response Object

```php
class ProductController extends Controller {

    public function index() {
        $products = [];

        // HTML Response
        return $this->response->html('<h1>Products</h1>');

        // JSON Response
        return $this->response->json(['products' => $products]);

        // XML Response
        return $this->response->xml('<products>...</products>');

        // CSV Response
        return $this->response->csv($products);

        // Plain Text
        return $this->response->plain('OK');

        // File Download
        return $this->response->download('/storage/file.pdf', 'Invoice.pdf');
    }

    public function error() {
        // Error Response
        return $this->response
            ->status(404)
            ->error('Product not found');
    }

    public function success() {
        // Success Response
        return $this->response
            ->status(201)
            ->success(['id' => 1, 'name' => 'Product']);
    }
}
```

### Chaining Methods

```php
// Response fluent API
return $this->response
    ->status(201)
    ->header('X-Custom-Header', 'Value')
    ->cookie('auth_token', 'token123', ['path' => '/', 'httponly' => true])
    ->cacheFor(3600)  // Cache for 1 hour
    ->json(['status' => 'created', 'id' => 1]);
```

---

## Pattern & Examples

### CRUD Pattern

```php
<?php
namespace App\Controllers;

use System\Core\Controller;
use App\Models\Product_model;

class ProductController extends Controller {

    protected $productModel;

    public function __construct() {
        parent::__construct();
        $this->productModel = new Product_model();
    }

    // GET /products
    public function index() {
        $page = $this->request->get('page', 1);
        $limit = $this->request->get('limit', 10);

        $products = $this->productModel->paginate($page, $limit);

        return $this->response->json($products);
    }

    // GET /products/{id}
    public function show($id) {
        $product = $this->productModel->find($id);

        if (!$product) {
            return $this->response
                ->status(404)
                ->error('Product not found');
        }

        return $this->response->json($product->toArray());
    }

    // GET /products/create
    public function create() {
        // Return form HTML
        return view('products.create');
    }

    // POST /products
    public function store() {
        $input = $this->request->all();

        // Validation (simple example)
        if (!isset($input['name'], $input['price'])) {
            return $this->response
                ->status(400)
                ->error('Name and price required');
        }

        // Save to database
        $product = new Product_model($input);
        $id = $product->save();

        return $this->response
            ->status(201)
            ->json(['id' => $id, 'message' => 'Created']);
    }

    // GET /products/{id}/edit
    public function edit($id) {
        $product = $this->productModel->find($id);

        if (!$product) {
            return $this->response->status(404)->error('Not found');
        }

        // Return edit form HTML with product data
        return view('products.edit', ['product' => $product]);
    }

    // POST /products/{id} atau PUT /products/{id}
    public function update($id) {
        $product = $this->productModel->find($id);

        if (!$product) {
            return $this->response->status(404)->error('Not found');
        }

        $input = $this->request->all();
        $product->fill($input);
        $product->save();

        return $this->response->json(['message' => 'Updated']);
    }

    // DELETE /products/{id}
    public function destroy($id) {
        $product = $this->productModel->find($id);

        if (!$product) {
            return $this->response->status(404)->error('Not found');
        }

        $product->delete();

        return $this->response->json(['message' => 'Deleted']);
    }
}
```

### API Controller

```php
<?php
namespace App\Controllers\Api;

use System\Core\Controller;
use App\Models\User_model;

class UserApiController extends Controller {

    public function __construct() {
        parent::__construct();
    }

    // GET /api/users
    public function getUsers() {
        // Check authentication
        $user = $this->request->user();
        if (!$user) {
            return $this->response
                ->status(401)
                ->error('Unauthorized');
        }

        $model = new User_model();
        $users = $model->query()
            ->select('id', 'name', 'email')
            ->where('status', 'active')
            ->getResultArray();

        return $this->response->json(['users' => $users]);
    }

    // GET /api/users/{id}
    public function getUser($id) {
        $model = new User_model();
        $user = $model->find($id);

        if (!$user) {
            return $this->response->status(404)->error('User not found');
        }

        return $this->response->json($user->toArray());
    }

    // POST /api/users
    public function createUser() {
        // Validate input
        $data = $this->request->all();

        $errors = [];
        if (empty($data['name'])) $errors[] = 'Name required';
        if (empty($data['email'])) $errors[] = 'Email required';

        if ($errors) {
            return $this->response
                ->status(422)
                ->error('Validation failed', $errors);
        }

        // Create user
        $user = new User_model($data);
        $id = $user->save();

        return $this->response
            ->status(201)
            ->json(['id' => $id, 'message' => 'User created']);
    }

    // PUT /api/users/{id}
    public function updateUser($id) {
        $model = new User_model();
        $user = $model->find($id);

        if (!$user) {
            return $this->response->status(404)->error('User not found');
        }

        $user->fill($this->request->all());
        $user->save();

        return $this->response->json(['message' => 'User updated']);
    }

    // DELETE /api/users/{id}
    public function deleteUser($id) {
        $model = new User_model();
        $user = $model->find($id);

        if (!$user) {
            return $this->response->status(404)->error('User not found');
        }

        $user->delete();

        return $this->response->json(['message' => 'User deleted']);
    }
}
```

### Form Controller dengan Middleware

```php
<?php
namespace App\Controllers;

use System\Core\Controller;
use App\Models\Product_model;

class FormController extends Controller {

    // GET /contact
    public function contactForm() {
        return view('contact');
    }

    // POST /contact
    public function submitContact() {
        // CSRF middleware akan validate token otomatis

        $data = $this->request->all();

        // Validate
        if (strlen($data['message'] ?? '') < 10) {
            return $this->response->error(422, 'Message too short');
        }

        // Process (send email, save to DB, etc)
        // ...

        return $this->response->json(['message' => 'Message sent']);
    }
}
```

---

## Best Practices

✅ **Do:**

- Gunakan meaningful action names (index, show, store, update, destroy)
- Return response object dari setiap action
- Leverage dependency injection untuk model/service
- Validate input sebelum processing
- Use type hints untuk auto-resolution
- Chain response methods untuk cleaner code
- Keep business logic di model, bukan controller

❌ **Don't:**

- Simpan business logic di controller (move ke model)
- Query database langsung tanpa model
- Assume input aman (always validate)
- Return bare arrays/objects (use response object)
- Create controller methods yang terlalu panjang
- Mix HTML rendering dengan JSON responses tanpa separation

---

**Untuk Detail Lebih Lanjut:** Lihat `doc/API.md` untuk Request/Response method signatures lengkap.
