# SunVortex Framework Documentation (Draft)

## 1. Introduction
SunVortex is a lightweight, modular PHP framework designed to provide a clean and modern development experience. It focuses on simplicity, flexibility, and performance, with built‑in tools such as routing, middleware, controllers, request/response handling, configuration system, and security utilities.

This documentation explains the framework structure, installation process, core concepts, features, and practical examples.

---

## 2. Directory Structure
A typical SunVortex project looks like this:

```
SunVortex/
 ├── app/
 │   ├── Controllers/
 │   ├── Models/
 │   ├── Middlewares/
 │   └── Helpers/
 ├── public/
 │   └── index.php
 ├── system/
 │   ├── Core/
 │   ├── Routing/
 │   ├── Http/
 │   └── Security/
 ├── storage/
 ├── .env
 ├── .gitignore
 └── composer.json
```

---

## 3. Installation & Initial Configuration
### 3.1 Requirements
- PHP 8+
- Composer
- Apache/Nginx

### 3.2 Install Dependencies
```
composer install
```

### 3.3 Environment Configuration
Copy `.env.example` to `.env`:

```
APP_ENV=development
APP_DEBUG=true
BASE_URL="http://localhost/sunvortex/"
```

You can access any value using:
```php
env('APP_ENV');
```

---

## 4. Routing
Routing is defined in `app/Routes/web.php`.

### Basic Route
```php
Route::get('/', function () {
    return view('welcome');
});
```

### Route to Controller
```php
Route::get('/user/{id}', 'UserController@detail');
```

### POST Route
```php
Route::post('/login', 'AuthController@login');
```

---

## 5. Controllers
Controllers are located in `app/Controllers`.

```php
class UserController extends Controller
{
    public function detail($id)
    {
        return response()->json([
            'id' => $id,
            'name' => 'John Doe'
        ]);
    }
}
```

---

## 6. Request Handling
Access request using the Request object:

```php
public function store(Request $request)
{
    $name = $request->input('name');
    $file = $request->file('avatar');
}
```

---

## 7. Response
### JSON Response
```php
return response()->json([ 'success' => true ]);
```

### View Response
```php
return view('dashboard', ['user' => $user]);
```

---

## 8. Middleware
Middleware processes request before reaching the controller.

### Example Middleware
```php
class AuthMiddleware
{
    public function handle($request, $next)
    {
        if (!Auth::check()) {
            return redirect('/login');
        }
        return $next($request);
    }
}
```

Register in route:
```php
Route::middleware('auth')->get('/dashboard', 'Dashboard@index');
```

---

## 9. CSRF Protection
SunVortex automatically validates CSRF tokens on POST/PUT/DELETE.

Handle preflight OPTIONS requests:
```php
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}
```

---

## 10. Database & Models
Models extend the core Base Model.

```php
class User extends Model
{
    protected $table = 'users';
}
```

### Query Example
```php
User::find(1);
User::where('status', 'active')->get();
```

---

## 11. Error Handling & Logging
Framework logs:
- Exceptions
- Rate limit blocks
- Invalid CSRF
- System errors

Example log message:
```
[RateLimiter] Request blocked: exceeding allowed request limit.
```

---

## 12. CLI Tools (Future Feature)
Planned commands:
- `php sunvortex migrate`
- `php sunvortex seeder`
- `php sunvortex make:controller`

---

## 13. Example Application
### Simple CRUD Example
```php
Route::get('/posts', 'PostController@index');
Route::post('/posts', 'PostController@store');
```

Controller:
```php
class PostController extends Controller
{
    public function index()
    {
        return Post::all();
    }

    public function store(Request $req)
    {
        return Post::create([
            'title' => $req->title,
            'content' => $req->content,
        ]);
    }
}
```

---

## 14. Conclusion
SunVortex is built to be simple, efficient, and customizable. This documentation will expand as more features are added, including:
- Advanced routing
- CLI tooling
- Caching system
- Authentication scaffolding
- Database migrations

---

*End of documentation draft.*

