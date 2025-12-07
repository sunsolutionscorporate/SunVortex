# SunVortex — Cache, Security & Advanced Topics Dokumentasi Lengkap

**Location:** `system/Cache/`, `system/Exceptions/`, Security Patterns

Dokumentasi mendalam tentang caching strategy, error handling, security best practices, dan optimization techniques.

---

## Daftar Isi

1. [Cache System](#cache-system)
2. [Security Best Practices](#security-best-practices)
3. [Error Handling](#error-handling)
4. [Performance Optimization](#performance-optimization)
5. [Logging & Monitoring](#logging-monitoring)
6. [Advanced Patterns](#advanced-patterns)

---

## Cache System

### Cache Configuration

**File:** `system/Cache/CacheConfig.php`

```php
// .env configuration
CACHE_DRIVER=file          // Driver: file or redis
CACHE_TTL=3600             // Default TTL (seconds)
CACHE_PATH=/storage/cache  // For file driver
REDIS_HOST=localhost
REDIS_PORT=6379
REDIS_PASSWORD=
REDIS_DATABASE=0
```

### File Driver

```php
use System\Cache\Cache;

$cache = new Cache('file');

// Store value
$cache->put('my_key', 'my_value', 3600);  // TTL: 1 hour

// Retrieve
$value = $cache->get('my_key');            // Returns: 'my_value'
$value = $cache->get('nonexistent', 'default');  // Returns: 'default'

// Check existence
if ($cache->has('my_key')) {
    echo "Cached!";
}

// Remove
$cache->forget('my_key');

// Clear all
$cache->flush();

// Increment/Decrement (for counters)
$cache->increment('counter', 1);
$count = $cache->get('counter');  // 1

$cache->decrement('counter', 1);
$count = $cache->get('counter');  // 0
```

### Redis Driver

```php
use System\Cache\Cache;

$cache = new Cache('redis');

// Same API as file driver
$cache->put('user:1:profile', $userData, 3600);
$userData = $cache->get('user:1:profile');

// Batch operations
$cache->put('session:abc123', $sessionData, 1800);
$cache->put('user:2:profile', $userData, 3600);

// Forget multiple
$cache->forget('session:abc123');
$cache->forget('user:2:profile');

// Pattern forget (Redis-specific)
// $cache->forget('user:*');  // Would clear all user:* keys
```

### Query Caching

```php
// Cache database query
$products = Database::init()->table('products')
    ->where('status', 'active')
    ->cacheTtl(3600, 'active_products')  // Cache for 1 hour
    ->get();

// First call: Hit database, cache result
// Subsequent calls: Return cached result

// Disable cache
$products = Database::init()->table('products')
    ->noCache()
    ->get();  // Always hit database
```

### Cache Invalidation Strategy

```php
class ProductController extends Controller {

    public function store() {
        $data = $this->request->all();
        $product = new Product_model($data);
        $product->save();

        // Invalidate related caches
        $cache = Database::init()->getCache();

        // Clear product list cache
        $cache->forget('active_products');

        // Clear by pattern (if using key with ID)
        $cache->forget('product:*');  // Clear all product:* keys

        // Clear category cache if category changed
        if (isset($data['category_id'])) {
            $cache->forget('category:' . $data['category_id']);
        }

        return $this->response->status(201)->json($product->toArray());
    }

    public function update($id) {
        // ... update logic ...

        // Invalidate single product cache
        $cache = Database::init()->getCache();
        $cache->forget("product:{$id}");
        $cache->forget('active_products');  // Also invalidate list
    }
}
```

### Advanced Caching Patterns

```php
class CacheService {

    private $cache;

    public function __construct() {
        $this->cache = Database::init()->getCache();
    }

    // Remember pattern (get or set)
    public function getProduct($id) {
        $key = "product:{$id}";

        return $this->cache->remember($key, 3600, function() use ($id) {
            return Database::init()->table('products')
                ->where('id', $id)
                ->first();
        });
    }

    // Pull pattern (get and delete)
    public function getAndDeleteSession($token) {
        $key = "session:{$token}";

        $session = $this->cache->pull($key);
        // Session is retrieved and deleted in one operation

        return $session;
    }

    // Atomic increment (for rate limiting)
    public function checkRateLimit($userId, $limit = 100, $window = 3600) {
        $key = "rate_limit:{$userId}";

        $attempts = $this->cache->increment($key, 1);

        if ($attempts === 1) {
            // First attempt in this window
            $this->cache->put($key, 1, $window);
        }

        return $attempts <= $limit;  // true if within limit
    }
}
```

---

## Security Best Practices

### Input Validation

```php
class ProductController extends Controller {

    public function store() {
        $data = $this->request->all();

        // Validate input
        $errors = [];

        // Name validation
        if (empty($data['name']) || strlen($data['name']) < 3) {
            $errors[] = 'Product name must be at least 3 characters';
        }

        // Price validation (numeric, positive)
        if (!isset($data['price']) || !is_numeric($data['price']) || $data['price'] <= 0) {
            $errors[] = 'Price must be a positive number';
        }

        // Category validation (must exist)
        if (empty($data['category_id'])) {
            $errors[] = 'Category is required';
        } else {
            $category = Database::init()->table('categories')
                ->where('id', $data['category_id'])
                ->first();
            if (!$category) {
                $errors[] = 'Category not found';
            }
        }

        if ($errors) {
            return $this->response->status(422)->json(['errors' => $errors]);
        }

        // Proceed with creation
        $product = new Product_model($data);
        $product->save();

        return $this->response->status(201)->json(['id' => $product->id]);
    }
}
```

### XSS Prevention

```php
// ❌ DANGEROUS: Output user input directly
echo "Welcome " . $this->request->get('name');  // If name = "<script>alert('XSS')</script>"

// ✅ SAFE: HTML escape output
echo "Welcome " . htmlspecialchars($this->request->get('name'), ENT_QUOTES, 'UTF-8');

// ✅ Template level (in views)
// In PHP template:
// <?= htmlspecialchars($data['name'], ENT_QUOTES, 'UTF-8') ?>

// Or create helper:
function escape($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

// Usage
echo "Welcome " . escape($this->request->get('name'));
```

### SQL Injection Prevention

```php
// ❌ VULNERABLE: String concatenation
$id = $_GET['id'];
$product = Database::init()->query("SELECT * FROM products WHERE id = $id");

// ✅ SAFE: Parameterized query
$id = $this->request->get('id');
$product = Database::init()->query("SELECT * FROM products WHERE id = ?", [$id]);

// ✅ SAFE: QueryBuilder (auto-escaped)
$id = $this->request->get('id');
$product = Database::init()->table('products')
    ->where('id', $id)
    ->first();
```

### CSRF Protection

```php
// In HTML form
<form method="POST" action="/products">
    <input type="hidden" name="_token" value="<?= csrf_token(); ?>">
    <input type="text" name="name" placeholder="Product name">
    <button type="submit">Create</button>
</form>

// In AJAX
const token = document.querySelector('meta[name="csrf-token"]')?.content;

fetch('/api/products', {
    method: 'POST',
    headers: {
        'X-CSRF-Token': token,
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({name: 'Product'})
});

// Middleware validates token automatically
```

### CORS Security

```php
// .env
CORS_ORIGINS=https://app.example.com,https://admin.example.com
CORS_METHODS=GET,POST,PUT,DELETE
CORS_HEADERS=Content-Type,Authorization
CORS_CREDENTIALS=true

// Only allows specific origins to make requests
// Prevents unauthorized cross-origin access
```

### Password Security

```php
class User_model extends BaseModel {

    protected function setPasswordAttribute($value) {
        // Auto-hash password on set
        if (strlen($value) < 60) {  // Not yet hashed
            $this->attributes['password'] = password_hash($value, PASSWORD_BCRYPT);
        }
    }
}

// Login
class AuthController extends Controller {

    public function login() {
        $email = $this->request->post('email');
        $password = $this->request->post('password');

        $user = User_model::findBy('email', $email);

        // Compare hashed password
        if (!$user || !password_verify($password, $user->password)) {
            return $this->response->error(401, 'Invalid credentials');
        }

        // Don't return password
        $userData = $user->toArray();
        unset($userData['password']);

        return $this->response->json(['user' => $userData]);
    }
}
```

### Authentication with JWT

```php
// .env
JWT_ALGORITHM=HS256
JWT_SECRET=your-secret-key-here
JWT_EXPIRY=3600

// Login endpoint
public function login() {
    $user = User_model::findBy('email', $this->request->post('email'));

    if (!$user || !password_verify($this->request->post('password'), $user->password)) {
        return $this->response->error(401, 'Invalid credentials');
    }

    // Generate token
    $token = jwt_encode([
        'id' => $user->id,
        'email' => $user->email,
        'iat' => time(),
        'exp' => time() + 3600
    ]);

    return $this->response->json(['token' => $token]);
}

// Protected endpoint
public function profile() {
    $user = $this->request->user();  // Set by auth middleware

    if (!$user) {
        return $this->response->error(401, 'Unauthorized');
    }

    return $this->response->json($user);
}
```

### Rate Limiting

```php
// .env
THROTTLE_ENABLED=true
THROTTLE_LIMIT=100
THROTTLE_WINDOW=3600

// Custom rate limiting per endpoint
class ApiSearchController extends Controller {

    public function search() {
        $ip = $this->request->ip();
        $throttle = new Throttle_Ms();

        // Max 10 searches per minute
        if (!$throttle->isAllowed("search:{$ip}", 10, 60)) {
            return $this->response
                ->status(429)
                ->error('Too many search requests');
        }

        // Process search
        $results = $this->doSearch($this->request->get('q'));
        return $this->response->json(['results' => $results]);
    }
}
```

---

## Error Handling

**File:** `system/Exceptions/`

### Custom Exception Classes

```php
use System\Exceptions\AppExeption;
use System\Exceptions\DBException;

// AppException - General application errors
try {
    // Some operation
    throw new AppExeption('User not found', 404);
} catch (AppExeption $e) {
    // Public message
    echo $e->getMessage();  // "User not found"

    // Internal details (not shown to user)
    $e->getInternalMessage();  // "SELECT * FROM users WHERE id = X failed"
}

// DBException - Database errors
try {
    Database::init()->table('invalid_table')->get();
} catch (DBException $e) {
    // Log query that failed
    $e->getQuery();

    // Log internally (don't show to user)
    $e->logInternally();
}
```

### Error Response Pattern

```php
class ProductController extends Controller {

    public function show($id) {
        try {
            $product = Product_model::find($id);

            if (!$product) {
                // 404 - Not Found
                return $this->response
                    ->status(404)
                    ->error('Product not found');
            }

            return $this->response->json($product->toArray());

        } catch (DBException $e) {
            // Log internal error
            $e->logInternally();

            // Return generic error to user
            return $this->response
                ->status(500)
                ->error('Server error');
        }
    }

    public function store() {
        try {
            $input = $this->request->all();

            // Validate
            if (empty($input['name'])) {
                // 422 - Unprocessable Entity (validation error)
                return $this->response
                    ->status(422)
                    ->json([
                        'message' => 'Validation failed',
                        'errors' => ['name' => 'Name is required']
                    ]);
            }

            $product = new Product_model($input);
            $product->save();

            // 201 - Created
            return $this->response
                ->status(201)
                ->json(['id' => $product->id]);

        } catch (Exception $e) {
            // Log error
            error_log("Product creation failed: " . $e->getMessage());

            return $this->response
                ->status(500)
                ->error('Failed to create product');
        }
    }
}
```

### Global Error Handler

```php
// In Bootstrap.php (if implemented)
set_exception_handler(function($exception) {
    $response = Response::getInstance();

    if ($exception instanceof AppExeption) {
        return $response
            ->status($exception->getCode())
            ->error($exception->getMessage());
    }

    if ($exception instanceof DBException) {
        error_log($exception->getInternalMessage());
        return $response
            ->status(500)
            ->error('Database error');
    }

    // Generic error
    error_log($exception->getMessage());
    return $response
        ->status(500)
        ->error('Internal server error');
});
```

---

## Performance Optimization

### Database Optimization

```php
// ❌ N+1 Problem (1000 queries)
$products = Database::init()->table('products')->get();
foreach ($products as $product) {
    $category = Database::init()->table('categories')
        ->where('id', $product['category_id'])
        ->first();  // 1000 queries!
}

// ✅ Solution: Use JOIN (1 query)
$products = Database::init()->table('products')
    ->select('p.*', 'c.name as category')
    ->leftJoin('categories c', 'c.id = p.category_id')
    ->get();

// ❌ Selecting all columns
$users = Database::init()->table('users')->get();  // Large dataset

// ✅ Select only needed
$users = Database::init()->table('users')
    ->select('id', 'name', 'email')
    ->get();

// ✅ Index frequently queried columns
// In migration:
// $table->index('email');
// $table->index('status');
// $table->index(['user_id', 'created_at']);

// ✅ Use pagination
$page = $this->request->get('page', 1);
$products = Database::init()->table('products')
    ->paginate($page, 20);  // 20 per page, not all
```

### Query Optimization

```php
// ✅ Cache expensive queries
$stats = Database::init()->table('sales')
    ->select('YEAR(created_at), SUM(amount)')
    ->groupBy('YEAR(created_at)')
    ->cacheTtl(3600 * 24)  // Cache for 1 day
    ->get();

// ✅ Use LIMIT for analytics
$topProducts = Database::init()->table('sales')
    ->select('product_id, SUM(qty) as total')
    ->groupBy('product_id')
    ->orderBy('total DESC')
    ->limit(10)  // Top 10 only
    ->get();

// ✅ Defer heavy processing
$collection = Collection::make($largeData)
    ->chunk(100)  // Process in chunks
    ->map(function($chunk) {
        // Heavy processing on small chunk
    });
```

### Response Optimization

```php
class ApiController extends Controller {

    public function index() {
        $products = Database::init()->table('products')
            ->select('id', 'name', 'price')  // Not all columns
            ->where('status', 'active')
            ->limit(100)  // Not all records
            ->get();

        return $this->response
            ->status(200)
            ->compress('gzip')  // Compress large responses
            ->cacheFor(3600)    // Cache at response level
            ->json($products);
    }
}
```

---

## Logging & Monitoring

### Application Logging

```php
use System\Support\Helpers;

// Simple logging
Helpers::slog("User login: john@example.com");
Helpers::slog("API request: GET /api/products");
Helpers::slog("Error processing payment for order #123");

// Logs to: storage/logs/sunvortex.log
// Format: [2025-12-07 10:30:45] User login: john@example.com
```

### Database Query Logging

```php
class DatabaseLogger {

    public static function logQueries() {
        if (env('APP_DEBUG')) {
            Database::init()->enableProfiler();

            // After queries
            $profiler = Database::init()->getProfiler();
            $queries = $profiler->getProfiles();

            foreach ($queries as $query) {
                Helpers::slog("QUERY ({$query['time']}ms): {$query['query']}");
            }
        }
    }
}
```

### Error Logging

```php
class ErrorHandler {

    public static function handle(Exception $e) {
        // Log full error with stack trace
        $errorLog = [
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ];

        error_log(json_encode($errorLog));
    }
}
```

### Performance Monitoring

```php
class PerformanceMonitor {

    private static $startTime;

    public static function start() {
        self::$startTime = microtime(true);
    }

    public static function end($name = 'Request') {
        $duration = microtime(true) - self::$startTime;
        $memory = memory_get_peak_usage(true) / 1024 / 1024;  // MB

        Helpers::slog("$name completed in {$duration}s, peak memory: {$memory}MB");
    }
}

// Usage in Bootstrap
PerformanceMonitor::start();
// ... handle request ...
PerformanceMonitor::end('API Request');
```

---

## Advanced Patterns

### Dependency Injection Container

```php
class ServiceContainer {

    private static $services = [];

    public static function register($name, callable $resolver) {
        self::$services[$name] = $resolver;
    }

    public static function resolve($name) {
        if (!isset(self::$services[$name])) {
            throw new Exception("Service not found: $name");
        }
        return self::$services[$name]();
    }
}

// Register services
ServiceContainer::register('UserService', function() {
    return new UserService();
});

ServiceContainer::register('EmailService', function() {
    return new EmailService();
});

// Use in controller
$userService = ServiceContainer::resolve('UserService');
$user = $userService->create($data);
```

### Repository Pattern

```php
class UserRepository {

    public function find($id) {
        return User_model::find($id);
    }

    public function findByEmail($email) {
        return User_model::findBy('email', $email);
    }

    public function getActive() {
        return User_model::query()
            ->where('status', 'active')
            ->getResultArray();
    }

    public function create(array $data) {
        $user = new User_model($data);
        $user->save();
        return $user;
    }

    public function update(User_model $user, array $data) {
        $user->fill($data);
        $user->save();
        return $user;
    }
}

// Usage
class UserController extends Controller {

    private $repository;

    public function __construct() {
        parent::__construct();
        $this->repository = new UserRepository();
    }

    public function index() {
        $users = $this->repository->getActive();
        return $this->response->json($users);
    }
}
```

---

**Untuk Detail Lebih Lanjut:** Lihat source files untuk complete implementations.
