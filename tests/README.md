# Cache Invalidation System - Test Files

## Daftar Test Files

### 1. **test_final.php** (RECOMMENDED - START HERE)

Test lengkap dan final untuk semua fungsi cache invalidation.

```bash
php test_final.php
```

Test yang dijalankan:

- ✓ Query Cache Tagging
- ✓ Multiple Queries from Same Table
- ✓ Multiple Tags per Query (JOIN support)
- ✓ Fluent Interface

**Output**: Menampilkan semua test results dengan ringkasan.

---

### 2. **test_cache_simple.php**

Test sederhana untuk Cache class tagging system.

```bash
php test_cache_simple.php
```

Test yang dijalankan:

- ✓ Cache tagging dengan file driver
- ✓ Tag-based invalidation
- ✓ flushTable method
- ✓ Multiple tags pada single entry

---

### 3. **test_querycache.php**

Test untuk QueryCache class tagging dan invalidation.

```bash
php test_querycache.php
```

Test yang dijalankan:

- ✓ QueryCache tagging dengan put()
- ✓ Multiple queries dengan different tags
- ✓ Backward compatibility
- ✓ flushTag method

---

### 4. **test_cache_invalidation.php**

(Legacy test - minimal functionality check)

---

### 5. **test_integration.php**

Test integrasi dengan actual database (memerlukan database connection).

---

### 6. **test_full.php**

Test lengkap dengan Bootstrap loading (memerlukan .env configuration).

---

## Cara Menjalankan Test

### Dari Command Line:

```bash
# Jalankan test final (recommended)
php tests/test_final.php

# Jalankan test cache simple
php tests/test_cache_simple.php

# Jalankan test query cache
php tests/test_querycache.php
```

### Dari Browser:

Set up web server terlebih dahulu:

```bash
php -S localhost:8080 -t public
```

Kemudian akses:

- `http://localhost:8080/tests/test_final.php` (jika public bisa access tests folder)

---

## Expected Output

Semua test seharusnya menampilkan:

```
✓✓✓ TEST 1 PASSED
✓✓✓ TEST 2 PASSED
✓✓✓ TEST 3 PASSED
✓✓✓ TEST 4 PASSED

ALL TESTS PASSED!
```

Jika ada test yang FAILED, check error message untuk debugging.

---

## Test Requirements

- PHP 7.3+
- Cache driver: file (default)
- Writable storage directory
- No database required (unless running test_integration.php)

---

## Troubleshooting

### Error: "Unable to load dynamic library 'php_imagick.dll'"

Normal warning - dapat diabaikan. Imagick tidak diperlukan untuk test ini.

### Error: "Class 'Database' not found"

Ensure Autoload.php dimuat dengan benar.

### Error: "Cache path not writable"

Check permissions di folder `storage/.cache/`

---

## Adding New Tests

Untuk menambah test baru:

1. Create file baru: `tests/test_something.php`
2. Include minimal boilerplate:

   ```php
   <?php
   define('ROOT_PATH', __DIR__ . '/../');
   define('CORE_PATH', ROOT_PATH . 'system/');
   define('DISK_PATH', ROOT_PATH . 'storage/');

   require_once CORE_PATH . 'Autoload.php';
   // ... test code
   ```

3. Run dengan: `php tests/test_something.php`

---

## More Information

Lihat dokumentasi di folder `/doc/`:

- `CACHE_IMPLEMENTATION_SUMMARY.md` - Technical details
- `CACHE_USAGE_EXAMPLES.md` - Usage examples
