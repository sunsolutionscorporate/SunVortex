# Database manager (core/database)

Dokumentasi singkat untuk Database manager dan QueryBuilder yang disediakan di `core/database`.

Fitur utama

- Multi-koneksi dari `.env` (DB_CONFIG JSON array)
- API mirip CodeIgniter4:
  - `$this->db->query($sql, $params, $group)` — jalankan query mentah, kembalikan `QueryResult`
  - `$this->db->table($table, $group)` — dapatkan `QueryBuilder` yang sudah di-set table
  - `$this->db->getQueryBuilder($group)` — dapatkan builder kosong
  - `$db->switchTo($group)` — ganti koneksi aktif

Contoh `.env` (Anda sudah punya):

```
DB_CONFIG='[
  {"driver":"mysql","host":"localhost","port":3306,"database":"desa","username":"root","password":""},
  {"driver":"mysql","host":"localhost","port":3306,"database":"produk","username":"root","password":""}
]'
```

Contoh penggunaan (di Controller / Model)

```php
// raw query
$res = $this->db->query('SELECT * FROM users WHERE id = :id', [':id' => 1]);
$row = $res->getFirstRow();

// query builder
$rows = $this->db->table('users')
            ->select(['id','name'])
            ->where(['status' => 'active'])
            ->orderBy('id DESC')
            ->limit(10)
            ->get()
            ->getResultArray();

// insert
$lastId = $this->db->table('users')->insert(['name'=>'Budi','age'=>30]);

// update (requires where)
$affected = $this->db->table('users')->where(['id'=>12])->update(['name'=>'Andi']);

// delete (requires where)
$deleted = $this->db->table('users')->where(['id'=>12])->delete();
```

Cara menjalankan test koneksi (lokal)

1. Pastikan PHP CLI terinstall dan XAMPP/DB service aktif.
2. Dari root project jalankan:

```powershell
php tests\test_database.php
```

Catatan

- `QueryBuilder` saat ini memiliki fitur dasar (select/where/orderBy/limit/offset/insert/update/delete). Jika Anda ingin fitur lebih lanjut (join, groupBy, transactions, escape identifiers) saya bisa tambahkan.
- `Database::init()` mengembalikan manager; `$this->db` di Controller diinisialisasi sebagai manager ini sehingga `$this->db->query()` dan `$this->db->table()` berfungsi secara langsung.
