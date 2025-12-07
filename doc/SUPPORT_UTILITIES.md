# SunVortex — Support Utilities Dokumentasi Lengkap

**Location:** `system/Support/`

Support utilities menyediakan helper classes dan functions untuk operasi umum seperti array manipulation, file handling, pipeline execution, dan utility functions.

---

## Daftar Isi

1. [Collection Class](#collection-class)
2. [Pipeline Class](#pipeline-class)
3. [Helpers Functions](#helpers-functions)
4. [File Class](#file-class)
5. [Use Cases & Examples](#use-cases-examples)

---

## Collection Class

**File:** `system/Support/Collection.php`

Collection menyediakan fluent API untuk manipulasi array data.

### Creating Collections

```php
use System\Support\Collection;

// Create from array
$collection = Collection::make([
    ['id' => 1, 'name' => 'John', 'status' => 'active'],
    ['id' => 2, 'name' => 'Jane', 'status' => 'inactive'],
    ['id' => 3, 'name' => 'Bob', 'status' => 'active'],
]);

// Create from database result
$results = Database::init()->table('users')->getResultArray();
$collection = Collection::make($results);

// Direct instantiation
$c = new Collection([1, 2, 3, 4, 5]);
```

### Filtering & Mapping

```php
$collection = Collection::make([
    ['id' => 1, 'name' => 'John', 'age' => 25],
    ['id' => 2, 'name' => 'Jane', 'age' => 30],
    ['id' => 3, 'name' => 'Bob', 'age' => 20],
]);

// Filter (keep matching items)
$adults = $collection->filter(function($item) {
    return $item['age'] >= 25;
});
// Result: [John(25), Jane(30)]

// Map (transform items)
$names = $collection->map(function($item) {
    return $item['name'];
});
// Result: ['John', 'Jane', 'Bob']

// Combine filter + map
$adult_names = $collection
    ->filter(function($item) { return $item['age'] >= 25; })
    ->map(function($item) { return $item['name']; });
// Result: ['John', 'Jane']
```

### Pluck & Extract

```php
$collection = Collection::make([
    ['id' => 1, 'name' => 'John', 'email' => 'john@example.com'],
    ['id' => 2, 'name' => 'Jane', 'email' => 'jane@example.com'],
]);

// Extract single column
$names = $collection->pluck('name');
// Result: ['John', 'Jane']

// Extract with index
$emailsByName = $collection->pluck('email', 'name');
// Result: ['John' => 'john@example.com', 'Jane' => 'jane@example.com']

// Get first and last
$first = $collection->first();   // First item
$last = $collection->last();     // Last item
$second = $collection->get(1);   // Item at index 1
```

### Grouping & Chunking

```php
$collection = Collection::make([
    ['user_id' => 1, 'status' => 'active'],
    ['user_id' => 2, 'status' => 'active'],
    ['user_id' => 1, 'status' => 'inactive'],
    ['user_id' => 3, 'status' => 'active'],
]);

// Chunk into smaller collections (for pagination)
$chunks = $collection->chunk(2);
// Result: [[item1, item2], [item3, item4], ...]

foreach ($chunks as $chunk) {
    // Process chunk
    // Useful for batch processing large data
}

// Group by key
$byUserId = $collection->groupBy('user_id');
// Result: [1 => [item1, item3], 2 => [item2], 3 => [item4]]
```

### Aggregation & Transformation

```php
$numbers = Collection::make([5, 3, 8, 2, 9]);

// Count
$count = $numbers->count();  // 5

// Sum
$sum = $numbers->reduce(function($carry, $item) {
    return $carry + $item;
}, 0);  // 27

// Get unique
$unique = $numbers->unique();  // [5, 3, 8, 2, 9] (no duplicates)

// Sort
$sorted = $numbers->sort(function($a, $b) {
    return $a - $b;  // Ascending
});  // [2, 3, 5, 8, 9]

// Reverse
$reversed = $numbers->reverse();  // [9, 8, 5, 3, 2]

// Check if contains value
$has = $numbers->contains(5);  // true
$has = $numbers->contains(100);  // false
```

### Combination Operations

```php
$collection = Collection::make([
    ['id' => 1, 'name' => 'John', 'dept' => 'IT'],
    ['id' => 2, 'name' => 'Jane', 'dept' => 'HR'],
    ['id' => 3, 'name' => 'Bob', 'dept' => 'IT'],
]);

// Merge multiple collections
$more_items = Collection::make([
    ['id' => 4, 'name' => 'Alice', 'dept' => 'Sales'],
]);
$merged = $collection->merge($more_items);

// Merge with callback
$result = $collection->merge($more_items, function($item) {
    return strtoupper($item['name']);
});

// Get diff
$diff = $collection->diff($more_items);  // Items in first but not in second

// Check empty
$isEmpty = $collection->isEmpty();  // false
```

### Output

```php
$collection = Collection::make([1, 2, 3]);

// To array
$array = $collection->toArray();  // [1, 2, 3]

// To JSON
$json = $collection->toJson();  // "[1,2,3]"

// To string
echo $collection;  // Uses __toString() method
```

---

## Pipeline Class

**File:** `system/Support/Pipeline.php` (347+ baris)

Pipeline menyediakan middleware pipeline pattern untuk mengeksekusi queue of handlers dalam urutan tertentu.

### Basic Pipeline

```php
use System\Support\Pipeline;
use System\Http\Request;

$pipeline = new Pipeline();

// Queue middleware/handlers
$pipeline->through([
    new LogRequestMiddleware(),
    new AuthMiddleware(),
    new ValidateInputMiddleware(),
    new CorsMiddleware(),
]);

// Define final handler
$response = $pipeline->then(function(Request $request) {
    // Final handler - called after all middleware
    return "Success!";
});
```

### Middleware Execution Flow

```
Request
  ↓
Pipeline::through([M1, M2, M3])
  ↓
M1 (pre-processing)
  ↓
M2 (pre-processing)
  ↓
M3 (pre-processing)
  ↓
Pipeline::then(handler)  ← Final handler
  ↓
M3 (post-processing)
  ↓
M2 (post-processing)
  ↓
M1 (post-processing)
  ↓
Response
```

### Skip Middleware

```php
$pipeline = new Pipeline();

$pipeline->through([
    new LogRequest(),
    new Auth(),
    new Validation(),
    new CORS(),
]);

// Skip specific middleware
$pipeline->skip(['Auth']);  // Skip authentication

$response = $pipeline->then(function(Request $request) {
    // Called without auth middleware
});
```

### Event Listener

```php
$pipeline = new Pipeline();

$pipeline->through([
    new MiddlewareA(),
    new MiddlewareB(),
]);

// Listen to middleware execution
$pipeline->listener(function($middleware, $action) {
    echo "Executing: " . get_class($middleware) . " ($action)\n";
    // Outputs: "Executing: MiddlewareA (before)"
    //          "Executing: MiddlewareB (before)"
    //          "Executing: MiddlewareB (after)"
    //          "Executing: MiddlewareA (after)"
});

$response = $pipeline->then(function() {
    return "Done!";
});
```

### Real-world Example

```php
class ApiPipeline {

    public function handle(Request $request) {
        $pipeline = new Pipeline();

        return $pipeline
            ->through([
                new ValidateApiVersion(),
                new RateLimiting(),
                new Authentication(),
                new Authorization(),
                new LogRequest(),
            ])
            ->skip($this->getSkipList($request))
            ->listener(function($mw, $action) {
                if ($action === 'before') {
                    slog("Middleware: " . get_class($mw));
                }
            })
            ->then(function() use ($request) {
                // API request proceeds here
                return RouteToController($request);
            });
    }

    private function getSkipList(Request $request) {
        // Public endpoints don't need auth
        if (strpos($request->path(), '/public') === 0) {
            return ['Authentication'];
        }
        return [];
    }
}
```

---

## Helpers Functions

**File:** `system/Support/Helpers.php`

Helper functions untuk operasi umum yang sering digunakan.

### Token Functions

```php
use System\Support\Helpers;

// Encode token (base64)
$token = Helpers::token_encode([
    'user_id' => 1,
    'email' => 'user@example.com',
    'exp' => time() + 3600
]);
// Returns: base64_encoded_data

// Decode token
$data = Helpers::token_decode($token);
// Returns: ['user_id' => 1, 'email' => 'user@example.com', 'exp' => ...]
```

### Byte Formatting

```php
// Format bytes to human-readable
echo Helpers::format_bytes(1024);         // "1 KB"
echo Helpers::format_bytes(1048576);      // "1 MB"
echo Helpers::format_bytes(1073741824);   // "1 GB"
echo Helpers::format_bytes(512);          // "512 B"
```

### String Functions (PHP < 8 Fallbacks)

```php
// String starts with
if (Helpers::str_starts_with('hello world', 'hello')) {
    echo "Starts with!";
}

// String ends with
if (Helpers::str_ends_with('hello.php', '.php')) {
    echo "Is PHP file!";
}

// String contains
if (Helpers::str_contains('john@example.com', '@example')) {
    echo "Is example domain!";
}

// Works with PHP < 8.0
// In PHP 8.0+, use native str_starts_with(), str_ends_with(), str_contains()
```

### Array Utility

```php
// Check if array is associative
$assoc = ['name' => 'John', 'age' => 30];
echo Helpers::is_assoc($assoc) ? "Assoc" : "Numeric";  // "Assoc"

// Check if string is valid JSON
$json = '{"key":"value"}';
echo Helpers::isJson($json) ? "JSON" : "Not JSON";  // "JSON"

// Spread array as function arguments
$args = [1, 2, 3];
$result = Helpers::spread('array_sum', $args);  // Calls array_sum(1, 2, 3)
```

### File Operations

```php
// Include class file
Helpers::include_class('path/to/MyClass.php');

// Load multiple files
Helpers::loadFiles([
    'path/to/file1.php',
    'path/to/file2.php'
]);

// Get content type
$type = Helpers::getContentType('image.jpg');  // "image/jpeg"
$type = Helpers::getContentType('file.pdf');   // "application/pdf"
```

### URL & Configuration

```php
// Get base URL
$url = Helpers::base_url();  // "http://example.com"
$path = Helpers::base_url('/api/products');  // "http://example.com/api/products"

// Get environment variable
$debug = Helpers::config('DEBUG');  // From .env
$port = Helpers::config('APP_PORT');
```

### HTTP & Version

```php
// Get HTTP status info
$info = Helpers::httpInfo(404);
// Returns: ['code' => 404, 'message' => 'Not Found']

// Check version string format
$valid = Helpers::isVersionString('1.2.3');  // true
$valid = Helpers::isVersionString('1.2');    // false
```

### Reflection Helpers

```php
// Change class property value via reflection
$object = new MyClass();
Helpers::changeClassProperty($object, 'privateProperty', 'new value');

// Object merge
$object1 = (object)['name' => 'John', 'age' => 30];
$object2 = (object)['age' => 31, 'city' => 'NYC'];
$merged = Helpers::object_merge($object1, $object2);
// Result: stdClass with name, age, city

// Get last URL segment
$lastSegment = Helpers::getLastSegment('/api/users/123');  // "123"
```

### Logging

```php
// Simple logging
Helpers::slog("Application started");
Helpers::slog("User logged in: john@example.com");

// Logs to file: storage/logs/sunvortex.log
// Format: [YYYY-MM-DD HH:MM:SS] message
```

---

## File Class

**File:** `system/Support/File.php`

File class untuk operasi file dan deteksi MIME type.

### MIME Type Detection

```php
use System\Support\File;

$file = new File();

// Get MIME type from filename
$mime = $file->getMimeType('document.pdf');   // "application/pdf"
$mime = $file->getMimeType('image.jpg');      // "image/jpeg"
$mime = $file->getMimeType('video.mp4');      // "video/mp4"
$mime = $file->getMimeType('script.js');      // "application/javascript"

// Supported types
// Images: jpg, jpeg, png, gif, bmp, svg, webp, ico
// Videos: mp4, mkv, avi, mov, flv, wmv, webm
// Audio: mp3, wav, flac, aac, ogg, m4a
// Documents: pdf, doc, docx, xls, xlsx, ppt, pptx
// Archives: zip, rar, 7z, tar, gz
// Others: txt, csv, json, xml, html, css, js
```

### File Operations

```php
$file = new File();

// Check if file exists
if ($file->exists('/path/to/file.pdf')) {
    echo "File found!";
}

// Get file size
$size = $file->getSize('/path/to/file.pdf');
echo "File size: " . format_bytes($size);

// Get file extension
$ext = $file->getExtension('document.pdf');  // "pdf"

// Get filename without extension
$name = $file->getNameWithoutExtension('document.pdf');  // "document"

// Get directory
$dir = $file->getDirectory('/path/to/file.pdf');  // "/path/to"
```

---

## Use Cases & Examples

### User Management with Collection

```php
class UserService {

    public function getActiveAdults(int $minAge = 18) {
        $users = Database::init()->table('users')
            ->where('status', 'active')
            ->getResultArray();

        return Collection::make($users)
            ->filter(function($user) use ($minAge) {
                return ($user['age'] ?? 0) >= $minAge;
            })
            ->map(function($user) {
                return [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email']
                ];
            })
            ->toArray();
    }

    public function getUsersByDepartment() {
        $users = Database::init()->table('users')
            ->select('id', 'name', 'department')
            ->getResultArray();

        return Collection::make($users)
            ->groupBy('department')
            ->map(function($users, $dept) {
                return [
                    'department' => $dept,
                    'count' => count($users),
                    'users' => Collection::make($users)
                        ->pluck('name')
                        ->toArray()
                ];
            })
            ->toArray();
    }
}
```

### Request Pipeline

```php
// Middleware for API validation
class ValidateJsonMiddleware implements BaseMw {
    public function handle(Request $request, callable $next) {
        $contentType = $request->header('Content-Type');
        if (strpos($contentType, 'json') === false) {
            return Response::getInstance()
                ->status(400)
                ->error('Content-Type must be application/json');
        }
        return $next($request);
    }
}

// Middleware for rate limiting
class RateLimitMiddleware implements BaseMw {
    public function handle(Request $request, callable $next) {
        $ip = $request->ip();
        $cache = Database::init()->getCache();

        $key = "rate_limit:{$ip}";
        $attempts = $cache->get($key, 0);

        if ($attempts >= 60) {  // 60 requests per minute
            return Response::getInstance()
                ->status(429)
                ->error('Too many requests');
        }

        $cache->put($key, $attempts + 1, 60);

        return $next($request);
    }
}

// Use pipeline
$pipeline = new Pipeline();
$response = $pipeline
    ->through([
        new ValidateJsonMiddleware(),
        new RateLimitMiddleware(),
        new AuthMiddleware(),
    ])
    ->then(function(Request $request) {
        // API handler
        return ApiController::dispatch($request);
    });
```

### Bulk Data Processing

```php
class ExportService {

    public function exportUsersAsJson() {
        $users = Database::init()->table('users')
            ->where('status', 'active')
            ->limit(10000)
            ->getResultArray();

        $collection = Collection::make($users)
            ->chunk(100)  // Process in chunks of 100
            ->map(function($chunk) {
                // Process each chunk
                return $chunk->map(function($user) {
                    $user['email_verified'] = (bool) $user['email_verified'];
                    $user['created_at'] = date('c', strtotime($user['created_at']));
                    return $user;
                })->toArray();
            });

        // Output
        foreach ($collection as $chunk) {
            // Stream to file
            file_put_contents('users_export.json', json_encode($chunk) . "\n", FILE_APPEND);
        }
    }
}
```

---

## Best Practices

✅ **Do:**

- Use Collection for array transformations
- Chain methods for readability
- Use Pipeline for middleware/handler logic
- Leverage helper functions to reduce code
- Extract MIME type for file uploads
- Use logging for debugging
- Chunk large datasets

❌ **Don't:**

- Manipulate arrays manually (use Collection)
- Hardcode MIME types
- Mix pipelines without clear separation
- Ignore file size for uploads
- Skip logging in important operations

---

**Untuk Detail Lebih Lanjut:** Lihat source files di `system/Support/` untuk complete implementations.
