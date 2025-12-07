# PANDUAN LENGKAP SISTEM DATABASE SUNVORTEX FRAMEWORK

**Framework:** SunVortex PHP  
**Versi:** 1.0  
**Kompatibilitas:** PHP 7.3+  
**Database Support:** MySQL, PostgreSQL, SQLite  
**Fitur Utama:** Query Builder, Caching, Profiler, Transaction, Multiple Connections  
**Bahasa:** Indonesia

---

## DAFTAR ISI

1. [Pengenalan Database System](#pengenalan-database-system)
2. [Arsitektur Sistem](#arsitektur-sistem)
3. [Setup dan Konfigurasi](#setup-dan-konfigurasi)
4. [Database Manager (Database.php)](#database-manager)
5. [Query Builder (QueryBuilder.php)](#query-builder)
6. [Query Cache (QueryCache)](#query-cache)
7. [Query Profiler](#query-profiler)
8. [Transaction Manager](#transaction-manager)
9. [Query Result](#query-result)
10. [Contoh Implementasi Lengkap](#contoh-implementasi-lengkap)
11. [Best Practices](#best-practices)
12. [Troubleshooting](#troubleshooting)

---

## PENGENALAN DATABASE SYSTEM

Sistem database SunVortex adalah abstraksi layer PDO yang powerful dengan fitur:

- ✅ **Multiple Database Connections** — Kelola multiple koneksi database sekaligus
- ✅ **Query Builder** — API fluent mirip Laravel/CodeIgniter untuk build query
- ✅ **Query Caching** — Cache hasil query dengan tag-based invalidation
- ✅ **Query Profiler** — Monitor dan analyze performa query
- ✅ **Transaction Manager** — Handle transaction dan savepoint
- ✅ **Result Wrapper** — Convenient API untuk access hasil query

### Komponen Utama

| Komponen               | File                               | Deskripsi                            |
| ---------------------- | ---------------------------------- | ------------------------------------ |
| **Database**           | `system/database/Database.php`     | Manager utama, singleton pattern     |
| **QueryBuilder**       | `system/database/QueryBuilder.php` | Build SQL query dengan fluent API    |
| **QueryCache**         | `system/database/QueryManager.php` | Cache hasil query dengan tag support |
| **QueryProfiler**      | `system/database/QueryManager.php` | Monitor durasi & jumlah query        |
| **TransactionManager** | `system/database/QueryManager.php` | Handle transaction & savepoint       |
| **QueryResult**        | `system/database/QueryResult.php`  | Wrapper hasil query PDO              |

---

## ARSITEKTUR SISTEM

```
┌─────────────────────────────────────────────────────────────┐
│                    Application Code                         │
│          (Controllers, Models, Services)                   │
└──────────────────────┬──────────────────────────────────────┘
                       │
┌──────────────────────▼──────────────────────────────────────┐
│           Database Manager (Singleton)                      │
│  - Manage multiple connections                            │
│  - Route queries to correct DB                            │
│  - Initialize cache & profiler                            │
└──┬───────────────────┬──────────────────┬─────────────────┘
   │                   │                  │
┌──▼──────┐   ┌────────▼────────┐  ┌──────▼──────────┐
│QueryCache│   │QueryProfiler   │  │TransactionMgr  │
│- File    │   │- Track timing  │  │- Savepoint     │
│- Tags    │   │- Slow query    │  │- Rollback      │
│- TTL     │   │- Analysis      │  │- After-commit  │
└──────────┘   └────────────────┘  └─────────────────┘
   │
┌──▼──────────────────────────────────────────────────────────┐
│                    QueryBuilder                             │
│  - select(), where(), join(), groupBy()                   │
│  - orderBy(), limit(), offset()                           │
│  - insert(), update(), delete()                           │
│  - Parameterized queries (safe from SQL injection)        │
└──┬───────────────────────────────────────────────────────────┘
   │
┌──▼───────────────────────────────────────────────────────────┐
│                   PDO Connections                            │
│  - MySQL (via DSN)                                          │
│  - PostgreSQL (via DSN)                                     │
│  - SQLite (via file path)                                   │
└───────────────────────────────────────────────────────────────┘
   │
┌──▼───────────────────────────────────────────────────────────┐
│                 Database Servers                             │
└───────────────────────────────────────────────────────────────┘
```

---

## SETUP DAN KONFIGURASI

### 1. Konfigurasi Database (.env)

```env
# DATABASE CONFIGURATION
DB_CONFIG='[
  {
    "driver": "mysql",
    "host": "localhost",
    "port": 3306,
    "database": "sunvortex_db",
    "username": "root",
    "password": ""
  },
  {
    "driver": "mysql",
    "host": "192.168.1.100",
    "port": 3306,
    "database": "sunvortex_secondary",
    "username": "dbuser",
    "password": "dbpass"
  }
]'

# QUERY CACHE SETTINGS
ENABLE_QUERY_CACHE=true
DEFAULT_QUERY_CACHE_TTL=600

# PROFILER SETTINGS (optional)
QUERY_PROFILER_ENABLED=true
```

### 2. Format DB_CONFIG

DB_CONFIG adalah JSON array berisi konfigurasi satu atau lebih database:

```json
[
  {
    "driver": "mysql|pgsql|sqlite",
    "host": "localhost",
    "port": 3306,
    "database": "nama_database",
    "username": "user",
    "password": "pass"
  }
]
```

**Opsi Driver:**

- `mysql` — MySQL/MariaDB
- `pgsql` — PostgreSQL
- `sqlite` — SQLite (file-based)

### 3. Inisialisasi Database

Biasanya di `Bootstrap.php`:

```php
<?php

// Lazy load: Database::init() akan dipanggil saat pertama kali dibutuhkan
// atau explicit:
Database::init();
```

---

## DATABASE MANAGER

File: `system/database/Database.php`

### Singleton Pattern

Database menggunakan Singleton untuk memastikan hanya ada satu instance:

```php
// Semua pemanggilan akan return instance yang sama
$db = Database::init();
$db2 = Database::init(); // $db === $db2
```

### Method Publik

#### 1. `init()` — Get Instance

```php
/**
 * @return Database
 */
public static function init()
```

Mendapatkan instance Database Manager (Singleton).

**Contoh:**

```php
$db = Database::init();
```

---

#### 2. `connectTo($group = null)` — Connect ke Database Tertentu

```php
/**
 * @param int|string|null $group Nama atau index database config
 * @return Database
 */
public static function connectTo($group = null)
```

Connect ke database tertentu dan set sebagai active connection.

**Contoh:**

```php
// Connect ke database pertama (default)
$db = Database::connectTo();

// Connect ke database index 1 (secondary)
$db = Database::connectTo(1);

// Connect ke database dengan nama database 'sunvortex_secondary'
$db = Database::connectTo('sunvortex_secondary');
```

---

#### 3. `connect($group = 0)` — Internal Connection

```php
/**
 * @param int|string $group
 * @return PDO
 */
public function connect($group = 0)
```

Membuat koneksi ke database tertentu. Biasanya auto-called, jarang dipanggil manual.

**Contoh:**

```php
$pdo = $db->connect(0); // Get PDO object untuk DB pertama
```

---

#### 4. `getConnection($group = null)` — Get PDO Object

```php
/**
 * @param int|string|null $group
 * @return PDO
 */
public function getConnection($group = null)
```

Mendapatkan PDO connection object untuk database tertentu.

**Contoh:**

```php
$pdo = Database::init()->getConnection(); // Get PDO object
$pdo = Database::init()->getConnection(1); // Get PDO untuk DB index 1
```

---

#### 5. `table($table, $group = null)` — Get QueryBuilder

```php
/**
 * @param string $table
 * @param int|string|null $group
 * @return QueryBuilder
 */
public function table($table, $group = null)
```

Mendapatkan QueryBuilder untuk tabel tertentu. **Ini adalah method paling sering digunakan.**

**Contoh:**

```php
// Query table 'residents' di database default
$results = Database::init()
    ->table('residents')
    ->where('id', 5)
    ->get()
    ->getResultArray();

// Query table 'residents' di database secondary
$results = Database::init()
    ->table('residents', 1)
    ->where('id', 5)
    ->get()
    ->getResultArray();
```

---

#### 6. `query($sql, $params = [], $group = null)` — Raw Query

```php
/**
 * @param string $sql
 * @param array $params
 * @param int|string|null $group
 * @return QueryResult
 */
public function query($sql, $params = [], $group = null)
```

Eksekusi raw SQL query dengan parameterized binding.

**Contoh:**

```php
// Raw SQL dengan parameters
$result = Database::init()->query(
    'SELECT * FROM residents WHERE id = :id AND name LIKE :name',
    ['id' => 5, 'name' => '%Budi%']
);

$rows = $result->getResultArray();
```

---

#### 7. `queryWithCache($sql, $params, $cacheKey, $ttl, $group)` — Query dengan Cache

```php
/**
 * @param string $sql
 * @param array $params
 * @param string|null $cacheKey
 * @param int|null $ttl
 * @param int|string|null $group
 * @return array
 */
public function queryWithCache($sql, $params = [], $cacheKey = null, $ttl = null, $group = null)
```

Eksekusi query dengan optional caching.

**Contoh:**

```php
$results = Database::init()->queryWithCache(
    'SELECT * FROM residents WHERE id = :id',
    ['id' => 5],
    'resident_5',     // cache key
    3600,             // TTL 1 jam
    0                 // group (DB index)
);
```

---

#### 8. `switchTo($group)` — Switch Database

```php
/**
 * @param int|string $group
 * @return Database
 */
public function switchTo($group)
```

Switch ke database lain untuk operasi berikutnya.

**Contoh:**

```php
$db = Database::init();
$db->switchTo(1); // Switch ke DB secondary

// Sekarang semua query akan ke DB secondary
$residents = $db->table('residents')->get()->getResultArray();

// Switch kembali
$db->switchTo(0);
```

---

#### 9. `testConnection($group = null)` — Test Connection

```php
/**
 * @param int|string|null $group
 * @return bool
 */
public function testConnection($group = null)
```

Test apakah koneksi database berhasil.

**Contoh:**

```php
if (Database::init()->testConnection()) {
    echo "Database connection OK";
} else {
    echo "Database connection FAILED";
}
```

---

#### 10. `escape($value, $group = null)` — Escape Value

```php
/**
 * @param mixed $value
 * @param int|string|null $group
 * @return string quoted value
 */
public function escape($value, $group = null)
```

Escape value menggunakan PDO::quote(). Return termasuk surrounding quotes.

**Contoh:**

```php
$escaped = Database::init()->escape("O'Brien");
// Output: 'O''Brien'
```

---

#### 11. `escapeLikeString($str, $escape_char = '!')` — Escape LIKE

```php
/**
 * @param string $str
 * @param string $escape_char
 * @return string
 */
public function escapeLikeString($str, $escape_char = '!')
```

Escape special characters untuk LIKE clause. Tidak menambah surrounding %.

**Contoh:**

```php
$escaped = Database::init()->escapeLikeString("50%");
// Output: "50!%"

// Kemudian gunakan di query:
->like('field', $escaped, 'after')
```

---

#### 12. `getProfiler()` — Get Profiler Instance

```php
/**
 * @return QueryProfiler|null
 */
public function getProfiler()
```

Mendapatkan QueryProfiler instance untuk analyze query performance.

**Contoh:**

```php
$profiler = Database::init()->getProfiler();
if ($profiler) {
    echo "Total queries: " . $profiler->getQueryCount();
    echo "Total time: " . $profiler->getTotalTime() . "ms";
}
```

---

#### 13. `getCache()` — Get Cache Instance

```php
/**
 * @return QueryCache|null
 */
public function getCache()
```

Mendapatkan QueryCache instance untuk manage cache.

**Contoh:**

```php
$cache = Database::init()->getCache();
if ($cache) {
    $cache->flushTag('table:residents'); // Invalidate cache untuk tabel
}
```

---

#### 14. `getTransactionManager()` — Get Transaction Manager

```php
/**
 * @return TransactionManager|null
 */
public function getTransactionManager()
```

Mendapatkan TransactionManager untuk handle transaction.

**Contoh:**

```php
$tm = Database::init()->getTransactionManager();
$tm->begin();
try {
    // operations
    $tm->commit();
} catch (Exception $e) {
    $tm->rollback();
}
```

---

#### 15. `getConfig()` — Get Database Config

```php
/**
 * @return array
 */
public function getConfig()
```

Mendapatkan semua database configuration.

**Contoh:**

```php
$configs = Database::init()->getConfig();
foreach ($configs as $config) {
    echo $config['database'];
}
```

---

### Helper Methods Internal

#### executeWithProfiling($sql, $params, $group)

Helper untuk INSERT/UPDATE/DELETE dengan profiling otomatis.

#### first($sql, $params, $group)

Ambil first row dari raw query.

```php
$resident = Database::init()->first(
    'SELECT * FROM residents WHERE id = :id',
    ['id' => 5]
);
```

---

## QUERY BUILDER

File: `system/database/QueryBuilder.php`

QueryBuilder adalah API fluent untuk build SQL query secara aman (prepared statements).

### Basic Usage

```php
$result = Database::init()
    ->table('residents')
    ->where('id', 5)
    ->get();
```

### SELECT Methods

#### 1. `select($columns = '*')` — Define Columns

```php
/**
 * @param string|array $columns
 * @return $this
 */
public function select($columns = '*')
```

Define kolom yang akan di-select.

**Contoh:**

```php
// String
Database::init()->table('residents')
    ->select('id, name, nik')
    ->get();

// Array
Database::init()->table('residents')
    ->select(['id', 'name', 'nik'])
    ->get();

// Dengan alias
Database::init()->table('residents')
    ->select('id, name AS resident_name')
    ->get();
```

---

#### 2. `from($table)` / `table($table)` — Define Table

```php
/**
 * @param string $table
 * @return $this
 */
public function from($table)
public function table($table)
```

Define tabel utama untuk query.

**Contoh:**

```php
Database::init()->table('residents')->get();
```

---

#### 3. `get()` — Execute SELECT

```php
/**
 * @return QueryResult
 */
public function get()
```

Eksekusi SELECT query dan return QueryResult.

**Contoh:**

```php
$result = Database::init()
    ->table('residents')
    ->where('id', '>', 5)
    ->get();

$rows = $result->getResultArray();
```

---

#### 4. `getResultArray()` — Get All Rows

```php
/**
 * @return array
 */
public function getResultArray()
```

Shortcut untuk `get()->getResultArray()`.

**Contoh:**

```php
$rows = Database::init()
    ->table('residents')
    ->where('id_job', 5)
    ->getResultArray();
```

---

#### 5. `getRow()` — Get First Row

```php
/**
 * @return array|null
 */
public function getRow()
```

Ambil first row.

**Contoh:**

```php
$resident = Database::init()
    ->table('residents')
    ->where('id', 5)
    ->getRow();
```

---

#### 6. `first()` — Get First Row (dengan LIMIT 1)

```php
/**
 * @return array|null
 */
public function first()
```

Ambil first row dengan auto LIMIT 1.

**Contoh:**

```php
$resident = Database::init()
    ->table('residents')
    ->where('nik', '1802266807918881')
    ->first();
```

---

### WHERE Methods

#### 1. `where($key, $value = null, $escape = true)` — WHERE Clause

```php
/**
 * @param string|array $key Kolom atau array kondisi
 * @param mixed $value Nilai (optional)
 * @param bool $escape
 * @return $this
 */
public function where($key, $value = null, $escape = true)
```

Add WHERE condition. Support multiple syntax:

**Contoh:**

```php
// Simple equality
Database::init()->table('residents')
    ->where('id', 5)
    ->get();
// SQL: WHERE id = 5

// Array
Database::init()->table('residents')
    ->where(['id' => 5, 'name' => 'Budi'])
    ->get();
// SQL: WHERE id = 5 AND name = 'Budi'

// Expression dengan params
Database::init()->table('residents')
    ->where('id > :id AND name = :name', ['id' => 5, 'name' => 'Budi'])
    ->get();
// SQL: WHERE id > 5 AND name = 'Budi'

// Raw expression
Database::init()->table('residents')
    ->where('id > 5')
    ->get();
// SQL: WHERE id > 5

// Chaining multiple where
Database::init()->table('residents')
    ->where('id', '>', 5)
    ->where('name', 'LIKE', '%Budi%')
    ->where('status', 'active')
    ->get();
// SQL: WHERE id > 5 AND name LIKE '%Budi%' AND status = 'active'
```

---

#### 2. `orWhere($key, $value = null)` — OR WHERE

```php
/**
 * @param string|array $key
 * @param mixed $value
 * @return $this
 */
public function orWhere($key, $value = null)
```

Add OR WHERE condition.

**Contoh:**

```php
Database::init()->table('residents')
    ->where('id', 5)
    ->orWhere('name', 'Budi')
    ->get();
// SQL: WHERE id = 5 OR name = 'Budi'

// Array
Database::init()->table('residents')
    ->where('id', 5)
    ->orWhere(['name' => 'Budi', 'status' => 'active'])
    ->get();
// SQL: WHERE id = 5 OR (name = 'Budi' AND status = 'active')
```

---

#### 3. `like($field, $match, $side = 'both')` — LIKE

```php
/**
 * @param string $field
 * @param string $match
 * @param string $side 'both'|'before'|'after'
 * @return $this
 */
public function like($field, $match, $side = 'both')
```

Add LIKE condition dengan wildcard otomatis.

**Contoh:**

```php
// both (default): %Budi%
Database::init()->table('residents')
    ->like('name', 'Budi')
    ->get();

// before: %Budi
Database::init()->table('residents')
    ->like('name', 'Budi', 'before')
    ->get();

// after: Budi%
Database::init()->table('residents')
    ->like('name', 'Budi', 'after')
    ->get();
```

---

#### 4. `orLike($field, $match, $side = 'both')` — OR LIKE

```php
public function orLike($field, $match, $side = 'both')
```

Add OR LIKE condition.

**Contoh:**

```php
Database::init()->table('residents')
    ->like('name', 'Budi')
    ->orLike('name', 'Andi')
    ->get();
// SQL: LIKE '%Budi%' OR ... LIKE '%Andi%'
```

---

#### 5. `whereIn($field, array $values)` — IN

```php
/**
 * @param string $field
 * @param array $values
 * @return $this
 */
public function whereIn($field, array $values)
```

Add WHERE IN condition.

**Contoh:**

```php
$ids = [1, 2, 3, 4, 5];
Database::init()->table('residents')
    ->whereIn('id', $ids)
    ->get();
// SQL: WHERE id IN (1, 2, 3, 4, 5)
```

---

#### 6. `orWhereIn($field, array $values)` — OR IN

```php
public function orWhereIn($field, array $values)
```

---

#### 7. `whereNotIn($field, array $values)` — NOT IN

```php
public function whereNotIn($field, array $values)
```

---

#### 8. `orWhereNotIn($field, array $values)` — OR NOT IN

```php
public function orWhereNotIn($field, array $values)
```

---

#### 9. `whereNull($field)` — IS NULL

```php
public function whereNull($field)
```

**Contoh:**

```php
Database::init()->table('residents')
    ->whereNull('deleted_at')
    ->get();
// SQL: WHERE deleted_at IS NULL
```

---

#### 10. `orWhereNull($field)` — OR IS NULL

```php
public function orWhereNull($field)
```

---

#### 11. `whereNotNull($field)` — IS NOT NULL

```php
public function whereNotNull($field)
```

---

#### 12. `orWhereNotNull($field)` — OR IS NOT NULL

```php
public function orWhereNotNull($field)
```

---

### JOIN Methods

#### 1. `join($table, $condition, $type = 'INNER')` — Generic JOIN

```php
/**
 * @param string $table
 * @param string $condition ON clause
 * @param string $type INNER|LEFT|RIGHT
 * @return $this
 */
public function join($table, $condition, $type = 'INNER')
```

**Contoh:**

```php
Database::init()->table('residents r')
    ->join('jobs j', 'r.id_job = j.id', 'LEFT')
    ->select(['r.id', 'r.name', 'j.name as job_name'])
    ->get();
```

---

#### 2. `leftJoin($table, $condition)` — LEFT JOIN

```php
public function leftJoin($table, $condition)
```

**Contoh:**

```php
Database::init()->table('residents')
    ->leftJoin('jobs', 'residents.id_job = jobs.id')
    ->select(['residents.id', 'residents.name', 'jobs.name'])
    ->get();
```

---

#### 3. `rightJoin($table, $condition)` — RIGHT JOIN

```php
public function rightJoin($table, $condition)
```

---

#### 4. `innerJoin($table, $condition)` — INNER JOIN

```php
public function innerJoin($table, $condition)
```

---

### GROUP BY & HAVING

#### 1. `groupBy($expr)` — GROUP BY

```php
/**
 * @param string $expr
 * @return $this
 */
public function groupBy($expr)
```

**Contoh:**

```php
Database::init()->table('residents')
    ->select(['id_job', 'COUNT(*) as total'])
    ->groupBy('id_job')
    ->get();
```

---

#### 2. `having($condition, $params = [])` — HAVING

```php
/**
 * @param string|array $condition
 * @param array $params
 * @return $this
 */
public function having($condition, $params = [])
```

**Contoh:**

```php
Database::init()->table('residents')
    ->select(['id_job', 'COUNT(*) as total'])
    ->groupBy('id_job')
    ->having('COUNT(*) > :count', ['count' => 5])
    ->get();
```

---

### ORDER BY, LIMIT, OFFSET

#### 1. `orderBy($expr)` — ORDER BY

```php
/**
 * @param string $expr
 * @return $this
 */
public function orderBy($expr)
```

**Contoh:**

```php
Database::init()->table('residents')
    ->orderBy('name ASC')
    ->get();

// Multiple fields
Database::init()->table('residents')
    ->orderBy('name ASC, created_at DESC')
    ->get();
```

---

#### 2. `limit($limit)` — LIMIT

```php
/**
 * @param int $limit
 * @return $this
 */
public function limit($limit)
```

---

#### 3. `offset($offset)` — OFFSET

```php
/**
 * @param int $offset
 * @return $this
 */
public function offset($offset)
```

**Contoh:**

```php
// Pagination: page 2, 10 items per page
$page = 2;
$perPage = 10;

Database::init()->table('residents')
    ->limit($perPage)
    ->offset(($page - 1) * $perPage)
    ->get();
```

---

### CACHE Methods

#### 1. `noCache()` — Disable Cache untuk Query Ini

```php
/**
 * @return $this
 */
public function noCache()
```

**Contoh:**

```php
Database::init()->table('residents')
    ->where('id', 5)
    ->noCache() // Jangan cache result ini
    ->get();
```

---

#### 2. `cacheTtl($seconds)` — Set Cache TTL untuk Query Ini

```php
/**
 * @param int $seconds
 * @return $this
 */
public function cacheTtl($seconds)
```

**Contoh:**

```php
Database::init()->table('residents')
    ->where('id', 5)
    ->cacheTtl(7200) // Cache 2 jam untuk query ini
    ->get();
```

---

### INSERT Methods

#### 1. `insert(array $data)` — INSERT

```php
/**
 * @param array $data Kolom => Value
 * @return int Insert ID (last insert id)
 */
public function insert(array $data)
```

**Contoh:**

```php
$id = Database::init()->table('residents')
    ->insert([
        'name' => 'Budi Santoso',
        'nik' => '1802266807918881',
        'placebirth' => 'Metro',
        'datebirth' => '1970-08-28'
    ]);

echo "Inserted with ID: $id";
```

---

### UPDATE Methods

#### 1. `update(array $data)` — UPDATE

```php
/**
 * @param array $data Kolom => Value baru
 * @return int Affected rows
 */
public function update(array $data)
```

**Catatan:** Harus ada WHERE clause. UPDATE tanpa WHERE akan throw DBException.

**Contoh:**

```php
$affected = Database::init()->table('residents')
    ->where('id', 4563)
    ->update([
        'name' => 'BUDI UPDATED',
        'placebirth' => 'Jakarta'
    ]);

echo "Updated rows: $affected";
```

---

### DELETE Methods

#### 1. `delete()` — DELETE

```php
/**
 * @return int Affected rows
 */
public function delete()
```

**Catatan:** Harus ada WHERE clause. DELETE tanpa WHERE akan throw DBException.

**Contoh:**

```php
$affected = Database::init()->table('residents')
    ->where('id', 4563)
    ->delete();

echo "Deleted rows: $affected";
```

---

### Utility Methods

#### 1. `toSql()` — Get SQL String

```php
/**
 * @return string
 */
public function toSql()
```

Lihat SQL yang akan diexecute tanpa benar-benar execute.

**Contoh:**

```php
$sql = Database::init()->table('residents')
    ->where('id', '>', 5)
    ->toSql();

echo $sql; // SELECT * FROM residents WHERE id > :where_id0
```

---

#### 2. `countAllResults($reset = true)` — COUNT

```php
/**
 * @param bool $reset Jika true, reset builder state
 * @return int Total baris
 */
public function countAllResults($reset = true)
```

Count total baris dengan kondisi WHERE saat ini.

**Contoh:**

```php
$total = Database::init()->table('residents')
    ->where('id_job', 5)
    ->countAllResults();

echo "Total: $total";
```

---

#### 3. `resetBuilder()` — Reset Builder State

```php
/**
 * @return $this
 */
public function resetBuilder()
```

Reset semua state (select, where, joins, limit, dll) untuk reuse builder.

**Contoh:**

```php
$qb = Database::init()->table('residents');

// Query 1
$total = $qb->where('id_job', 5)->countAllResults(false);
$qb->resetBuilder();

// Query 2
$results = $qb->where('id_marital', 3)->get()->getResultArray();
```

---

## QUERY CACHE

File: `system/database/QueryManager.php`

### Overview

QueryCache adalah layer caching untuk query results. Support file-based storage dengan TTL dan tag-based invalidation.

### Fitur

- ✅ File-based caching (di `.cache/query/`)
- ✅ TTL support (automatic expiration)
- ✅ Tag-based invalidation (invalidate related queries)
- ✅ Table-based invalidation (otomatis ketika tabel berubah)

### Configuration

Di `.env`:

```env
ENABLE_QUERY_CACHE=true
DEFAULT_QUERY_CACHE_TTL=600
```

### Method Publik

#### 1. `get($key)` — Get from Cache

```php
/**
 * @param string $key
 * @return mixed|null
 */
public function get($key)
```

**Contoh:**

```php
$cache = Database::init()->getCache();
$results = $cache->get('residents_all');
```

---

#### 2. `put($key, $value, $ttl = null)` — Put to Cache

```php
/**
 * @param string $key
 * @param mixed $value
 * @param int|null $ttl Seconds
 * @return bool
 */
public function put($key, $value, $ttl = null)
```

**Contoh:**

```php
$cache = Database::init()->getCache();
$cache->put('residents_all', $results, 3600);
```

---

#### 3. `forget($key)` — Remove from Cache

```php
/**
 * @param string $key
 * @return bool
 */
public function forget($key)
```

---

#### 4. `flush()` — Clear All Cache

```php
/**
 * @return bool
 */
public function flush()
```

---

#### 5. `tags($tags)` — Set Tags untuk Next Cache Entry

```php
/**
 * @param array|string $tags
 * @return $this
 */
public function tags($tags)
```

Tag cache entries untuk group invalidation.

**Contoh:**

```php
$cache = Database::init()->getCache();
$cache->tags(['residents', 'jobs'])->put('key', $value);
```

---

#### 6. `flushTag($tags)` — Invalidate by Tag

```php
/**
 * @param string|array $tags
 * @return bool
 */
public function flushTag($tags)
```

Invalidate semua cache dengan tag tertentu.

**Contoh:**

```php
$cache = Database::init()->getCache();
$cache->flushTag('residents'); // Hapus semua cache dengan tag 'residents'
$cache->flushTag(['residents', 'jobs']); // Hapus dengan multiple tags
```

---

#### 7. `flushTable($tables)` — Invalidate by Table

```php
/**
 * @param string|array $tables
 * @return bool
 */
public function flushTable($tables)
```

Invalidate cache untuk tabel tertentu (auto-tag dengan `table:{name}`).

**Contoh:**

```php
$cache = Database::init()->getCache();
$cache->flushTable('residents'); // Invalidate semua cache untuk tabel residents
$cache->flushTable(['residents', 'jobs']); // Multiple tables
```

---

#### 8. `disable()` / `enable()` — Toggle Cache

```php
public function disable()
public function enable()
```

Disable/enable caching globally.

---

### Automatic Table-based Invalidation

Ketika query melakukan INSERT/UPDATE/DELETE di suatu tabel, cache otomatis di-invalidate:

```php
// Ini akan auto-flush cache untuk tabel 'residents'
Database::init()->table('residents')
    ->where('id', 5)
    ->update(['name' => 'Budi']);
// Cache dengan tag 'table:residents' akan dihapus otomatis
```

---

## QUERY PROFILER

File: `system/database/QueryManager.php`

### Overview

QueryProfiler track dan analyze semua query yang di-execute.

### Method Publik

#### 1. `log($sql, $params, $duration)` — Log Query

```php
/**
 * @param string $sql
 * @param array $params
 * @param float $duration Milliseconds
 */
public function log($sql, $params, $duration)
```

Internally called oleh Database. Jarang dipanggil manual.

---

#### 2. `getQueries()` — Get All Logged Queries

```php
/**
 * @return array
 */
public function getQueries()
```

**Contoh:**

```php
$profiler = Database::init()->getProfiler();
$queries = $profiler->getQueries();

foreach ($queries as $q) {
    echo $q['sql'] . "\n";
    echo "Duration: " . $q['duration'] . "ms\n";
}
```

---

#### 3. `getSlowQueries($threshold = 1000)` — Get Slow Queries

```php
/**
 * @param float $threshold Milliseconds
 * @return array
 */
public function getSlowQueries($threshold = 1000)
```

Get queries yang lebih lama dari threshold.

**Contoh:**

```php
$profiler = Database::init()->getProfiler();
$slowQueries = $profiler->getSlowQueries(500); // Queries > 500ms

foreach ($slowQueries as $q) {
    echo "SLOW: " . $q['sql'] . " ({$q['duration']}ms)\n";
}
```

---

#### 4. `getTotalTime()` — Get Total Query Time

```php
/**
 * @return float
 */
public function getTotalTime()
```

**Contoh:**

```php
$profiler = Database::init()->getProfiler();
echo "Total query time: " . $profiler->getTotalTime() . "ms";
```

---

#### 5. `getQueryCount()` — Get Query Count

```php
/**
 * @return int
 */
public function getQueryCount()
```

**Contoh:**

```php
$profiler = Database::init()->getProfiler();
echo "Queries executed: " . $profiler->getQueryCount();
```

---

#### 6. `reset()` — Reset Profiler

```php
/**
 * @return $this
 */
public function reset()
```

Clear semua logged queries.

---

#### 7. `disable()` / `enable()` — Toggle Profiler

```php
public function disable()
public function enable()
```

---

## TRANSACTION MANAGER

File: `system/database/QueryManager.php`

### Overview

TransactionManager handle database transaction dan savepoint.

### Method Publik

#### 1. `begin($name = null)` — Begin Transaction

```php
/**
 * @param string|null $name
 * @return $this
 */
public function begin($name = null)
```

Mulai transaction. Jika sudah ada transaction, create savepoint.

**Contoh:**

```php
$tm = Database::init()->getTransactionManager();
$tm->begin();
try {
    // operations
    $tm->commit();
} catch (Exception $e) {
    $tm->rollback();
}
```

---

#### 2. `commit()` — Commit Transaction

```php
/**
 * @return bool
 */
public function commit()
```

Commit transaction atau release savepoint.

---

#### 3. `rollback()` — Rollback Transaction

```php
/**
 * @return bool
 */
public function rollback()
```

Rollback transaction atau rollback to savepoint.

---

#### 4. `transaction($callback)` — Auto-transaction Wrapper

```php
/**
 * @param callable $callback
 * @return mixed
 */
public function transaction($callback)
```

Wrapper yang auto-commit jika sukses, auto-rollback jika gagal.

**Contoh:**

```php
$tm = Database::init()->getTransactionManager();
try {
    $result = $tm->transaction(function($tm) {
        // Multi-step operations
        Database::init()->table('residents')
            ->where('id', 4563)
            ->delete();

        Database::init()->table('logs')
            ->insert(['message' => 'Resident deleted']);

        return true;
    });
} catch (Exception $e) {
    echo "Transaction failed: " . $e->getMessage();
}
```

---

#### 5. `inTransaction()` — Check if in Transaction

```php
/**
 * @return bool
 */
public function inTransaction()
```

---

#### 6. `getTransactionDepth()` — Get Savepoint Depth

```php
/**
 * @return int
 */
public function getTransactionDepth()
```

---

#### 7. `registerAfterCommit($callback)` — Register After-commit Hook

```php
/**
 * @param callable $callback
 * @return $this
 */
public function registerAfterCommit($callback)
```

Register callback yang akan dijalankan setelah top-level commit berhasil.

**Contoh:**

```php
$tm = Database::init()->getTransactionManager();
$tm->registerAfterCommit(function() {
    // Invalidate cache
    Database::init()->getCache()->flushTable('residents');
});

$tm->begin();
// ... operations
$tm->commit(); // Callback akan dijalankan otomatis
```

---

## QUERY RESULT

File: `system/database/QueryResult.php`

### Overview

QueryResult adalah wrapper untuk PDOStatement yang provide convenient API.

### Method Publik

#### 1. `fetchAll($fetchMode = PDO::FETCH_ASSOC)` — Get All Rows

```php
/**
 * @param int $fetchMode
 * @return array
 */
public function fetchAll($fetchMode = PDO::FETCH_ASSOC)
```

---

#### 2. `fetch($fetchMode = PDO::FETCH_ASSOC)` — Get First Row

```php
/**
 * @param int $fetchMode
 * @return array|null
 */
public function fetch($fetchMode = PDO::FETCH_ASSOC)
```

---

#### 3. `getResultArray()` — Alias untuk fetchAll()

```php
/**
 * @return array
 */
public function getResultArray()
```

---

#### 4. `getRow($index = 0)` — Get Row by Index

```php
/**
 * @param int $index
 * @return array|null
 */
public function getRow($index = 0)
```

---

#### 5. `getFirstRow()` — Get First Row

```php
/**
 * @return array|null
 */
public function getFirstRow()
```

---

#### 6. `getNumRows()` — Get Row Count

```php
/**
 * @return int
 */
public function getNumRows()
```

---

#### 7. `rowCount()` — Get Affected Rows

```php
/**
 * @return int
 */
public function rowCount()
```

---

#### 8. `getStatement()` — Get PDOStatement

```php
/**
 * @return PDOStatement|null
 */
public function getStatement()
```

---

#### 9. `fromArray(array $rows)` — Factory Method

```php
/**
 * @param array $rows
 * @return QueryResult
 * @static
 */
public static function fromArray(array $rows)
```

Create QueryResult dari array (useful untuk cache atau mock).

---

## CONTOH IMPLEMENTASI LENGKAP

### Contoh 1: CRUD Standar dengan QueryBuilder

```php
<?php

class ResidentsService
{
    /**
     * Get all residents
     */
    public function getAll($page = 1, $perPage = 10)
    {
        $db = Database::init();

        // Count total
        $total = $db->table('residents')
            ->whereNull('deleted_at')
            ->countAllResults();

        // Get results
        $offset = ($page - 1) * $perPage;
        $residents = $db->table('residents')
            ->whereNull('deleted_at')
            ->orderBy('name ASC')
            ->limit($perPage)
            ->offset($offset)
            ->getResultArray();

        return [
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'data' => $residents
        ];
    }

    /**
     * Get resident by ID
     */
    public function getById($id)
    {
        return Database::init()
            ->table('residents')
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->first();
    }

    /**
     * Find resident by NIK
     */
    public function getByNik($nik)
    {
        return Database::init()
            ->table('residents')
            ->where('nik', $nik)
            ->whereNull('deleted_at')
            ->first();
    }

    /**
     * Create resident
     */
    public function create($data)
    {
        return Database::init()
            ->table('residents')
            ->insert($data);
    }

    /**
     * Update resident
     */
    public function update($id, $data)
    {
        return Database::init()
            ->table('residents')
            ->where('id', $id)
            ->update($data);
    }

    /**
     * Soft delete resident
     */
    public function delete($id)
    {
        return Database::init()
            ->table('residents')
            ->where('id', $id)
            ->update(['deleted_at' => date('Y-m-d H:i:s')]);
    }

    /**
     * Hard delete resident
     */
    public function hardDelete($id)
    {
        return Database::init()
            ->table('residents')
            ->where('id', $id)
            ->delete();
    }
}
```

---

### Contoh 2: JOIN dengan Multiple Tables

```php
<?php

class ResidentsService
{
    /**
     * Get residents dengan job dan marital status (eager load)
     */
    public function getWithRelations($page = 1, $perPage = 10)
    {
        $offset = ($page - 1) * $perPage;

        $residents = Database::init()
            ->table('residents r')
            ->leftJoin('jobs j', 'r.id_job = j.id')
            ->leftJoin('marital_statuses m', 'r.id_marital = m.id')
            ->select([
                'r.id',
                'r.name',
                'r.nik',
                'r.datebirth',
                'j.name AS job_name',
                'm.name AS marital_status'
            ])
            ->whereNull('r.deleted_at')
            ->orderBy('r.name ASC')
            ->limit($perPage)
            ->offset($offset)
            ->getResultArray();

        return $residents;
    }
}
```

---

### Contoh 3: Complex Query dengan GROUP BY

```php
<?php

class ReportsService
{
    /**
     * Get resident count by job
     */
    public function residentsByJob()
    {
        return Database::init()
            ->table('residents r')
            ->leftJoin('jobs j', 'r.id_job = j.id')
            ->select(['j.name', 'COUNT(r.id) AS total'])
            ->where('r.deleted_at', null) // whereNull alternative
            ->groupBy('r.id_job')
            ->orderBy('total DESC')
            ->getResultArray();
    }

    /**
     * Get residents by marital status
     */
    public function residentsByMaritalStatus()
    {
        return Database::init()
            ->table('residents r')
            ->leftJoin('marital_statuses m', 'r.id_marital = m.id')
            ->select(['m.name', 'COUNT(r.id) AS total', 'AVG(YEAR(NOW()) - YEAR(r.datebirth)) AS avg_age'])
            ->whereNull('r.deleted_at')
            ->groupBy('r.id_marital')
            ->having('COUNT(r.id) > :min_count', ['min_count' => 10])
            ->getResultArray();
    }
}
```

---

### Contoh 4: Transaction dengan Multiple Operations

```php
<?php

class ResidentsService
{
    /**
     * Transfer resident antara jobs dengan atomic operation
     */
    public function transferJob($residentId, $newJobId)
    {
        $tm = Database::init()->getTransactionManager();

        try {
            $tm->begin();

            // Get current resident
            $resident = Database::init()
                ->table('residents')
                ->where('id', $residentId)
                ->first();

            if (!$resident) {
                throw new Exception('Resident tidak ditemukan');
            }

            // Update resident
            Database::init()
                ->table('residents')
                ->where('id', $residentId)
                ->update(['id_job' => $newJobId]);

            // Log the change
            Database::init()
                ->table('resident_job_changes')
                ->insert([
                    'resident_id' => $residentId,
                    'old_job_id' => $resident['id_job'],
                    'new_job_id' => $newJobId,
                    'changed_at' => date('Y-m-d H:i:s')
                ]);

            $tm->commit();

            return true;
        } catch (Exception $e) {
            $tm->rollback();
            throw $e;
        }
    }
}
```

---

### Contoh 5: Caching dengan Manual Control

```php
<?php

class ResidentsService
{
    /**
     * Get residents dengan caching
     */
    public function getCachedAll($forceRefresh = false)
    {
        $cache = Database::init()->getCache();
        $cacheKey = 'residents_all';

        // Check cache jika tidak force refresh
        if (!$forceRefresh) {
            $cached = $cache->get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }

        // Query database
        $residents = Database::init()
            ->table('residents')
            ->whereNull('deleted_at')
            ->orderBy('name ASC')
            ->getResultArray();

        // Store to cache dengan 1 jam TTL
        $cache->put($cacheKey, $residents, 3600);

        return $residents;
    }

    /**
     * Invalidate cache ketika ada perubahan
     */
    public function updateWithCacheInvalidation($id, $data)
    {
        // Update database
        $affected = Database::init()
            ->table('residents')
            ->where('id', $id)
            ->update($data);

        // Invalidate cache
        if ($affected > 0) {
            Database::init()
                ->getCache()
                ->flushTable('residents');
        }

        return $affected;
    }
}
```

---

### Contoh 6: Profiling untuk Debug

```php
<?php

class DebugService
{
    /**
     * Execute query dan show profiling info
     */
    public function executeWithProfiling($callback)
    {
        $profiler = Database::init()->getProfiler();

        // Reset profiler
        if ($profiler) {
            $profiler->reset();
        }

        // Execute callback
        $result = call_user_func($callback);

        // Show profiling
        if ($profiler) {
            echo "=== QUERY PROFILING ===\n";
            echo "Total Queries: " . $profiler->getQueryCount() . "\n";
            echo "Total Time: " . $profiler->getTotalTime() . "ms\n";
            echo "\nQueries:\n";

            foreach ($profiler->getQueries() as $i => $q) {
                echo ($i + 1) . ". [{$q['duration']}ms] {$q['sql']}\n";
                if (!empty($q['params'])) {
                    echo "   Params: " . json_encode($q['params']) . "\n";
                }
            }

            // Show slow queries
            $slow = $profiler->getSlowQueries(100);
            if (!empty($slow)) {
                echo "\nSlow Queries (> 100ms):\n";
                foreach ($slow as $q) {
                    echo "- [{$q['duration']}ms] {$q['sql']}\n";
                }
            }
        }

        return $result;
    }
}
```

---

## BEST PRACTICES

### 1. Selalu Gunakan Parameterized Queries

```php
// ✓ BAIK
Database::init()->table('residents')
    ->where('name', 'Budi')
    ->get();

// ✗ BURUK - SQL Injection Risk!
Database::init()->query("SELECT * FROM residents WHERE name = '" . $name . "'");
```

---

### 2. Gunakan Table Alias untuk Complex Query

```php
// ✓ BAIK - Readable
Database::init()->table('residents r')
    ->leftJoin('jobs j', 'r.id_job = j.id')
    ->select(['r.id', 'r.name', 'j.name AS job_name'])
    ->get();

// ✗ BURUK - Ambigu
Database::init()->table('residents')
    ->leftJoin('jobs', 'residents.id_job = jobs.id')
    ->select(['id', 'name'])
    ->get();
```

---

### 3. Gunakan Transaction untuk Multi-step Operations

```php
// ✓ BAIK - Atomic
$tm = Database::init()->getTransactionManager();
$tm->begin();
try {
    // operations
    $tm->commit();
} catch (Exception $e) {
    $tm->rollback();
}

// ✗ BURUK - Partial update jika fail
Database::init()->table('t1')->update(['data' => 'x']);
Database::init()->table('t2')->update(['data' => 'y']); // Jika ini fail
```

---

### 4. Cache Frequently Accessed Data

```php
// ✓ BAIK
$data = Database::init()->table('config')
    ->where('key', 'app_name')
    ->cacheTtl(86400) // 24 hours
    ->first();

// ✗ BURUK - Hit database setiap kali
$data = Database::init()->table('config')
    ->where('key', 'app_name')
    ->noCache()
    ->first();
```

---

### 5. Use countAllResults untuk Pagination

```php
// ✓ BAIK
$total = Database::init()
    ->table('residents')
    ->where('id_job', 5)
    ->countAllResults(false); // false = jangan reset builder

$results = Database::init()
    ->table('residents')
    ->where('id_job', 5)
    ->limit(10)
    ->offset(0)
    ->get();

// ✗ BURUK - Dua query terpisah
$total = Database::init()
    ->table('residents')
    ->where('id_job', 5)
    ->countAllResults(); // Reset builder

$results = Database::init()
    ->table('residents')
    ->where('id_job', 5)
    ->limit(10)
    ->get();
```

---

### 6. Use Profiler untuk Debug

```php
// Di development
if (config('APP_ENV') === 'development') {
    $profiler = Database::init()->getProfiler();
    echo "Queries: " . $profiler->getQueryCount();
    echo "Slow: " . count($profiler->getSlowQueries(100));
}
```

---

### 7. Invalidate Cache Saat Data Berubah

```php
// ✓ BAIK
Database::init()->table('residents')->insert($data);
Database::init()->getCache()->flushTable('residents');

// ✗ BURUK - Cache tidak updated
Database::init()->table('residents')->insert($data);
// Cache masih berisi data lama!
```

---

### 8. Handle Soft Delete Properly

```php
// ✓ BAIK - Exclude deleted records
Database::init()->table('residents')
    ->whereNull('deleted_at')
    ->get();

// ✗ BURUK - Include deleted records
Database::init()->table('residents')
    ->get(); // Includes deleted!
```

---

## TROUBLESHOOTING

### Q1: "Database config tidak ditemukan"

**A:** Check `.env` file, pastikan `DB_CONFIG` ada dan valid JSON:

```env
DB_CONFIG='[{"driver":"mysql","host":"localhost","database":"mydb","username":"root","password":""}]'
```

---

### Q2: Query tidak return hasil, padahal data ada

**A:** Cek apakah ada WHERE clause yang exclude data:

```php
// Mungkin ada soft delete
->whereNull('deleted_at')

// atau status filter
->where('status', 'active')
```

---

### Q3: Cache tidak di-invalidate setelah update

**A:** Pastikan call `flushTable()` setelah update:

```php
Database::init()->table('residents')->where('id', 5)->update($data);
Database::init()->getCache()->flushTable('residents');
```

---

### Q4: "Unsafe update: no WHERE clause"

**A:** UPDATE memerlukan WHERE clause untuk safety:

```php
// ✗ ERROR
Database::init()->table('residents')->update($data);

// ✓ BENAR
Database::init()->table('residents')->where('id', 5)->update($data);
```

---

### Q5: Transaction rollback tidak bekerja

**A:** Pastikan driver support transaction. SQLite perlu setting pragma:

```php
// Di config
"pragmas": {
    "journal_mode": "WAL",
    "synchronous": "NORMAL"
}
```

---

### Q6: Query masih hit database meskipun sudah cache

**A:** Check cache TTL tidak expired dan key sama:

```php
// Cache key harus sama persis
$cache->get('residents_5'); // null jika tidak ada
$cache->put('residents_5', $data, 3600);
```

---

### Q7: JOIN tidak return hasil dari right table

**A:** Pastikan ON condition benar dan gunakan LEFT JOIN:

```php
// ✗ Inner join (exclude jika job tidak ada)
->join('jobs', 'r.id_job = j.id', 'INNER')

// ✓ Left join (include dengan NULL jika job tidak ada)
->leftJoin('jobs', 'r.id_job = j.id')
```

---

## RINGKASAN QUICK REFERENCE

### Basic CRUD

| Operation | Code                                                                     |
| --------- | ------------------------------------------------------------------------ |
| SELECT    | `Database::init()->table('t')->get()->getResultArray()`                  |
| INSERT    | `Database::init()->table('t')->insert(['col' => 'val'])`                 |
| UPDATE    | `Database::init()->table('t')->where('id', 1)->update(['col' => 'val'])` |
| DELETE    | `Database::init()->table('t')->where('id', 1)->delete()`                 |
| COUNT     | `Database::init()->table('t')->countAllResults()`                        |

### WHERE Conditions

| Condition | Code                                            |
| --------- | ----------------------------------------------- |
| Equal     | `->where('col', 'val')`                         |
| Array     | `->where(['col1' => 'val1', 'col2' => 'val2'])` |
| LIKE      | `->like('col', 'search', 'both')`               |
| IN        | `->whereIn('col', [1, 2, 3])`                   |
| NULL      | `->whereNull('col')`                            |
| OR        | `->orWhere('col', 'val')`                       |

### JOIN

| Join  | Code                                      |
| ----- | ----------------------------------------- |
| LEFT  | `->leftJoin('t2', 't.id = t2.t_id')`      |
| INNER | `->join('t2', 't.id = t2.t_id', 'INNER')` |
| RIGHT | `->rightJoin('t2', 't.id = t2.t_id')`     |

### Other

| Feature     | Code                                        |
| ----------- | ------------------------------------------- |
| ORDER       | `->orderBy('col ASC')`                      |
| LIMIT       | `->limit(10)`                               |
| OFFSET      | `->offset(0)`                               |
| CACHE       | `->cacheTtl(3600)` or `->noCache()`         |
| GROUP       | `->groupBy('col')`                          |
| HAVING      | `->having('COUNT(*) > :cnt', ['cnt' => 5])` |
| TRANSACTION | `$tm->begin(); ... $tm->commit();`          |

---

**END OF DOCUMENTATION**

File dokumentasi ini mencakup semua aspek Database System SunVortex Framework secara menyeluruh.
