# Implementasi Cache Invalidation Otomatis - SunVortex Framework

## Ringkasan Masalah

Sebelum implementasi ini, sistem cache query database berjalan tetapi **cache tidak otomatis terhapus** ketika data tabel berubah (INSERT/UPDATE/DELETE). Hal ini menyebabkan data yang ditampilkan menjadi **tidak valid/stale**.

## Solusi yang Diimplementasikan

Sistem **Automatic Table-Based Cache Invalidation** yang mendeteksi tabel mana saja yang digunakan dalam setiap query, kemudian otomatis menghapus cache ketika ada perubahan data pada tabel tersebut.

---

## Komponen yang Diubah

### 1. **Cache.php** (`system/Cache/Cache.php`)

Tambahan pada class `Cache`:

- Property `$tags` untuk menyimpan tag cache
- Method `tags($tags)` - fluent interface untuk set tag
- Method `flushTag($tags)` - invalidate cache berdasarkan tag
- Method `flushTable($tables)` - invalidate cache berdasarkan nama tabel
- Update method `set()` untuk simpan tag dalam cache file

### 2. **QueryManager.php** (`system/database/QueryManager.php`)

Tambahan pada class `QueryCache`:

- Property `$tags` untuk menyimpan tag cache
- Method `tags($tags)` - fluent interface untuk set tag
- Method `flushTag($tags)` - invalidate cache berdasarkan tag
- Method `flushTable($tables)` - invalidate cache berdasarkan nama tabel
- Method `getDriver()` - get driver yang digunakan
- Update method `put()` untuk simpan tag dalam cache file

### 3. **QueryBuilder.php** (`system/database/QueryBuilder.php`)

Perubahan pada class `QueryBuilder`:

**Method `extractTablesFromQuery()` (BARU)**

- Ekstrak nama tabel dari FROM clause
- Ekstrak tabel dari JOIN clause
- Return array nama-nama tabel yang digunakan

**Method `get()` (UPDATE)**

- Setelah cache MISS dan query dijalankan:
  - Ekstrak tabel dari query dengan `extractTablesFromQuery()`
  - Set tag: `'table:{table_name}'` pada cache
  - Simpan hasil dengan tag menggunakan `put()`

**Method `insert()` (UPDATE)**

- Setelah INSERT sukses:
  - Panggil `$cache->flushTable($this->from)`
  - Semua cache dengan tag `'table:{table_name}'` otomatis dihapus

**Method `update()` (UPDATE)**

- Setelah UPDATE sukses:
  - Panggil `$cache->flushTable($this->from)`
  - Semua cache dengan tag `'table:{table_name}'` otomatis dihapus

**Method `delete()` (UPDATE)**

- Setelah DELETE sukses:
  - Panggil `$cache->flushTable($this->from)`
  - Semua cache dengan tag `'table:{table_name}'` otomatis dihapus

---

## Cara Kerja Sistem

### Scenario 1: Read Query dengan Cache

```
SELECT * FROM residents WHERE id = 1

1. Query Builder mengekstrak: table = 'residents'
2. Mencek cache dengan key 'qb:md5(...)'
   → Cache MISS (first time)
3. Execute query ke database
4. Tag cache dengan 'table:residents'
5. Simpan hasil ke cache
6. Return data ke aplikasi

Query berikutnya dengan kondisi sama:
1. Cek cache → CACHE HIT (lebih cepat)
2. Return data dari cache
```

### Scenario 2: Write Operation dengan Cache Invalidation

```
UPDATE residents SET name = 'Budi' WHERE id = 1

1. Execute UPDATE ke database
2. Setelah sukses:
   - Ekstrak table name: 'residents'
   - Panggil: $cache->flushTable('residents')
3. Semua cache dengan tag 'table:residents' DIHAPUS

Query berikutnya:
SELECT * FROM residents WHERE id = 1

1. Cek cache → CACHE MISS (baru saja di-invalidate)
2. Execute query ke database
3. Simpan hasil baru ke cache
4. Return data TERBARU ke aplikasi
```

### Scenario 3: JOIN Query dengan Multiple Tables

```
SELECT * FROM users
JOIN orders ON users.id = orders.user_id

1. Extract tables: ['users', 'orders']
2. Set tags: ['table:users', 'table:orders']
3. Simpan cache dengan kedua tag

Jika ada UPDATE pada tabel 'users':
1. Panggil: $cache->flushTable('users')
2. Cache dihapus karena salah satu tagnya cocok
3. Query JOIN otomatis refresh dengan data terbaru
```

---

## Test Results

Semua test case berhasil:

✅ **Test 1: Query Cache Tagging**

- Cache disimpan dengan tag tabel
- Invalidation hanya pada tabel yang sesuai

✅ **Test 2: Multiple Queries from Same Table**

- Semua query dengan tabel sama di-invalidate bersama
- Efisien untuk batch invalidation

✅ **Test 3: Multiple Tags per Query**

- JOIN query dengan multiple tables berfungsi
- Invalidation jika ANY tag match

✅ **Test 4: Fluent Interface**

- Syntax `tags()->put()` dan `flushTable()` lancar

---

## File Test

- `public/test_cache_simple.php` - Test Cache class tagging
- `public/test_querycache.php` - Test QueryCache tagging
- `public/test_final.php` - Test lengkap semua fungsi

Jalankan: `php public/test_final.php`

---

## Keuntungan Implementasi

✅ **Otomatis** - Tidak perlu manual invalidation di model  
✅ **Akurat** - Hanya invalidate cache tabel yang berubah  
✅ **Efisien** - Cache tetap berjalan maksimal  
✅ **Flexible** - Support single dan multiple tables  
✅ **Backward Compatible** - Kode lama tetap berfungsi  
✅ **Production Ready** - Sudah ditest dan siap pakai

---

## Implementasi di Aplikasi

Penggunaan sistem ini **TRANSPARAN** - tidak perlu perubahan kode aplikasi:

```php
// Kode aplikasi tetap sama seperti sebelumnya
$residents = $db->table('residents')
    ->where('status', 'active')
    ->get()
    ->fetchAll();

// Cache otomatis bekerja:
// - First query: cache MISS, data dari DB
// - Second query sama: cache HIT, data dari cache
// - Setelah UPDATE/INSERT/DELETE: cache otomatis invalidate
```

Semua logika cache invalidation sudah handle oleh framework secara otomatis.

---

## Status Implementasi

🎉 **COMPLETE & TESTED**

Sistem cache invalidation otomatis berdasarkan tabel sudah fully implemented dan tested dengan baik.
