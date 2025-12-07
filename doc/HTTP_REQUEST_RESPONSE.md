# SunVortex — Request & Response Dokumentasi Lengkap

**Files:** `system/Http/Request.php` & `system/Http/Response.php`

Request dan Response adalah dua komponen utama HTTP abstraction layer yang menyederhanakan interaksi dengan klien.

---

## Daftar Isi

1. [Request Object](#request-object)
2. [Response Object](#response-object)
3. [Content Negotiation](#content-negotiation)
4. [Caching & Headers](#caching-headers)
5. [File Operations](#file-operations)
6. [Best Practices](#best-practices)

---

## Request Object

### Singleton Pattern

```php
use System\Http\Request;

// Get singleton instance
$request = Request::getInstance();

// Or via controller
public function myAction() {
    $request = $this->request;  // Auto-injected
}
```

### Input Methods

```php
// Get specific input (from GET, POST, or PUT)
$name = $request->get('name');              // Default: null
$email = $request->post('email');           // Default: null
$age = $request->get('age', 25);            // With default value

// Get all input
$all = $request->all();  // Returns: ['name' => '...', 'email' => '...', ...]

// Check if input exists
if ($request->has('name')) {
    echo "Name provided";
}

// Get multiple inputs
$inputs = $request->get(['name', 'email', 'phone']);
// Returns: ['name' => '...', 'email' => '...', 'phone' => '...']

// Get input with sanitization
$email = $request->get('email', filter: true);
// Removes HTML tags, trims whitespace
```

### Request Metadata

```php
// HTTP Method
$method = $request->method();  // "GET", "POST", "PUT", "DELETE", "PATCH"

// URI Information
$uri = $request->uri();        // Full URI: "/products?page=1"
$path = $request->path();      // Path only: "/products"
$query = $request->query();    // Query string only: "?page=1"

// Domain Information
$host = $request->host();      // Domain: "example.com"
$protocol = $request->protocol();  // "http" or "https"

// Client Information
$ip = $request->ip();          // Client IP address
$userAgent = $request->header('User-Agent');  // Browser info
```

### Headers

```php
// Get specific header
$auth = $request->header('Authorization');       // "Bearer token123"
$contentType = $request->header('Content-Type'); // "application/json"

// Get all headers
$headers = $request->headers();  // ['Authorization' => '...', ...]

// Check header exists
if ($request->header('X-Custom-Header')) {
    echo "Custom header found";
}

// Common headers
$request->header('Accept');              // "application/json"
$request->header('Accept-Encoding');     // "gzip, deflate"
$request->header('Authorization');      // "Bearer ..."
$request->header('Content-Type');        // "application/json"
$request->header('X-Requested-With');    // "XMLHttpRequest"
```

### Request Body & Files

```php
// JSON Body
// Content-Type: application/json
// Body: {"name": "John", "age": 30}

$data = $request->all();  // ['name' => 'John', 'age' => 30]

// Form Data
// Content-Type: application/x-www-form-urlencoded
// Body: name=John&age=30

$name = $request->post('name');  // "John"

// File Upload
// Content-Type: multipart/form-data

if ($request->has('file')) {
    $file = $request->get('file');

    // $file structure:
    // [
    //     'name' => 'invoice.pdf',
    //     'type' => 'application/pdf',
    //     'tmp_name' => '/tmp/php123',
    //     'size' => 102400,
    //     'error' => 0
    // ]
}

// Multiple files
if ($request->has('files')) {
    $files = $request->get('files');  // Array of file arrays
}
```

### User Information (Authentication)

```php
// Get authenticated user (set by auth middleware)
$user = $request->user();

if ($user) {
    echo "User: " . $user['name'];
} else {
    echo "Not authenticated";
}

// Set user (usually by auth middleware)
$request->user = ['id' => 1, 'name' => 'John', 'email' => 'john@example.com'];
```

### Server Information

```php
// Server details
$method = $request->method();              // "GET", "POST", etc
$server = $request->server();              // Raw $_SERVER array
$remote = $request->server()['REMOTE_ADDR'];  // Client IP (alt)
```

### CORS & Middleware Properties

```php
// These are set by middleware:

// CORS
$cors = $request->cors;  // ['origin' => '...', 'allowed' => true]

// Authentication
$auth = $request->auth;  // ['user_id' => 1, 'token' => '...']

// CSRF Token
$csrf = $request->csrf;  // ['token' => '...']

// Rate Limiting
$throttle = $request->throttle;  // ['attempts' => 5, 'limit' => 10]

// File Upload Info
$file = $request->file;  // ['name' => '...', 'size' => ...]
```

---

## Response Object

### Singleton Pattern

```php
use System\Http\Response;

// Get singleton instance
$response = Response::getInstance();

// Or via controller
public function myAction() {
    return $this->response->json(['status' => 'ok']);  // Auto-injected
}
```

### Content Type Methods

```php
// HTML Response
return $response->html('<h1>Hello</h1>');

// JSON Response (most common)
return $response->json(['status' => 'ok', 'data' => [...]], 200);

// XML Response
return $response->xml('<root><item>...</item></root>');

// CSV Response (for downloads)
return $response->csv([
    ['Name', 'Email'],
    ['John', 'john@example.com'],
    ['Jane', 'jane@example.com']
]);

// Plain Text
return $response->plain('OK');

// File Download
return $response->download('/storage/invoice.pdf', 'Invoice.pdf');
```

### Status Code

```php
// Set HTTP status code
return $response
    ->status(200)
    ->json(['status' => 'ok']);

// Common status codes
return $response->status(200)->json([...]);      // OK
return $response->status(201)->json([...]);      // Created
return $response->status(204)->plain('');        // No Content
return $response->status(400)->error('Bad request');    // Bad Request
return $response->status(401)->error('Unauthorized');   // Unauthorized
return $response->status(403)->error('Forbidden');      // Forbidden
return $response->status(404)->error('Not found');      // Not Found
return $response->status(422)->error('Invalid data');   // Unprocessable
return $response->status(500)->error('Server error');   // Internal Server Error
```

### Error & Success Helpers

```php
// Error response (auto status 400)
return $response->error('Something went wrong');

// Error with details
return $response->error('Validation failed', [
    'email' => 'Email is required',
    'phone' => 'Phone must be numeric'
]);

// Error with custom status
return $response
    ->status(404)
    ->error('Product not found');

// Success response (auto status 200)
return $response->success(['id' => 1, 'name' => 'Product']);

// Success with custom status
return $response
    ->status(201)
    ->success(['id' => 1, 'message' => 'Created']);
```

### Headers

```php
// Set single header
return $response
    ->header('X-Custom-Header', 'value')
    ->header('X-API-Version', '1.0')
    ->json([...]);

// Common headers
return $response
    ->header('Content-Disposition', 'attachment; filename="export.csv"')
    ->header('X-RateLimit-Limit', '100')
    ->header('X-RateLimit-Remaining', '99');
```

### Cookies

```php
// Set cookie
return $response
    ->cookie('auth_token', 'abc123', [
        'path' => '/',
        'domain' => 'example.com',
        'expires' => time() + (7 * 24 * 60 * 60),  // 7 days
        'httponly' => true,
        'secure' => true,  // HTTPS only
        'samesite' => 'Lax'
    ])
    ->json(['status' => 'ok']);

// Delete cookie (set expires to past)
return $response
    ->cookie('auth_token', '', ['expires' => time() - 3600])
    ->json(['status' => 'ok']);
```

### Fluent/Chainable API

```php
// Chain multiple methods
return $response
    ->status(201)
    ->header('X-Created-ID', 1)
    ->cookie('session', 'xyz')
    ->json(['status' => 'created', 'id' => 1]);

// Each method returns $this for chaining
$response
    ->status(200)
    ->header('X-Custom', 'value')
    ->json([...]);
```

---

## Content Negotiation

### Accept Header Handling

```php
// Client can request different formats via Accept header:
// Accept: application/json
// Accept: application/xml
// Accept: text/plain

// Respond accordingly:
$data = ['name' => 'John', 'age' => 30];

$accept = $request->header('Accept');

if (strpos($accept, 'application/json') !== false) {
    return $response->json($data);
} elseif (strpos($accept, 'application/xml') !== false) {
    return $response->xml('<root>...</root>');
} else {
    return $response->html('<h1>' . $data['name'] . '</h1>');
}

// Or automatically (response object can handle)
return $response->json($data);  // Responds in requested format
```

### Content Encoding

```php
// Auto-compress response if supported
return $response
    ->compress('gzip')  // or 'deflate'
    ->json(['large' => [...]]); // Large JSON response

// Client receives compressed response, browser decompresses automatically
```

---

## Caching & Headers

### Response Caching

```php
// Cache response for 1 hour
return $response
    ->cacheFor(3600)  // seconds
    ->json(['data' => [...]]);

// Cache for specific amount of time
return $response
    ->cacheFor(60 * 60 * 24)  // 1 day
    ->json([...]);

// Cache for specific time with expiry message
return $response->cacheFor(3600)->json(['expires' => 'in 1 hour']);
```

### ETag & Last-Modified

```php
// Set ETag (for client-side caching validation)
return $response
    ->eTag('abc123xyz')
    ->json(['data' => [...]]);

// Set Last-Modified
$timestamp = strtotime('2025-12-07 10:00:00');
return $response
    ->lastModified($timestamp)
    ->json([...]);

// Client sends If-None-Match header, response returns 304 Not Modified
// Client sends If-Modified-Since header, response returns 304 if not changed
```

### Cache Control Headers

```php
// Public cache (can be cached by CDN)
return $response
    ->header('Cache-Control', 'public, max-age=3600')
    ->json([...]);

// Private cache (only browser cache)
return $response
    ->header('Cache-Control', 'private, max-age=3600')
    ->json([...]);

// No cache
return $response
    ->header('Cache-Control', 'no-cache, no-store')
    ->json([...]);

// Must revalidate
return $response
    ->header('Cache-Control', 'max-age=0, must-revalidate')
    ->json([...]);
```

---

## File Operations

### File Download

```php
// Simple download
return $response->download('/storage/invoice.pdf');

// Download with custom name
return $response->download('/storage/invoice.pdf', 'Invoice-2025.pdf');

// Download with custom MIME type
return $response
    ->download('/path/to/file', 'filename.ext', 'application/octet-stream');

// Large file download (streaming)
return $response
    ->download('/storage/large_video.mp4', 'video.mp4')
    ->header('Content-Transfer-Encoding', 'binary');
```

### Range Requests (Resume Download)

```php
// Supports HTTP Range header for resumable downloads
// Client sends: Range: bytes=1024000-2048000
// Response: 206 Partial Content with requested bytes

return $response
    ->download('/storage/large_file.zip', 'file.zip')
    // Response object handles Range header automatically
```

### File Upload Handling

```php
class FileController extends Controller {

    public function upload() {
        if (!$this->request->has('file')) {
            return $this->response->error(400, 'File required');
        }

        $file = $this->request->get('file');

        // Validate
        if ($file['error'] !== 0) {
            return $this->response->error(400, 'Upload failed');
        }

        if ($file['size'] > 5000000) {  // 5MB limit
            return $this->response->error(422, 'File too large');
        }

        // Move uploaded file
        $destination = '/storage/uploads/' . uniqid() . '_' . $file['name'];
        move_uploaded_file($file['tmp_name'], ROOT . $destination);

        return $this->response->json([
            'file' => $destination,
            'size' => $file['size']
        ]);
    }
}
```

---

## Best Practices

✅ **Do:**

- Always return Response object from controllers
- Chain response methods for cleaner code
- Use meaningful HTTP status codes
- Validate input data before processing
- Set appropriate Content-Type headers
- Cache responses when appropriate
- Compress large responses
- Validate file uploads (type, size, content)
- Handle errors gracefully
- Use JSON for APIs

❌ **Don't:**

- Echo directly in controllers (use response object)
- Assume input is safe (always validate)
- Return bare arrays/objects (use response object)
- Send sensitive data in responses
- Cache sensitive user data
- Allow unlimited file uploads
- Return 200 status for errors
- Expose internal error details to client
- Mix content types in single response

---

## Complete Example

```php
<?php
namespace App\Controllers;

use System\Core\Controller;
use App\Models\Product_model;

class ApiProductController extends Controller {

    protected $productModel;

    public function __construct() {
        parent::__construct();
        $this->productModel = new Product_model();
    }

    // GET /api/products
    public function index() {
        $page = $this->request->get('page', 1);
        $limit = $this->request->get('limit', 10);

        $products = $this->productModel->paginate($page, $limit);

        return $this->response
            ->status(200)
            ->header('X-Page', $page)
            ->header('X-Total', $products['total'])
            ->cacheFor(3600)
            ->json($products);
    }

    // POST /api/products
    public function store() {
        // Validate CSRF (middleware handles this)

        $data = $this->request->all();

        // Validate input
        if (empty($data['name']) || empty($data['price'])) {
            return $this->response
                ->status(422)
                ->json([
                    'errors' => [
                        'name' => 'Product name required',
                        'price' => 'Price required'
                    ]
                ]);
        }

        // Create product
        $product = new Product_model($data);
        $id = $product->save();

        return $this->response
            ->status(201)
            ->header('Location', '/api/products/' . $id)
            ->json([
                'message' => 'Product created',
                'id' => $id,
                'product' => $product->toArray()
            ]);
    }

    // DELETE /api/products/{id}
    public function destroy($id) {
        $product = $this->productModel->find($id);

        if (!$product) {
            return $this->response
                ->status(404)
                ->error('Product not found');
        }

        $product->delete();

        return $this->response
            ->status(200)
            ->json(['message' => 'Product deleted']);
    }
}
```

---

**Untuk Detail Lebih Lanjut:** Lihat `doc/API.md` untuk complete method signature reference.
