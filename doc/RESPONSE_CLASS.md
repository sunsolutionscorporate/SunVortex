# Response Class - HTTP Response Handler

## Daftar Isi

1. [Pengenalan](#pengenalan)
2. [Instalansi & Setup](#instalansi--setup)
3. [Core Concepts](#core-concepts)
4. [Quick Response Methods](#quick-response-methods)
5. [Content Types](#content-types)
6. [Cache Control](#cache-control)
7. [Cookie Management](#cookie-management)
8. [Compression](#compression)
9. [Security Headers](#security-headers)
10. [Middleware Pipeline](#middleware-pipeline)
11. [Best Practices](#best-practices)
12. [Examples](#examples)
13. [Troubleshooting](#troubleshooting)

---

## Pengenalan

Response class adalah HTTP response handler yang fleksibel, chainable, dan high-performance. Dirancang untuk menangani berbagai tipe response (HTML, JSON, XML, CSV, File streaming) dengan fitur-fitur modern termasuk caching, compression, security headers, dan middleware pipeline.

### Fitur Utama

- ✅ Multiple content types (HTML, JSON, XML, CSV, Plain, Custom)
- ✅ File streaming dengan HTTP Range support
- ✅ Smart caching dengan ETag & Last-Modified
- ✅ Compression support (gzip, deflate, auto-detect)
- ✅ Security headers otomatis
- ✅ Cookie management dengan opsi lengkap
- ✅ Middleware pipeline
- ✅ Error handling & JSON format
- ✅ Fluent/chainable interface

### Response Type

Response mengunakan singleton pattern dan fluent interface untuk kemudahan chaining method.

---

## Instalansi & Setup

### Inisialisasi Response

```php
// Get singleton instance
$response = Response::getInstance();

// Atau langsung dari static method
$response = Response::json(['status' => 'ok']);
```

### Reset Singleton (untuk testing/CLI)

```php
Response::reset();
$response = Response::getInstance(); // Fresh instance
```

---

## Core Concepts

### Singleton Pattern

Response menggunakan singleton pattern - hanya satu instance per request:

```php
$r1 = Response::getInstance();
$r2 = Response::getInstance();
// $r1 dan $r2 adalah object yang sama
```

### Fluent Interface (Method Chaining)

Semua method mengembalikan `$this` untuk memungkinkan chaining:

```php
Response::success($data)
    ->cacheFor(3600)
    ->withETag('abc123')
    ->compress('gzip')
    ->send();
```

### Response Sending

```php
// Method send() mengirim response ke client
$response->send(); // Trigger exit

// Hanya untuk testing/mocking
$snapshot = $response->exportForCache();
```

---

## Quick Response Methods

### success(data, code, message)

Mengirim successful response dengan HTTP 200 (atau custom code):

```php
// Signature
public static function success(
    $data,
    int $code = 200,
    string $message = 'Success'
): Response

// Contoh
Response::success([
    'id' => 1,
    'name' => 'John Doe',
    'email' => 'john@example.com'
])
->send();

// Output JSON
{
    "status": "success",
    "message": "Success",
    "data": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com"
    },
    "meta": {
        "timestamp": 1234567890,
        "request_id": "req_123"
    }
}

// Dengan custom code dan message
Response::success(['user_id' => 123], 201, 'User created')
    ->send();
```

### error(message, code, errors)

Mengirim error response dengan custom HTTP code:

```php
// Signature
public static function error(
    string $message,
    int $code = 400,
    $errors = null
): Response

// Contoh
Response::error('Invalid input', 400, [
    'email' => 'Email is required',
    'password' => 'Password min 8 characters'
])
->send();

// Output JSON
{
    "status": "error",
    "message": "Invalid input",
    "data": null,
    "meta": { ... },
    "errors": {
        "email": "Email is required",
        "password": "Password min 8 characters"
    }
}
```

### validationError(errors, message)

Shortcut untuk validation error (422 Unprocessable Entity):

```php
// Signature
public static function validationError(
    $errors,
    string $message = 'Validation failed'
): Response

// Contoh
$errors = Validator::validate($input);
if (!empty($errors)) {
    Response::validationError($errors, 'Validation failed')
        ->send();
}

// Menghasilkan 422 status code
```

### notFound(message)

Shortcut untuk 404 Not Found response:

```php
// Signature
public static function notFound(
    string $message = 'Resource not found'
): Response

// Contoh
if (!$user) {
    Response::notFound('User tidak ditemukan')
        ->send();
}
```

### unauthorized(message)

Shortcut untuk 401 Unauthorized response:

```php
// Signature
public static function unauthorized(
    string $message = 'Unauthorized'
): Response

// Contoh
if (!Auth::check()) {
    Response::unauthorized('Login required')
        ->send();
}
```

### forbidden(message)

Shortcut untuk 403 Forbidden response:

```php
// Signature
public static function forbidden(
    string $message = 'Forbidden'
): Response

// Contoh
if (!Auth::can('delete_user')) {
    Response::forbidden('Anda tidak memiliki permission')
        ->send();
}
```

### serverError(message, errors)

Shortcut untuk 500 Internal Server Error response:

```php
// Signature
public static function serverError(
    string $message = 'Internal server error',
    $errors = null
): Response

// Contoh
try {
    $result = Database::query(...);
} catch (Exception $e) {
    Response::serverError('Database error', [
        'exception' => $e->getMessage()
    ])
    ->send();
}
```

---

## Content Types

Response mendukung berbagai content type yang bisa di-chain:

### json(data)

Mengirim JSON response:

```php
// Static factory method
Response::json(['status' => 'ok', 'code' => 200])
    ->cacheFor(3600)
    ->send();
```

### html(content)

Mengirim HTML response:

```php
Response::html('<h1>Hello World</h1>')
    ->send();

// Dengan View object
$view = new View('users.profile', ['name' => 'John']);
Response::html($view)->send();
```

### xml(content)

Mengirim XML response:

```php
$xml = '<?xml version="1.0"?><root><status>ok</status></root>';
Response::xml($xml)->send();
```

### csv(content)

Mengirim CSV response:

```php
$csv = "name,email\nJohn,john@example.com\nJane,jane@example.com";
Response::csv($csv)
    ->header('Content-Disposition', 'attachment; filename="users.csv"')
    ->send();
```

### plain(content)

Mengirim plain text response:

```php
Response::plain('This is plain text')
    ->send();
```

### download(filePath)

Mengirim file untuk di-download:

```php
Response::download('/path/to/document.pdf')
    ->header('Content-Disposition', 'attachment; filename="document.pdf"')
    ->send();
```

### stream(filePath)

Mengirim file dengan streaming (support HTTP Range untuk video/audio):

```php
Response::stream('/path/to/video.mp4')
    ->compress(false) // Jangan compress streaming
    ->send();
```

---

## Cache Control

### cacheFor(seconds)

Set cache control public dengan durasi tertentu:

```php
// Signature
public function cacheFor(?int $seconds = 3600): Response

// Contoh
Response::json(['users' => $users])
    ->cacheFor(3600) // Cache 1 jam
    ->send();

// Header yang dihasilkan
// Cache-Control: public, max-age=3600

// Without parameter - default 1 jam
Response::json(['data' => $data])
    ->cacheFor() // 3600 detik
    ->send();
```

### noCache()

Disable caching dengan revalidation:

```php
// Signature
public function noCache(): Response

// Contoh
Response::json(['user' => $user])
    ->noCache()
    ->send();

// Header yang dihasilkan
// Cache-Control: no-cache, must-revalidate
// Pragma: no-cache
```

### privateCacheFor(seconds)

Set private cache (hanya untuk single user/client):

```php
// Signature
public function privateCacheFor(?int $seconds = 3600): Response

// Contoh
Response::json(['profile' => Auth::user()])
    ->privateCacheFor(1800) // Cache 30 menit untuk client
    ->send();

// Header yang dihasilkan
// Cache-Control: private, max-age=1800
```

### withETag(etag)

Set ETag untuk cache validation:

```php
// Signature
public function withETag(string $etag): Response

// Contoh
$data = ['users' => User::all()];
$etag = md5(json_encode($data));

Response::json($data)
    ->withETag($etag)
    ->cacheFor(3600)
    ->send();

// Client bisa melakukan If-None-Match untuk conditional request
// Jika ETag match, server bisa respond 304 Not Modified
```

### withLastModified(timestamp)

Set Last-Modified header untuk cache validation:

```php
// Signature
public function withLastModified($timestamp): Response

// Contoh
$lastModified = $user->updated_at; // Unix timestamp

Response::json(['user' => $user])
    ->withLastModified($lastModified)
    ->cacheFor(3600)
    ->send();

// Header yang dihasilkan
// Last-Modified: Wed, 21 Oct 2024 07:28:00 GMT

// Client bisa melakukan If-Modified-Since untuk conditional request
```

### Cache Strategy Recommendations

**Public Data (Blog Posts, Public APIs)**

```php
Response::json($posts)
    ->cacheFor(86400) // 24 hours
    ->withETag(md5(json_encode($posts)))
    ->send();
```

**Dynamic Data (User Profile)**

```php
Response::json(['profile' => $user])
    ->privateCacheFor(3600) // 1 hour
    ->send();
```

**Real-time Data (Live Updates)**

```php
Response::json(['status' => $status])
    ->noCache()
    ->send();
```

---

## Cookie Management

### cookie(name, value, expire, options)

Set cookie dengan options lengkap:

```php
// Signature
public function cookie(
    string $name,
    string $value,
    int $expire = 0,
    array $options = []
): Response

// Contoh Basic
Response::success(['logged_in' => true])
    ->cookie('session_id', 'xyz789')
    ->send();

// Contoh dengan options lengkap
Response::success(['logged_in' => true])
    ->cookie('session', 'abc123def456', time() + (86400 * 7), [
        'path' => '/',
        'domain' => '.example.com',
        'secure' => true,      // HTTPS only
        'httponly' => true,    // JavaScript tidak bisa access
        'samesite' => 'Strict' // CSRF protection
    ])
    ->send();

// Multiple cookies
Response::success(['status' => 'ok'])
    ->cookie('session', 'abc123')
    ->cookie('preferences', 'dark_mode')
    ->send();
```

### Cookie Options

| Option     | Type   | Default | Deskripsi                    |
| ---------- | ------ | ------- | ---------------------------- |
| `path`     | string | `/`     | Cookie path                  |
| `domain`   | string | ``      | Cookie domain                |
| `secure`   | bool   | false   | HTTPS only                   |
| `httponly` | bool   | true    | JavaScript tidak bisa access |
| `samesite` | string | `Lax`   | `Strict`, `Lax`, atau `None` |

### Security Best Practices

```php
// Untuk session cookie
Response::success($data)
    ->cookie('PHPSESSID', session_id(), 0, [
        'path' => '/',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Strict'
    ])
    ->send();

// Untuk remember-me cookie
Response::success($data)
    ->cookie('remember_me', $token, time() + (86400 * 30), [
        'path' => '/',
        'domain' => '.example.com',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Lax'
    ])
    ->send();
```

---

## Compression

### compress(method)

Enable compression untuk mengurangi bandwidth:

```php
// Signature
public function compress(?string $method = 'gzip'): Response

// Contoh - GZIP compression
Response::json(['large' => $largeDataset])
    ->compress('gzip')
    ->send();
// Header: Content-Encoding: gzip

// Contoh - DEFLATE compression
Response::json(['large' => $largeDataset])
    ->compress('deflate')
    ->send();
// Header: Content-Encoding: deflate

// Contoh - Auto-detect dari Accept-Encoding header
Response::json(['data' => $data])
    ->compress('auto')
    ->send();
// Browser request gzip/deflate, server auto-select
```

### noCompress()

Disable compression:

```php
// Signature
public function noCompress(): Response

// Contoh - Untuk streaming (video/audio)
Response::stream('/path/video.mp4')
    ->noCompress() // Jangan compress, sudah compressed
    ->send();

// Contoh - Untuk small responses
Response::json(['status' => 'ok'])
    ->noCompress()
    ->send();
```

### Compression Recommendations

**Use Compression For**

- Large JSON responses (> 1KB)
- HTML pages
- XML/CSV data
- API responses dengan payload besar

**Don't Compress**

- Small responses (< 1KB) - overhead lebih besar dari saving
- Already compressed (JPEG, MP4, GZIP files)
- Streaming content
- Real-time communication (WebSocket)

```php
// Auto compress untuk response besar
if (strlen(json_encode($data)) > 2048) {
    Response::json($data)
        ->compress('auto')
        ->send();
} else {
    Response::json($data)
        ->noCompress()
        ->send();
}
```

---

## Security Headers

### secureHeaders()

Auto-set security headers:

```php
// Signature
public function secureHeaders(): Response

// Contoh
Response::getInstance()
    ->secureHeaders()
    ->send();

// Headers yang di-set:
// X-Frame-Options: DENY (Prevent clickjacking)
// X-Content-Type-Options: nosniff (Prevent MIME sniffing)
// Referrer-Policy: strict-origin-when-cross-origin
// Permissions-Policy: camera=(), microphone=(), payment=()
// Content-Security-Policy: default-src 'self'
```

### Automatic Security

Security headers di-set otomatis di `getInstance()`:

```php
// getInstance() sudah call secureHeaders()
$response = Response::getInstance();
// Security headers sudah ada
```

### Custom Headers

```php
// Tambah custom security header
Response::success($data)
    ->header('X-Custom-Header', 'value')
    ->headers([
        'X-API-Version' => 'v1',
        'X-RateLimit-Limit' => '1000',
        'X-RateLimit-Remaining' => '999'
    ])
    ->send();
```

---

## Middleware Pipeline

### middleware(callback)

Add middleware callbacks untuk processing response:

```php
// Signature
public function middleware(callable $callback): Response

// Contoh - Single middleware
Response::success(['data' => $data])
    ->middleware(function($response) {
        // Process response
        error_log('Response sent at ' . date('Y-m-d H:i:s'));
    })
    ->send();

// Contoh - Multiple middleware
Response::json($data)
    ->middleware(function($response) {
        // Middleware 1: Logging
        error_log('Response Type: ' . $response->getResponseType());
    })
    ->middleware(function($response) {
        // Middleware 2: Analytics
        Analytics::track('response_sent');
    })
    ->middleware(function($response) {
        // Middleware 3: Validation
        if ($response->getHttpCode() >= 400) {
            ErrorMonitor::alert($response->getHttpCode());
        }
    })
    ->send();
```

### Middleware Execution Order

Middleware di-execute sebelum `sendHeaders()` dalam urutan penambahan:

```php
Response::success($data)
    ->middleware(function($r) { echo "First\n"; })
    ->middleware(function($r) { echo "Second\n"; })
    ->middleware(function($r) { echo "Third\n"; })
    ->send();
// Output:
// First
// Second
// Third
// [Headers sent]
// [Content sent]
```

---

## Best Practices

### 1. Always Use Type-Specific Methods

```php
// ❌ Bad - tidak jelas
Response::getInstance()
    ->header('Content-Type', 'application/json')
    ->send();

// ✅ Good - clear intent
Response::json($data)->send();
```

### 2. Chain Methods for Fluent Interface

```php
// ❌ Bad - verbose
$response = Response::success($data);
$response->cacheFor(3600);
$response->withETag('abc123');
$response->send();

// ✅ Good - fluent and readable
Response::success($data)
    ->cacheFor(3600)
    ->withETag('abc123')
    ->send();
```

### 3. Handle Errors Gracefully

```php
// ❌ Bad - no error info
if (error) {
    Response::error('Something wrong')->send();
}

// ✅ Good - detailed error info
if (error) {
    Response::validationError($errors, 'Validation failed')
        ->send();
}
```

### 4. Use Cache Control Strategically

```php
// Cache public data aggressively
Response::json(Category::all())
    ->cacheFor(86400) // 1 day
    ->send();

// Don't cache user-specific data
Response::json(['user' => Auth::user()])
    ->noCache()
    ->send();

// Cache longer for static content
Response::json(Config::all())
    ->cacheFor(604800) // 7 days
    ->send();
```

### 5. Use Security Headers

```php
// Otomatis di-set, tapi bisa di-override
Response::getInstance()
    ->secureHeaders()
    ->header('Access-Control-Allow-Origin', 'https://trusted.com')
    ->send();
```

### 6. Compress Strategically

```php
// Compress besar JSON response
$largeData = ['items' => array_fill(0, 1000, ['id', 'name', 'description'])];

Response::json($largeData)
    ->compress('auto')
    ->send();
```

### 7. Test Response Output

```php
// Testing tanpa send()
$snapshot = Response::success(['data' => $data])
    ->cacheFor(3600)
    ->exportForCache();

// Verify:
assert($snapshot['status'] == 200);
assert($snapshot['responseType'] == 'json');
```

---

## Examples

### API Response Examples

**List Users Endpoint**

```php
public function listUsers()
{
    $users = User::paginate(10);

    Response::success(
        ['users' => $users->getItems(), 'total' => $users->getTotal()],
        200,
        'Users retrieved successfully'
    )
    ->cacheFor(3600)
    ->withETag(md5(json_encode($users)))
    ->send();
}
```

**Create User Endpoint**

```php
public function createUser()
{
    $input = Request::all();
    $errors = Validator::validate($input, [
        'name' => 'required',
        'email' => 'required|email|unique:users',
        'password' => 'required|min:8'
    ]);

    if (!empty($errors)) {
        Response::validationError($errors, 'Validation failed')
            ->send();
    }

    $user = User::create($input);

    Response::success(
        ['user' => $user],
        201,
        'User created successfully'
    )
    ->cookie('user_id', $user->id)
    ->send();
}
```

**Update User Endpoint**

```php
public function updateUser($id)
{
    $user = User::find($id);

    if (!$user) {
        Response::notFound('User not found')
            ->send();
    }

    if (!Auth::can('edit', $user)) {
        Response::forbidden('Cannot edit this user')
            ->send();
    }

    $input = Request::all();
    $errors = Validator::validate($input, [
        'name' => 'sometimes|required',
        'email' => 'sometimes|required|email'
    ]);

    if (!empty($errors)) {
        Response::validationError($errors)
            ->send();
    }

    $user->update($input);

    Response::success(['user' => $user], 200, 'User updated')
        ->noCache()
        ->send();
}
```

**Delete User Endpoint**

```php
public function deleteUser($id)
{
    if (!Auth::check()) {
        Response::unauthorized('Login required')
            ->send();
    }

    $user = User::find($id);

    if (!$user) {
        Response::notFound()
            ->send();
    }

    $user->delete();

    Response::success(
        ['deleted_id' => $id],
        200,
        'User deleted successfully'
    )
    ->noCache()
    ->send();
}
```

**Error Handling**

```php
public function processPayment()
{
    try {
        $result = PaymentGateway::process($amount);

        Response::success(['transaction_id' => $result->id])
            ->send();
    } catch (PaymentException $e) {
        Response::error(
            'Payment failed',
            400,
            ['payment_error' => $e->getMessage()]
        )
        ->send();
    } catch (Exception $e) {
        Response::serverError(
            'Internal error',
            ['error' => 'Contact support']
        )
        ->send();
    }
}
```

**File Download**

```php
public function downloadReport($id)
{
    $report = Report::find($id);

    if (!$report || !Auth::can('view', $report)) {
        Response::forbidden('Access denied')
            ->send();
    }

    Response::download($report->file_path)
        ->header('Content-Disposition',
            'attachment; filename="' . $report->name . '.pdf"')
        ->noCache()
        ->send();
}
```

**Video Streaming**

```php
public function streamVideo($id)
{
    $video = Video::find($id);

    if (!$video) {
        Response::notFound()
            ->send();
    }

    Response::stream($video->file_path)
        ->noCompress() // Video sudah compressed
        ->send();
}
```

**HTML Page Render**

```php
public function showProfile($username)
{
    $user = User::where('username', $username)->first();

    if (!$user) {
        Response::notFound('User not found')
            ->send();
    }

    $view = new View('profile', [
        'user' => $user,
        'posts' => $user->posts()->latest()->limit(10)->get()
    ]);

    Response::html($view)
        ->cacheFor(3600)
        ->send();
}
```

---

## Troubleshooting

### Issue: Headers Already Sent

**Problem**: `Cannot modify header information`

```php
// ❌ Problem - output sent before Response
echo "Something";
Response::success($data)->send(); // Error: headers already sent

// ✅ Solution - no output before Response
Response::success($data)->send();
```

### Issue: Cache Not Working

**Problem**: Response tidak di-cache meskipun di-set `cacheFor()`

```php
// ❌ Check - noCache() override cacheFor()
Response::json($data)
    ->cacheFor(3600)
    ->noCache() // Ini override sebelumnya
    ->send();

// ✅ Solution - gunakan salah satu
Response::json($data)->cacheFor(3600)->send();
// atau
Response::json($data)->noCache()->send();
```

### Issue: Compression Not Working

**Problem**: Content tidak di-compress

```php
// ❌ Check - client tidak support
// Request header: Accept-Encoding: gzip, deflate
// Tapi browser lama tidak support gzip

// ✅ Solution - use auto-detect
Response::json($data)
    ->compress('auto') // Auto-detect dari client
    ->send();
```

### Issue: ETag Not Updating

**Problem**: Client cache terus hit meski data berubah

```php
// ❌ Problem - ETag static
Response::json($data)
    ->withETag('static-etag')
    ->send();

// ✅ Solution - dynamic ETag from data
Response::json($data)
    ->withETag(md5(json_encode($data)))
    ->send();
```

### Issue: CORS Blocked

**Problem**: JavaScript CORS error

```php
// ✅ Solution - add CORS headers
Response::json($data)
    ->header('Access-Control-Allow-Origin', 'https://client.com')
    ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE')
    ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization')
    ->send();
```

### Issue: Middleware Not Executing

**Problem**: Middleware callbacks tidak jalan

```php
// Middleware hanya execute sebelum send()
Response::json($data)
    ->middleware(function($r) {
        error_log('Middleware here');
    });
// Middleware belum jalan!

Response::json($data)
    ->middleware(function($r) {
        error_log('Middleware here');
    })
    ->send(); // ✅ Middleware execute di sini
```

### Debug Response

```php
// Get response info
$code = $response->getHttpCode();
$type = $response->getResponseType();
$content = $response->getContent();
$errors = $response->getErrors();
$meta = $response->getMeta();

// Export untuk testing
$snapshot = $response->exportForCache();
var_dump($snapshot);
```

---

## API Reference

### Static Methods

| Method              | Signature                                                                          | Return         |
| ------------------- | ---------------------------------------------------------------------------------- | -------------- |
| `reset()`           | `reset(): void`                                                                    | -              |
| `getInstance()`     | `getInstance(): Response`                                                          | Response       |
| `status()`          | `status(int $code, string $message = ''): Response`                                | Response       |
| `success()`         | `success($data, int $code = 200, string $message = 'Success'): Response`           | Response       |
| `error()`           | `error(string $message, int $code = 400, $errors = null): Response`                | Response       |
| `validationError()` | `validationError($errors, string $message = 'Validation failed'): Response`        | Response       |
| `notFound()`        | `notFound(string $message = 'Resource not found'): Response`                       | Response       |
| `unauthorized()`    | `unauthorized(string $message = 'Unauthorized'): Response`                         | Response       |
| `forbidden()`       | `forbidden(string $message = 'Forbidden'): Response`                               | Response       |
| `serverError()`     | `serverError(string $message = 'Internal server error', $errors = null): Response` | Response       |
| `getHttpInfo()`     | `getHttpInfo(): HttpInfoObject`                                                    | HttpInfoObject |

### Instance Methods

| Method               | Signature                                                                             | Return   | Chainable |
| -------------------- | ------------------------------------------------------------------------------------- | -------- | --------- |
| `secureHeaders()`    | `secureHeaders(): Response`                                                           | Response | ✅        |
| `header()`           | `header(string $key, string $value): Response`                                        | Response | ✅        |
| `headers()`          | `headers(array $headers): Response`                                                   | Response | ✅        |
| `cacheFor()`         | `cacheFor(?int $seconds = 3600): Response`                                            | Response | ✅        |
| `noCache()`          | `noCache(): Response`                                                                 | Response | ✅        |
| `privateCacheFor()`  | `privateCacheFor(?int $seconds = 3600): Response`                                     | Response | ✅        |
| `withETag()`         | `withETag(string $etag): Response`                                                    | Response | ✅        |
| `withLastModified()` | `withLastModified($timestamp): Response`                                              | Response | ✅        |
| `cookie()`           | `cookie(string $name, string $value, int $expire = 0, array $options = []): Response` | Response | ✅        |
| `compress()`         | `compress(?string $method = 'gzip'): Response`                                        | Response | ✅        |
| `noCompress()`       | `noCompress(): Response`                                                              | Response | ✅        |
| `setChunkSize()`     | `setChunkSize(int $bytes): Response`                                                  | Response | ✅        |
| `middleware()`       | `middleware(callable $callback): Response`                                            | Response | ✅        |
| `getHttpCode()`      | `getHttpCode(): int`                                                                  | int      | ❌        |
| `getResponseType()`  | `getResponseType(): string`                                                           | string   | ❌        |
| `getContent()`       | `getContent(): mixed`                                                                 | mixed    | ❌        |
| `getMeta()`          | `getMeta(): array`                                                                    | array    | ❌        |
| `getErrors()`        | `getErrors(): array`                                                                  | array    | ❌        |
| `send()`             | `send(): void`                                                                        | -        | -         |
| `exportForCache()`   | `exportForCache(): array`                                                             | array    | ❌        |

---

**Terakhir diupdate**: 2024
**Versi**: 2.0
**Kompatibilitas**: PHP 7.3+
