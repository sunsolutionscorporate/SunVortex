# Dokumentasi Database & QueryBuilder

Framework ini menyediakan API database mirip CodeIgniter4, mendukung multi-koneksi, QueryBuilder, dan eksekusi query mentah. Berikut dokumentasi lengkap penggunaannya.

---

## 1. Inisialisasi Database

### Mendapatkan Instance Database

```php
// Di controller/model
db = $this->db; // default koneksi
// Atau koneksi lain
---
db2 = Database::connect('nama_koneksi');
```

## 2. Query Builder

### a. Select Data

```php
// SELECT * FROM residents WHERE id = 1
$data = $this->db->table('residents')->where('id', 1)->get()->getResultArray();

// SELECT id, name FROM residents WHERE id < 100
$data = $this->db->table('residents')->select('id, name')->where('id < 100')->getResultArray();

// SELECT * FROM residents WHERE status = 'active' AND gender = 'F'
$data = $this->db->table('residents')->where(['status'=>'active','gender'=>'F'])->getResultArray();
```

### b. Chaining Where

```php
// SELECT * FROM residents WHERE status = 'active' AND gender = 'F'
$data = $this->db->table('residents')
    ->where('status', 'active')
    ->where('gender', 'F')
    ->getResultArray();
```

### c. orWhere

```php
// SELECT * FROM residents WHERE status = 'active' OR status = 'pending'
$data = $this->db->table('residents')
    ->where('status', 'active')
    ->orWhere('status', 'pending')
    ->getResultArray();
```

### d. LIKE

```php
// SELECT * FROM residents WHERE name LIKE '%budi%'
$data = $this->db->table('residents')->like('name', 'budi')->getResultArray();
```

### e. whereIn, whereNotIn

```php
// SELECT * FROM residents WHERE id IN (1,2,3)
$data = $this->db->table('residents')->whereIn('id', [1,2,3])->getResultArray();

// SELECT * FROM residents WHERE id NOT IN (1,2,3)
$data = $this->db->table('residents')->whereNotIn('id', [1,2,3])->getResultArray();
```

### f. whereNull, whereNotNull

```php
// SELECT * FROM residents WHERE deleted_at IS NULL
$data = $this->db->table('residents')->whereNull('deleted_at')->getResultArray();

// SELECT * FROM residents WHERE updated_at IS NOT NULL
$data = $this->db->table('residents')->whereNotNull('updated_at')->getResultArray();
```

### g. Order, Limit, Offset

```php
// SELECT * FROM residents ORDER BY id DESC LIMIT 10 OFFSET 5
$data = $this->db->table('residents')
    ->orderBy('id DESC')
    ->limit(10)
    ->offset(5)
    ->getResultArray();
```

---

## 3. Insert Data

```php
// INSERT INTO residents (name, age) VALUES ('Budi', 30)
$id = $this->db->table('residents')->insert([
    'name' => 'Budi',
    'age'  => 30
]);
```

---

## 4. Update Data

```php
// UPDATE residents SET name='Andi' WHERE id=1
$rows = $this->db->table('residents')
    ->where('id', 1)
    ->update(['name' => 'Andi']);
```

## 5. Delete Data

````php
// DELETE FROM residents WHERE id=1
$rows = $this->db->table('residents')

---

## 16. QueryBuilder::countAllResults()

`countAllResults()` menghitung total baris sesuai kondisi builder saat ini, termasuk `JOIN`, `WHERE`, dan `HAVING`. Ketika `GROUP BY`/`HAVING` digunakan, query dibungkus dengan subselect otomatis.

### Signatur:
```php
public function countAllResults(bool $reset = true): int
```

### Parameter:
- `$reset` (default: `true`): Jika `true`, builder state akan direset otomatis setelah count. Ini memudahkan pagination karena Anda bisa langsung melanjutkan dengan `limit()`/`offset()` tanpa perlu reset manual.

### Contoh Dasar:

```php
$qb = $this->db->table('officials o')
    ->leftJoin('residents r', 'r.id = o.id_pend')
    ->leftJoin('officials_position pos', 'pos.id = o.id_pos')
    ->where('o.status', 'active');

$total = $qb->countAllResults(); // Menghitung total, builder state direset otomatis ($reset=true default)
$data = $qb->limit(10)->offset(0)->get()->getResultArray();
```

### Catatan Penting:

- `countAllResults()` menjalankan query **terpisah** untuk menghitung total.
- Dengan `$reset = true` (default), state builder (where, joins, params, dll) akan direset setelah counting, sehingga Anda bisa langsung melanjutkan query tanpa khawatir kondisi lama tertinggal.
- Dengan `$reset = false`, state builder tetap dipertahankan (berguna jika Anda ingin reuse builder atau melakukan operasi manual).

