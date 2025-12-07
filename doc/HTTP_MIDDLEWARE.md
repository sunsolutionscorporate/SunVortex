# SunVortex — Middleware Dokumentasi Lengkap

**Location:** `system/Http/Middleware/`

Middleware adalah layer yang memproses request sebelum mencapai controller dan response sebelum dikirim ke klien. SunVortex menyediakan 6 built-in middleware yang dapat dikonfigurasi.

---

## Daftar Isi

1. [Konsep Dasar](#konsep-dasar)
2. [Middleware Order](#middleware-order)
3. [CORS Middleware](#cors-middleware)
4. [CSRF Middleware](#csrf-middleware)
5. [Auth Middleware](#auth-middleware)
6. [Throttle Middleware](#throttle-middleware)
7. [PageCache Middleware](#pagecache-middleware)
8. [Route Middleware](#route-middleware)
9. [Custom Middleware](#custom-middleware)

---

## Konsep Dasar

### Pipeline Architecture

Middleware dijalankan dalam pipeline (urutan tertentu):

```
Request
  ↓
[Middleware 1] → Process request
  ↓
[Middleware 2] → Modify request
  ↓
...
  ↓
[Controller Action] → Generate response
  ↓
[Middleware n] → Process response
  ↓
Response
```

### Middleware Interface

```php
use System\Interfaces\BaseMw;
use System\Http\Request;

class CustomMiddleware implements BaseMw {

    public function handle(Request $request, callable $next) {
        // Pre-processing (sebelum controller)

        // Call next middleware/controller
        $response = $next($request);

        // Post-processing (sebelum response dikirim)

        return $response;
    }
}
```

---

## Middleware Order

### Default Order (from `system/Bootstrap.php`)

```
1. CORS Middleware     (Handle cross-origin requests)
2. PageCache Middleware (Check cache, serve if exists)
3. Throttle Middleware (Rate limiting)
4. Auth Middleware     (JWT authentication)
5. Route Middleware    (Route resolution)
6. CSRF Middleware     (CSRF token validation)
```

### Custom Order

Ubah order di `system/Bootstrap.php`:

```php
protected function bootstrapPipeline() {
    $middleware = [
        'Cors_Ms',
        'PageCache_Ms',
        'Throttle_Ms',
        'Auth_Ms',
        'Route_Ms',
        'Csrf_Ms',
        // ... add custom middleware here
    ];

    // Load and register middleware
    foreach ($middleware as $mw) {
        $class = "\\App\\Middleware\\{$mw}";
        if (!class_exists($class)) {
            $class = "\\System\\Http\\Middleware\\{$mw}";
        }
        $this->pipeline->through(new $class());
    }
}
```

---

## CORS Middleware

**File:** `system/Http/Middleware/Cors_Ms.php`

CORS (Cross-Origin Resource Sharing) middleware mengatur akses dari domain berbeda.

### Konfigurasi

```php
// .env configuration
CORS_ORIGINS=http://localhost:3000,https://app.example.com
CORS_METHODS=GET,POST,PUT,DELETE,PATCH,OPTIONS
CORS_HEADERS=Content-Type,Authorization,X-Requested-With
CORS_CREDENTIALS=true
CORS_CACHE=86400
```

### How It Works

```php
// Browser sends preflight request (OPTIONS)
// OPTIONS /api/products
// Origin: http://localhost:3000
// Access-Control-Request-Method: POST

// Middleware validates origin:
// ✅ If origin in CORS_ORIGINS
// ↓
// Respond with:
// Access-Control-Allow-Origin: http://localhost:3000
// Access-Control-Allow-Methods: GET,POST,PUT,DELETE
// Access-Control-Allow-Headers: Content-Type,Authorization
// Access-Control-Max-Age: 86400
// ↓
// Browser proceeds with actual request
```

### Usage Example

```php
// Frontend code
fetch('http://api.example.com/api/products', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer token'
    },
    body: JSON.stringify({name: 'Product'})
});

// Browser sends preflight OPTIONS
// CORS middleware validates
// ✅ If allowed → browser sends actual POST request
// ❌ If not allowed → browser blocks request
```

### Environment Variables

| Variable         | Default  | Description                       |
| ---------------- | -------- | --------------------------------- |
| CORS_ORIGINS     | \*       | Allowed origins (comma-separated) |
| CORS_METHODS     | GET,POST | Allowed HTTP methods              |
| CORS_HEADERS     | \*       | Allowed headers                   |
| CORS_CREDENTIALS | false    | Allow credentials (cookies, auth) |
| CORS_CACHE       | 3600     | Preflight cache time (seconds)    |

---

## CSRF Middleware

**File:** `system/Http/Middleware/Csrf_Ms.php`

CSRF (Cross-Site Request Forgery) middleware melindungi dari serangan CSRF dengan token validation.

### Setup

```html
<!-- In form, include CSRF token hidden input -->
<form method="POST" action="/products">
  <input type="hidden" name="_token" value="<?php echo csrf_token(); ?>" />
  <input type="text" name="name" placeholder="Product name" />
  <button type="submit">Create</button>
</form>
```

### How It Works

```php
// 1. GET /products/create
// Middleware generates token
// Token stored in session: 'csrf_token_12345'
// HTML form includes: <input name="_token" value="12345">

// 2. POST /products
// Form submission includes token
// Middleware validates:
// ✅ Token exists in $_POST
// ✅ Token matches session token
// ✅ Token not expired
// ↓
// ✅ Request proceeds to controller
```

### Configuration

```php
// .env
CSRF_ENABLED=true
CSRF_EXPIRY=3600  // 1 hour
CSRF_COOKIE_NAME=csrf_token
CSRF_HEADER_NAME=X-CSRF-Token
```

### Usage in AJAX

```javascript
// Get token from meta tag or cookie
const token = document.querySelector('meta[name="csrf-token"]')?.content;

fetch("/api/products", {
  method: "POST",
  headers: {
    "X-CSRF-Token": token,
    "Content-Type": "application/json",
  },
  body: JSON.stringify({ name: "Product" }),
});
```

### Helper Function

```php
// Generate fresh token
$token = csrf_token();

// Verify token in controller
class ProductController extends Controller {
    public function store() {
        // Middleware automatically validates CSRF
        // If invalid, returns 403 Forbidden

        // Only reach here if CSRF is valid
        $data = $this->request->all();
        // ... process form
    }
}
```

---

## Auth Middleware

**File:** `system/Http/Middleware/Auth_Ms.php`

Auth middleware melakukan JWT (JSON Web Token) authentication dan set user info di request object.

### Setup

```php
// .env
JWT_ALGORITHM=HS256
JWT_SECRET=your-secret-key-here
JWT_EXPIRY=3600

// Or use RS256 dengan keys
JWT_PUBLIC_KEY=/path/to/public.key
JWT_PRIVATE_KEY=/path/to/private.key
```

### How It Works

```php
// 1. Client sends request with Authorization header
// GET /api/products
// Authorization: Bearer eyJhbGc...token...

// 2. Middleware extracts token
// Validates signature using JWT_SECRET
// Validates expiry
// Decodes payload
// Sets $request->user = decoded payload

// 3. Controller can access:
$user = $this->request->user();  // ['id' => 1, 'name' => 'John', ...]
```

### Login Flow

```php
class AuthController extends Controller {

    public function login() {
        $email = $this->request->post('email');
        $password = $this->request->post('password');

        // Validate credentials
        $user = User_model::findBy('email', $email);
        if (!$user || !password_verify($password, $user->password)) {
            return $this->response->error(401, 'Invalid credentials');
        }

        // Generate JWT token
        $token = jwt_encode([
            'id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'iat' => time(),
            'exp' => time() + (env('JWT_EXPIRY') ?: 3600)
        ]);

        return $this->response->json([
            'token' => $token,
            'user' => $user->toArray()
        ]);
    }
}
```

### Usage in API

```php
class ApiProductController extends Controller {

    public function index() {
        // Auth middleware sets user automatically
        $user = $this->request->user();

        if (!$user) {
            return $this->response->status(401)->error('Unauthorized');
        }

        // User authenticated, proceed
        $products = Product_model::query()
            ->where('user_id', $user['id'])
            ->getResultArray();

        return $this->response->json(['products' => $products]);
    }
}
```

### Token Payload

```php
// Typical JWT payload structure
[
    'id' => 1,
    'email' => 'user@example.com',
    'name' => 'John Doe',
    'role' => 'admin',  // Optional
    'iat' => 1702050000,    // Issued at
    'exp' => 1702053600,    // Expires at (1 hour later)
]
```

---

## Throttle Middleware

**File:** `system/Http/Middleware/Throttle_Ms.php`

Throttle middleware membatasi jumlah request per identifier (IP, user ID, custom) dalam periode tertentu.

### Configuration

```php
// .env
THROTTLE_ENABLED=true
THROTTLE_LIMIT=100          // Max requests
THROTTLE_WINDOW=3600        // Time window (seconds)
THROTTLE_CACHE_DRIVER=file  // file or redis
```

### How It Works

```php
// 1. Request comes in with identifier (default: IP)
// 127.0.0.1 → 5 requests in last 3600 seconds

// 2. Middleware checks:
// ✅ Attempts < Limit
// ↓
// ✅ Allow request, increment counter

// 3. If limit exceeded:
// ↓
// ❌ Return 429 Too Many Requests
// Set Retry-After header
```

### Custom Throttle

```php
class ApiProductController extends Controller {

    public function search() {
        // Apply custom throttle rule: 10 requests per 60 seconds per IP
        $identifier = $this->request->ip();
        $throttle = new ThrottleMiddleware();

        if (!$throttle->isAllowed($identifier, 10, 60)) {
            return $this->response
                ->status(429)
                ->header('Retry-After', '60')
                ->error('Too many requests');
        }

        // Proceed with search
        $results = Product_model::search(...);
        return $this->response->json($results);
    }
}
```

### Rate Limit Headers

```php
// Response headers
X-RateLimit-Limit: 100          // Max requests
X-RateLimit-Remaining: 95       // Remaining requests
X-RateLimit-Reset: 1702053600   // Unix timestamp when limit resets
Retry-After: 3600               // Seconds until retry (if exceeded)
```

---

## PageCache Middleware

**File:** `system/Http/Middleware/PageCache_Ms.php`

PageCache middleware menyimpan dan melayani response dari cache untuk meningkatkan performance.

### Configuration

```php
// .env
CACHE_DRIVER=file       // file or redis
CACHE_TTL=3600          // Default TTL (seconds)
PAGE_CACHE_ENABLED=true
PAGE_CACHE_EXCLUDE=api.*,admin.*  // Exclude patterns
```

### How It Works

```php
// 1. GET /products
// Middleware checks cache key: "page_cache:GET:/products"
// ✅ If exists and not expired
// ↓
// Return cached response immediately (skip controller)

// 2. If not cached:
// ↓
// Controller executes, generates response
// Middleware stores response in cache
// Returns to client

// 3. Subsequent requests within TTL serve from cache
```

### Enable Caching in Controller

```php
class ProductController extends Controller {

    public function index() {
        // Cache this response for 1 hour
        return $this->response
            ->cacheFor(3600)
            ->json(['products' => [...]]);
    }

    public function show($id) {
        // No cache for dynamic content
        $product = Product_model::find($id);
        return $this->response->json($product->toArray());
    }
}
```

### Cache Key Format

```php
// page_cache:METHOD:PATH:QUERY_PARAMS
// page_cache:GET:/products?page=1
// page_cache:GET:/products/123
// page_cache:POST:/api/products  // Usually not cached
```

### Invalidate Cache

```php
class ProductController extends Controller {

    public function store() {
        // Create product
        $product = new Product_model($this->request->all());
        $product->save();

        // Invalidate related cache
        $cache = Database::init()->getCache();
        $cache->forget('page_cache:GET:/products');  // Invalidate list

        return $this->response
            ->status(201)
            ->json(['id' => $product->id]);
    }
}
```

---

## Route Middleware

**File:** `system/Http/Middleware/Route_Ms.php`

Route middleware menyelesaikan URL routing dan mengubah controller/method/parameter sesuai rute yang didefinisikan.

### How It Works

```php
// Bootstrap membaca route definition (fallback ke reflection)
// Middleware melakukan:
// 1. Parse URI path
// 2. Extract controller & method
// 3. Extract parameters
// 4. Set di $request object
// 5. Controller.php dibuat dan dijalankan

// Example routing:
// GET /products/123
// ↓
// Controller: ProductController
// Method: show
// Params: [123]
```

### Routing Convention

SunVortex menggunakan **reflection-based routing** (tidak perlu route file):

```
GET  /products              → ProductController::index()
GET  /products/{id}         → ProductController::show(id)
GET  /products/create       → ProductController::create()
POST /products              → ProductController::store()
GET  /products/{id}/edit    → ProductController::edit(id)
POST /products/{id}         → ProductController::update(id)
DELETE /products/{id}       → ProductController::destroy(id)
```

### Change Route Dynamically

```php
class CustomMiddleware implements BaseMw {

    public function handle(Request $request, callable $next) {
        // Change controller
        $request->controller = 'CustomController';

        // Change method
        $request->method = 'customMethod';

        // Change parameters
        $request->params = ['param1', 'param2'];

        // Continue to next middleware with modified routing
        return $next($request);
    }
}
```

---

## Custom Middleware

### Create Custom Middleware

```php
<?php
namespace App\Middleware;

use System\Interfaces\BaseMw;
use System\Http\Request;

class ApiVersionMiddleware implements BaseMw {

    public function handle(Request $request, callable $next) {
        // Pre-processing: validate API version
        $version = $request->header('X-API-Version');

        if (!$version || !in_array($version, ['1.0', '2.0'])) {
            return Response::getInstance()
                ->status(400)
                ->error('API version not specified or invalid');
        }

        // Set version in request
        $request->api_version = $version;

        // Call next middleware/controller
        $response = $next($request);

        // Post-processing: add version header to response
        $response->header('X-API-Version', $version);

        return $response;
    }
}
```

### Register Custom Middleware

```php
// In system/Bootstrap.php, add to pipeline:
$middleware = [
    'Cors_Ms',
    'PageCache_Ms',
    'ApiVersionMiddleware',  // Custom middleware
    'Throttle_Ms',
    'Auth_Ms',
    'Route_Ms',
    'Csrf_Ms',
];
```

### Conditional Middleware

```php
// Apply middleware hanya untuk certain routes
class ApiAuthMiddleware implements BaseMw {

    public function handle(Request $request, callable $next) {
        // Only check auth untuk /api/* routes
        if (strpos($request->path(), '/api') === 0) {
            $user = $request->user();
            if (!$user) {
                return Response::getInstance()
                    ->status(401)
                    ->error('Unauthorized');
            }
        }

        return $next($request);
    }
}
```

---

## Best Practices

✅ **Do:**

- Keep middleware focused (single responsibility)
- Validate input early in pipeline
- Set meaningful response status codes
- Log security-relevant events
- Cache appropriately
- Use env variables for configuration
- Test middleware separately
- Handle errors gracefully

❌ **Don't:**

- Complex business logic in middleware
- Modify request data without validation
- Skip security checks
- Store sensitive data in cache
- Make database queries in middleware (performance)
- Hardcode configuration values
- Fail silently (always log errors)

---

**Untuk Detail Lebih Lanjut:** Lihat `doc/API.md` untuk complete middleware API reference.