---

## 17. Pagination Mudah dengan countAllResults($reset = true)

Fitur `$reset = true` pada `countAllResults()` dirancang untuk mengotomatisasi pagination dan membuat kode lebih bersih. Berikut pola umum:

### Pola Pagination Otomatis:

```php
// Step 1: Siapkan kondisi filter
$qb = $this->db->table('officials o')
    ->leftJoin('residents r', 'r.id = o.id_pend')
    ->where('o.status', 'active')
    ->like('r.name', 'ali'); // Filter tambahan

// Step 2: Hitung total dengan $reset=true (default)
// Builder akan direset otomatis setelah count
$total = $qb->countAllResults();

// Step 3: Query data dengan kondisi fresh (tanpa filter lama)
// PENTING: Setelah countAllResults($reset=true), Anda bisa langsung apply limit/offset
$perPage = 10;
$page = 1;
$offset = ($page - 1) * $perPage;

$data = $qb->limit($perPage)->offset($offset)->get()->getResultArray();

return [
    'total'     => $total,
    'per_page'  => $perPage,
    'page'      => $page,
    'data'      => $data
];
```

### Contoh Lengkap Pagination di Controller:

```php
class OfficialController {

    public function list() {
        $page = (int)($_GET['page'] ?? 1);
        $perPage = 10;

        // Siapkan query dengan filter
        $qb = $this->db->table('officials o')
            ->select('o.id, r.name, o.status, pos.name as position')
            ->leftJoin('residents r', 'r.id = o.id_pend')
            ->leftJoin('officials_position pos', 'pos.id = o.id_pos')
            ->where('o.status', 'active');

        // Tambahkan filter dinamis jika ada
        if ($search = ($_GET['search'] ?? null)) {
            $escaped = $this->db->escapeLikeString($search);
            $qb->like('r.name', $escaped);
        }

        // Hitung total (builder auto-reset)
        $total = $qb->countAllResults();

        // Ambil data halaman
        $offset = ($page - 1) * $perPage;
        $data = $qb->limit($perPage)->offset($offset)->get()->getResultArray();

        // Hitung pagination
        $totalPages = ceil($total / $perPage);

        return [
            'success' => true,
            'data'    => $data,
            'pagination' => [
                'current_page' => $page,
                'per_page'     => $perPage,
                'total'        => $total,
                'total_pages'  => $totalPages,
                'from'         => $offset + 1,
                'to'           => min($offset + $perPage, $total)
            ]
        ];
    }
}
```

### Perbandingan: Manual vs Otomatis

#### Manual Reset (Old Way):
```php
// Perlu manual reset atau query terpisah
$total = $qb->where('status', 'active')->countAllResults($reset = false);

// Harus manual reset atau buat query baru
$qb->resetBuilder(); // atau $qb = new QueryBuilder()

$data = $qb->where('status', 'active')
    ->limit(10)
    ->offset(0)
    ->get()
    ->getResultArray();
```

#### Otomatis Reset (New Way):
```php
// Cukup satu baris - auto reset!
$total = $qb->where('status', 'active')->countAllResults(); // $reset=true default

// Langsung lanjut - builder sudah bersih
$data = $qb->limit(10)->offset(0)->get()->getResultArray();
```

### Kapan Menggunakan `$reset = false`:

```php
$total = $qb->where('status', 'active')->countAllResults($reset = false);

// Ingin reuse builder atau manual kontrol
$data1 = $qb->limit(10)->get()->getResultArray(); // Still memiliki where clause
$qb->resetBuilder(); // Bersihkan manual
$data2 = $qb->where('status', 'inactive')->get()->getResultArray(); // Query baru
```

---

## 18. QueryBuilder::resetBuilder()

Method untuk membersihkan state builder secara manual.

### Signatur:
```php
public function resetBuilder(): self
```

### Contoh:
```php
$qb = $this->db->table('residents')
    ->where('status', 'active')
    ->orderBy('id DESC');

// Lakukan sesuatu...
$count = $qb->countAllResults();

// Bersihkan manual jika perlu
$qb->resetBuilder();

// Sekarang builder fresh kembali
$newData = $qb->where('gender', 'F')->get()->getResultArray();
```

---

## 16. OLD - QueryBuilder::countAllResults() [Previous Documentation]
  ->where('id', 1)
  ->delete();

````

---

## 6. Query Mentah (Raw Query)

```php
// SELECT * FROM residents WHERE id = 1
$result = $this->db->query('SELECT * FROM residents WHERE id = :id', ['id'=>1]);
$data = $result->getResultArray();
```

---

## 7. Multi Koneksi Database

```php
// Koneksi default
db1 = Database::connect();
// Koneksi lain (misal: 'produk')
db2 = Database::connect('produk');
```

---

## 8. Fitur Lain

- `toSql()`: Melihat SQL yang dihasilkan builder
  ```php
  $sql = $this->db->table('residents')->where('id', 1)->toSql();
  ```
- `first()`: Ambil satu baris pertama
  ```php
  $row = $this->db->table('residents')->where('id', 1)->first();
  ```
- `getRow()`: Alias untuk ambil satu baris

---

## 9. Catatan

- Semua query builder otomatis binding parameter (SQL injection safe)
- Fitur chaining didukung penuh
- Untuk fitur lanjutan, cek source code QueryBuilder.php

---

## 10. Contoh Lengkap

```php
// Ambil data penduduk aktif wanita, urutkan nama, limit 5
$data = $this->db->table('residents')
    ->select('id, name')
    ->where('status', 'active')
    ->where('gender', 'F')
    ->orderBy('name ASC')
    ->limit(5)
    ->getResultArray();
```

---

## 11. Contoh: Search + Filters + Pagination (safe, parameterized)

Berikut contoh implementasi yang setara dengan kode lama Anda (menggunakan JOIN dan kondisi dinamis), tetapi menggunakan binding parameter dan helper `escapeLikeString` yang ada di `Database`.

```php
public function get_officials(array $params = [])
{
    $limit  = (int)($params['limit'] ?? 20);
    $offset = (int)($params['offset'] ?? 0);

    $whereParts = [];
    $bindings = [];
    $i = 0;

    foreach ($params as $key => $val) {
        if ($val === '' || $val === null) continue; // skip kosong
        switch ($key) {
            case 'limit':
            case 'offset':
                break;
            case 'search':
                // escape untuk LIKE (menghindari wildcard injection)
                $escaped = $this->db->escapeLikeString($val);
                $p = ':search' . $i++;
                $whereParts[] = "(r.name LIKE $p ESCAPE '!'
                                  OR pos.name LIKE $p ESCAPE '!')";
                $bindings[ltrim($p, ':')] = "%{$escaped}%";
                break;
            default:
                // gunakan binding agar aman
                $p = ':' . $key . $i++;
                $whereParts[] = "o.$key = $p";
                $bindings[ltrim($p, ':')] = $val;
                break;
        }
    }

    $whereSql = $whereParts ? 'WHERE ' . implode(' AND ', $whereParts) : '';

    // total count
    $countSql = "SELECT COUNT(*) as total FROM officials o
                 LEFT JOIN residents r ON o.id_pend=r.id
                 LEFT JOIN officials_position pos ON o.id_pos=pos.id
                 $whereSql";
    $row = $this->db->query($countSql, $bindings)->getRow();

    // data
    $sql = "SELECT o.id,
               r.id as id_pend, pos.id as id_pos,
               o.leader,
               r.name as name, pos.name as position
            FROM officials o
            LEFT JOIN residents r ON o.id_pend=r.id
            LEFT JOIN officials_position pos ON o.id_pos=pos.id
            $whereSql
            LIMIT :_limit OFFSET :_offset";

    // tambahkan limit/offset ke bindings
    $bindings['_limit'] = $limit;
    $bindings['_offset'] = $offset;

    $result = $this->db->query($sql, $bindings);

    if (isset($params['id'])) {
        $payload = $result->getRow();
    } else {
        $payload = $result->getResultArray();
    }

    return [
        'total' => $row['total'] ?? 0,
        'data'  => $payload
    ];
}
```

Catatan:

- Kita menggunakan binding parameter untuk semua nilai dinamis. Ini menghindari SQL injection.
- Untuk pencarian LIKE, kita memanggil `$this->db->escapeLikeString($val)` untuk meng-escape wildcard `%` dan `_` (serta karakter escape sendiri). Setelah itu kita menambahkan `%` di sisi yang diinginkan.
- `Database::query()` menerima parameter array yang akan diteruskan ke PDO::execute.

Jika Anda ingin, saya bisa juga menambah `join()` dan `leftJoin()` ke `QueryBuilder` sehingga contoh di atas bisa ditulis menggunakan builder (lebih rapi dan mudah di-maintain).

Untuk pertanyaan lebih lanjut, cek dokumentasi di setiap method atau hubungi maintainer framework.
