# PANDUAN LENGKAP PENGGUNAAN BASEMODEL

**File:** `system/Core/BaseModel.php`  
**Versi:** 1.0  
**Bahasa:** Indonesia  
**Update Terakhir:** Desember 2025

---

## DAFTAR ISI

1. [Pengenalan BaseModel](#pengenalan-basemodel)
2. [Instalasi dan Konfigurasi Dasar](#instalasi-dan-konfigurasi-dasar)
3. [Properti Konfigurasi Model](#properti-konfigurasi-model)
4. [Membuat Model Baru](#membuat-model-baru)
5. [Method Publik - CRUD dan Query](#method-publik---crud-dan-query)
6. [Method Publik - Helpers Utility](#method-publik---helpers-utility)
7. [Method Publik - Events](#method-publik---events)
8. [Method Publik - Serialisasi](#method-publik---serialisasi)
9. [Method Publik - Transactions](#method-publik---transactions)
10. [Fitur Tambahan: Casting, Accessor, Mutator](#fitur-tambahan-casting-accessor-mutator)
11. [Fitur Tambahan: Mass Assignment](#fitur-tambahan-mass-assignment)
12. [Fitur Tambahan: Soft Delete](#fitur-tambahan-soft-delete)
13. [Contoh Implementasi Lengkap di Controller](#contoh-implementasi-lengkap-di-controller)
14. [Best Practices](#best-practices)
15. [FAQ dan Troubleshooting](#faq-dan-troubleshooting)

---

## PENGENALAN BASEMODEL

`BaseModel` adalah class abstrak yang menjadi fondasi semua Model di aplikasi SunVortex. Ia menyediakan:

- **CRUD Otomatis**: Create, Read, Update, Delete dengan API sederhana
- **Mass Assignment Aman**: Pengisian atribut massal dengan proteksi `$fillable` dan `$guarded`
- **Casting & Transformasi**: Otomatis konversi tipe data (int, float, bool, date, json, dll)
- **Accessor & Mutator**: Custom getter/setter untuk setiap atribut
- **Event System**: Hook sebelum/sesudah operasi CRUD
- **Soft Delete**: Tandai record sebagai terhapus tanpa menghapus dari DB
- **Helper Utilities**: `findBy()`, `deleteById()`, `updateOrCreate()`, `firstOrCreate()`, `refresh()`
- **Transaction Support**: Proxy untuk transaction DB
- **Serialisasi**: Convert ke Array dan JSON dengan mudah

Semua Model di aplikasi Anda harus extend `BaseModel` untuk mendapatkan fitur-fitur tersebut.

---

## INSTALASI DAN KONFIGURASI DASAR

### Prasyarat

- Framework SunVortex sudah ter-setup
- `Database` class sudah dikonfigurasi dan dapat diakses via `Database::init()`
- PHP 7.3 atau lebih tinggi

### Cara Menggunakan

Semua model harus extend `BaseModel`:

```php
<?php

class NamaModel extends BaseModel
{
    // Konfigurasi akan dijelaskan di section berikutnya
}
```

---

## PROPERTI KONFIGURASI MODEL

Properti yang dapat Anda override di model turunan Anda:

### 1. `protected $table`

**Tipe:** String  
**Default:** "" (kosong, akan di-infer otomatis)

Nama tabel di database yang sesuai dengan model ini.

**Contoh:**

```php
class Residents_model extends BaseModel
{
    protected $table = 'residents'; // tabel 'residents' di DB
}
```

Jika tidak di-set, `BaseModel` akan mencoba menebak dari nama class:

- `Residents_model` → `residents`
- `User_model` → `users`
- `Post_model` → `posts`

---

### 2. `protected $primaryKey`

**Tipe:** String  
**Default:** `'id'`

Nama kolom primary key (identifier unik setiap record).

**Contoh:**

```php
protected $primaryKey = 'id'; // default
// atau jika PK bernama lain:
protected $primaryKey = 'user_id';
```

---

### 3. `protected $fillable`

**Tipe:** Array  
**Default:** `[]` (kosong)

Daftar kolom yang boleh di-isi via `fill()` (mass assignment). Jika kosong, semua kolom kecuali yang di-`$guarded` boleh diisi.

**Contoh:**

```php
protected $fillable = [
    'name',
    'nik',
    'placebirth',
    'datebirth',
    'id_job',
    'id_marital'
];
```

---

### 4. `protected $guarded`

**Tipe:** Array  
**Default:** `['id']`

Daftar kolom yang TIDAK boleh di-isi via `fill()` (mass assignment). Digunakan ketika `$fillable` kosong.

**Contoh:**

```php
protected $guarded = ['id', 'created_at', 'updated_at'];
```

**Catatan:** Gunakan SALAH SATU dari `$fillable` atau `$guarded`. Best practice adalah menggunakan `$fillable` untuk whitelist kolom yang diizinkan.

---

### 5. `protected $casts`

**Tipe:** Array (key=kolom, value=tipe)  
**Default:** `[]`

Definisi automatic type casting untuk setiap atribut. Tipe yang didukung:

- `'int'` atau `'integer'` → cast ke integer
- `'float'` → cast ke float
- `'bool'` atau `'boolean'` → cast ke boolean
- `'string'` → cast ke string
- `'array'` → JSON decode ke array, atau return array jika sudah array
- `'json'` → JSON decode ke array
- `'object'` → JSON decode ke stdClass
- `'date'` atau `'datetime'` → create DateTime object (atau null jika invalid)

**Contoh:**

```php
protected $casts = [
    'datebirth'  => 'date',
    'id_job'     => 'int',
    'is_active'  => 'bool',
    'metadata'   => 'json'
];
```

Ketika mengakses atribut yang di-cast, otomatis dikonversi ke tipe yang ditentukan.

---

### 6. `protected $timestamps`

**Tipe:** Boolean  
**Default:** `true`

Jika `true`, `BaseModel` otomatis menambahkan dan memperbarui kolom `created_at` dan `updated_at` pada setiap `save()`.

**Contoh:**

```php
protected $timestamps = true; // otomatis isi created_at dan updated_at
protected $timestamps = false; // manual isi kolom waktu
```

---

### 7. `protected $softDelete`

**Tipe:** Boolean  
**Default:** `false`

Jika `true`, `delete()` akan melakukan soft delete (set kolom `deleted_at`) bukan hard delete. Query otomatis mengabaikan baris dengan `deleted_at != null`.

**Contoh:**

```php
protected $softDelete = true; // hapus dengan menandai deleted_at
protected $softDelete = false; // hapus langsung dari tabel
```

---

### 8. `protected $deletedAtColumn`

**Tipe:** String  
**Default:** `'deleted_at'`

Nama kolom untuk soft delete. Hanya digunakan jika `$softDelete = true`.

**Contoh:**

```php
protected $deletedAtColumn = 'deleted_at';
// atau custom:
protected $deletedAtColumn = 'removed_at';
```

---

### 9. Properti Internal (jangan di-override)

- `protected $attributes = []` — menyimpan nilai atribut model
- `protected $original = []` — snapshot atribut setelah save (untuk mendeteksi perubahan)
- `protected $relations = []` — menyimpan relasi yang sudah di-load
- `protected $events = []` — daftar event listener instance
- `protected $db` — instance Database

---

## MEMBUAT MODEL BARU

### Contoh Minimal

```php
<?php

namespace App\Models;

class Residents_model extends BaseModel
{
    protected $table = 'residents';
    protected $primaryKey = 'id';
    protected $fillable = ['name', 'nik', 'placebirth', 'datebirth', 'id_job', 'id_marital'];
}
```

### Contoh Lengkap dengan Semua Fitur

```php
<?php

namespace App\Models;

class Residents_model extends BaseModel
{
    // Konfigurasi
    protected $table = 'residents';
    protected $primaryKey = 'id';
    protected $softDelete = true;
    protected $timestamps = true;
    protected $deletedAtColumn = 'deleted_at';

    // Mass assignment
    protected $fillable = ['name', 'nik', 'placebirth', 'datebirth', 'id_job', 'id_marital'];
    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];

    // Type casting
    protected $casts = [
        'datebirth' => 'date',
        'id_job'    => 'int',
        'id_marital' => 'int'
    ];

    // Custom mutator: sanitasi NIK
    public function setNikAttribute($value) {
        return preg_replace('/\D/', '', $value); // hapus non-digit
    }

    // Custom accessor: uppercase nama
    public function getNameAttribute($value) {
        return strtoupper($value);
    }
}
```

---

## METHOD PUBLIK - CRUD DAN QUERY

### 1. Constructor: `__construct($attributes = [], $db = null)`

**Signature:**

```php
public function __construct($attributes = [], $db = null)
```

**Parameter:**

- `$attributes` (array) — Atribut awal model (optional)
- `$db` (Database) — Instance Database custom (optional, untuk testing)

**Return:** Void (konstruktor)

**Perilaku:**

- Inisialisasi Database connection
- Infer nama tabel jika belum di-set
- Isi atribut awal jika diberikan
- Ambil profiler jika tersedia

**Contoh:**

```php
// Cara normal
$m = new Residents_model();

// Dengan atribut awal
$m = new Residents_model(['name' => 'Budi', 'nik' => '1234']);

// Dengan injeksi Database (untuk testing)
$db = Database::init();
$m = new Residents_model([], $db);
```

---

### 2. Mengatur Database Connection: `setDatabase($db)`

**Signature:**

```php
public function setDatabase($db)
```

**Parameter:**

- `$db` (Database) — Instance Database baru

**Return:** `$this` (untuk chaining)

**Perilaku:**
Mengganti koneksi database yang dipakai model setelah instansiasi.

**Contoh:**

```php
$m = new Residents_model();
$customDb = Database::init();
$m->setDatabase($customDb);
```

---

### 3. Mass Assignment: `fill($data)`

**Signature:**

```php
public function fill($data)
```

**Parameter:**

- `$data` (array) — Data yang akan diisi ke model

**Return:** `$this` (untuk chaining)

**Perilaku:**

- Mengisi atribut model dari array `$data`
- Menghormati `$fillable` (whitelist) atau `$guarded` (blacklist)
- Memanggil `setAttribute()` untuk setiap field (jadi mutator akan dijalankan)

**Contoh:**

```php
$m = new Residents_model();
$m->fill([
    'name'       => 'Budi',
    'nik'        => '12345678',
    'placebirth' => 'Bandarlampung',
    'datebirth'  => '1990-01-01'
]);
$m->save();

// Jika 'id' tidak ada di $fillable, akan diabaikan:
$m->fill(['id' => 999, 'name' => 'Andi']); // id akan diabaikan
```

---

### 4. Set Atribut Tunggal: `setAttribute($key, $value)`

**Signature:**

```php
public function setAttribute($key, $value)
```

**Parameter:**

- `$key` (string) — Nama atribut
- `$value` (mixed) — Nilai atribut

**Return:** `$this`

**Perilaku:**

- Mengecek apakah ada mutator `set{Field}Attribute`
- Jika ada, jalankan mutator dan gunakan hasil yang dikembalikannya
- Simpan ke `$attributes`

**Contoh:**

```php
$m = new Residents_model();
$m->setAttribute('name', 'Budi');
$m->setAttribute('nik', '12345678'); // akan dijalankan setNikAttribute() jika ada

// Lebih sederhana menggunakan magic setter:
$m->name = 'Budi';
$m->nik = '12345678';
```

---

### 5. Get Atribut Tunggal: `getAttribute($key)`

**Signature:**

```php
public function getAttribute($key)
```

**Parameter:**

- `$key` (string) — Nama atribut

**Return:** mixed (nilai atribut, atau null jika tidak ada)

**Perilaku:**

- Mengecek apakah ada accessor `get{Field}Attribute`
- Jika ada, jalankan accessor
- Terapkan casting sesuai `$casts` jika ada
- Return nilai

**Contoh:**

```php
$m = new Residents_model();
$m->name = 'budi';
echo $m->getAttribute('name'); // output: "BUDI" (jika ada getNameAttribute)

// Lebih sederhana menggunakan magic getter:
echo $m->name;
```

---

### 6. Save Model: `save()`

**Signature:**

```php
public function save()
```

**Parameter:** Tidak ada

**Return:** mixed

- Pada insert: return `insert_id` (integer > 0)
- Pada update: return `true` (eksekusi berhasil) atau `false` (gagal)

**Perilaku:**

- Cek apakah model sudah ada (`exists()`)
- Jika ada: panggil `update()`
- Jika tidak: panggil `insertNew()`
- Trigger events: `before:save`, `before:create`/`before:update`, `after:create`/`after:update`, `after:save`
- Jika gagal: trigger `after:create:failed` atau `after:update:failed`
- Sync atribut ke `$original` jika berhasil

**Contoh Insert:**

```php
$m = new Residents_model();
$m->fill(['name' => 'Budi', 'nik' => '12345678']);
$id = $m->save();
if ($id) {
    echo "Berhasil insert, ID: $id";
} else {
    echo "Gagal insert";
}
```

**Contoh Update:**

```php
$m = (new Residents_model())->find(4563);
if ($m) {
    $m->name = 'BUDI UPDATE';
    $ok = $m->save();
    if ($ok) {
        echo "Berhasil update";
    } else {
        echo "Gagal update";
    }
}
```

---

### 7. Find by Primary Key: `find($id)`

**Signature:**

```php
public function find($id)
```

**Parameter:**

- `$id` (mixed) — Nilai primary key (bisa integer atau string)

**Return:** BaseModel instance atau null

**Perilaku:**

- Query database dengan kondisi `WHERE pk = $id`
- Jika `softDelete=true`: tambahkan `WHERE deleted_at IS NULL`
- Jika ditemukan: return model instance dengan atribut ter-set dan ter-sync
- Jika tidak: return null

**Contoh:**

```php
$m = (new Residents_model())->find(4563);
if ($m) {
    echo "Nama: " . $m->name;
    echo "NIK: " . $m->nik;
} else {
    echo "Record tidak ditemukan";
}

// Dengan UUID atau string PK:
$m = (new Residents_model())->find('abc-def-123');
```

---

### 8. Find by Custom Column: `findBy($field, $value)`

**Signature:**

```php
public function findBy($field, $value)
```

**Parameter:**

- `$field` (string) — Nama kolom
- `$value` (mixed) — Nilai yang dicari

**Return:** BaseModel instance atau null

**Perilaku:**

- Query database dengan kondisi `WHERE $field = $value`
- Jika `softDelete=true`: tambahkan `WHERE deleted_at IS NULL`
- Return model instance atau null

**Contoh:**

```php
// Cari berdasarkan NIK
$m = (new Residents_model())->findBy('nik', '1802266807918881');
if ($m) {
    echo "Nama: " . $m->name;
}

// Cari berdasarkan id_job
$m = (new Residents_model())->findBy('id_job', 5);
```

---

### 9. Delete Model: `delete()`

**Signature:**

```php
public function delete()
```

**Parameter:** Tidak ada

**Return:** mixed (hasil query atau false jika gagal)

**Perilaku:**

- Trigger event `before:delete`
- Cek apakah model punya primary key, jika tidak: throw Exception
- Jika `softDelete=true`: UPDATE kolom `deleted_at` dengan timestamp
- Jika `softDelete=false`: DELETE record dari tabel
- Jika berhasil: trigger `after:delete`
- Jika gagal (exception): trigger `after:delete:failed` dan return false

**Contoh (Soft Delete):**

```php
$m = (new Residents_model())->find(4563);
if ($m) {
    $ok = $m->delete(); // set deleted_at, tidak hapus fisik
    if ($ok) {
        echo "Record ditandai terhapus";
    }
}
```

**Contoh (Hard Delete):**

```php
class User_model extends BaseModel {
    protected $softDelete = false; // hard delete
}

$m = (new User_model())->find(1);
if ($m) {
    $ok = $m->delete(); // hapus langsung dari DB
}
```

**Catatan:** Method ini akan throw Exception jika primary key tidak ter-set. Untuk menghindari exception, gunakan `deleteById($id)`.

---

## METHOD PUBLIK - HELPERS UTILITY

### 10. Delete by ID: `deleteById($id)`

**Signature:**

```php
public function deleteById($id)
```

**Parameter:**

- `$id` (mixed) — Primary key value

**Return:** mixed (hasil delete atau false jika tidak ditemukan)

**Perilaku:**

- Cari record dengan `find($id)`
- Jika ditemukan: panggil `delete()` pada model
- Jika tidak ditemukan: return false
- **Tidak akan throw exception** meskipun tidak ditemukan

**Contoh:**

```php
$ok = (new Residents_model())->deleteById(4563);
if ($ok) {
    echo "Berhasil dihapus";
} else {
    echo "Record tidak ditemukan";
}
```

---

### 11. Find by Custom Column (Chaining): `findBy($field, $value)`

Sudah dijelaskan di section CRUD di atas (#8).

---

### 12. Update or Create: `updateOrCreate(array $search, array $data)`

**Signature:**

```php
public function updateOrCreate($search, $data)
```

**Parameter:**

- `$search` (array) — Kondisi pencarian (key=kolom, value=nilai)
- `$data` (array) — Data yang akan di-set/update

**Return:** BaseModel instance (baik dari update atau create)

**Perilaku:**

- Query dengan kondisi dari `$search`
- Jika ditemukan: update dengan merge existing row + `$data`, lalu save
- Jika tidak: buat baru dengan merge `$search` + `$data`, lalu save

**Contoh:**

```php
// Update jika NIK sudah ada, create jika belum
$m = (new Residents_model())->updateOrCreate(
    ['nik' => '1802266807918881'], // kondisi pencarian
    ['name' => 'SURIP UPDATED']     // data update/create
);
echo $m->id;
```

---

### 13. First or Create: `firstOrCreate(array $attrs, array $values = [])`

**Signature:**

```php
public function firstOrCreate($attrs, $values = [])
```

**Parameter:**

- `$attrs` (array) — Atribut yang harus cocok
- `$values` (array) — Atribut tambahan saat create (optional)

**Return:** BaseModel instance

**Perilaku:**

- Query dengan kondisi dari `$attrs`
- Jika ditemukan: return model tersebut
- Jika tidak: create baru dengan merge `$attrs` + `$values`, lalu return

**Contoh:**

```php
// Cari atau buat jika tidak ada
$m = (new Residents_model())->firstOrCreate(
    ['nik' => '1802266807918881'], // atribut pencarian
    ['name' => 'Default Name']     // atribut create
);
```

---

### 14. Refresh Model: `refresh()`

**Signature:**

```php
public function refresh()
```

**Parameter:** Tidak ada

**Return:** `$this` (untuk chaining)

**Perilaku:**

- Reload atribut model dari database berdasarkan primary key
- Gunakan ketika ada perubahan eksternal atau operasi bulk lain
- Sync atribut ke `$original`

**Contoh:**

```php
$m = (new Residents_model())->find(4563);
// ... ada operasi lain yang memodifikasi record ...
$m->refresh(); // reload dari DB
echo $m->name; // nilai terbaru dari DB
```

---

## METHOD PUBLIK - EVENTS

### 15. Register Event Listener: `on($event, callable $callback)`

**Signature:**

```php
public function on($event, $callback)
```

**Parameter:**

- `$event` (string) — Nama event
- `$callback` (callable) — Callback function/closure

**Return:** `$this` (untuk chaining)

**Perilaku:**

- Register listener untuk event instance tertentu
- Ketika event di-trigger, callback akan dipanggil dengan parameter `$this` (model instance)

**Event yang tersedia:**

- `before:save` — sebelum save (insert atau update)
- `after:save` — sesudah save sukses
- `before:create` — sebelum insert
- `after:create` — sesudah insert sukses
- `after:create:failed` — insert gagal
- `before:update` — sebelum update
- `after:update` — sesudah update sukses
- `after:update:failed` — update gagal
- `before:delete` — sebelum delete
- `after:delete` — sesudah delete sukses
- `after:delete:failed` — delete gagal

**Contoh:**

```php
$m = (new Residents_model())->find(4563);

// Logging sebelum delete
$m->on('before:delete', function($model) {
    error_log("Menghapus record: " . json_encode($model->toArray()));
});

// Notifikasi setelah update
$m->on('after:update', function($model) {
    echo "Record " . $model->id . " berhasil diupdate";
});

// Validasi sebelum save
$m->on('before:save', function($model) {
    if (!$model->name) {
        throw new Exception("Nama tidak boleh kosong");
    }
});

$m->delete(); // akan trigger before:delete event
```

---

### 16. Remove Event Listener: `off($event, $callback = null)`

**Signature:**

```php
public function off($event, $callback = null)
```

**Parameter:**

- `$event` (string) — Nama event
- `$callback` (callable, optional) — Callback yang ingin dihapus; jika null, hapus semua

**Return:** `$this` (untuk chaining)

**Perilaku:**

- Jika `$callback` diberikan: hapus listener spesifik
- Jika `$callback` null: hapus semua listener untuk event itu

**Contoh:**

```php
$callback = function($model) { echo "Dihapus"; };

$m = (new Residents_model())->find(4563);
$m->on('after:delete', $callback);
// ... kemudian ...
$m->off('after:delete', $callback); // hapus listener spesifik

// atau hapus semua listener untuk event:
$m->off('after:delete');
```

---

## METHOD PUBLIK - SERIALISASI

### 17. Convert to Array: `toArray()`

**Signature:**

```php
public function toArray()
```

**Parameter:** Tidak ada

**Return:** array

**Perilaku:**

- Konversi semua atribut model ke array
- Jika ada relasi yang sudah di-set: convert relasi juga (recursive)
- Normalize DateTime ke string format `Y-m-d H:i:s`
- Return array

**Contoh:**

```php
$m = (new Residents_model())->find(4563);
$array = $m->toArray();
print_r($array);
// Output:
// Array
// (
//     [id] => 4563
//     [name] => SURIP
//     [nik] => 1802266807918881
//     [placebirth] => METRO
//     [datebirth] => 1970-08-28 (DateTime dinormalkan ke string)
//     ...
// )

// Dengan relasi:
$m->setRelation('job', $jobModel);
$array = $m->toArray();
// array akan berisi juga key 'job' dengan data job model
```

---

### 18. Convert to JSON: `toJson($options = 0)`

**Signature:**

```php
public function toJson($options = 0)
```

**Parameter:**

- `$options` (int) — JSON encode options (optional)

**Return:** string (JSON)

**Perilaku:**

- Panggil `toArray()`
- JSON encode hasilnya dengan options tertentu
- Return JSON string

**Contoh:**

```php
$m = (new Residents_model())->find(4563);
$json = $m->toJson();
echo $json;
// Output: {"id":4563,"name":"SURIP","nik":"1802266807918881",...}

// Dengan pretty print:
$json = $m->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
echo $json;
```

---

## METHOD PUBLIK - TRANSACTIONS

### 19. Begin Transaction: `beginTransaction()`

**Signature:**

```php
public function beginTransaction()
```

**Parameter:** Tidak ada

**Return:** result dari Database::beginTransaction() (boolean atau void)

**Perilaku:**

- Proxy ke `Database::beginTransaction()`
- Mulai transaksi database

**Contoh:**

```php
$m = new Residents_model();
$m->beginTransaction();
try {
    $rec = $m->find(4563);
    $rec->delete();

    $rec2 = $m->find(4564);
    $rec2->delete();

    $m->commit();
} catch (Exception $e) {
    $m->rollBack();
    echo "Gagal: " . $e->getMessage();
}
```

---

### 20. Commit Transaction: `commit()`

**Signature:**

```php
public function commit()
```

**Parameter:** Tidak ada

**Return:** result dari Database::commit()

**Perilaku:**

- Proxy ke `Database::commit()`
- Commit transaksi (save semua perubahan)

Lihat contoh di `beginTransaction()` di atas.

---

### 21. Rollback Transaction: `rollBack()`

**Signature:**

```php
public function rollBack()
```

**Parameter:** Tidak ada

**Return:** result dari Database::rollBack()

**Perilaku:**

- Proxy ke `Database::rollBack()`
- Batalkan transaksi (discard semua perubahan)

Lihat contoh di `beginTransaction()` di atas.

---

## FITUR TAMBAHAN: CASTING, ACCESSOR, MUTATOR

### Automatic Type Casting dengan `$casts`

Definisikan tipe data di properti `$casts`, dan `BaseModel` akan otomatis convert.

**Tipe yang didukung:**

| Tipe               | Hasil                            |
| ------------------ | -------------------------------- |
| `int`, `integer`   | Cast ke integer                  |
| `float`            | Cast ke float                    |
| `bool`, `boolean`  | Cast ke boolean                  |
| `string`           | Cast ke string                   |
| `array`            | JSON decode atau return as array |
| `json`             | JSON decode ke associative array |
| `object`           | JSON decode ke stdClass          |
| `date`, `datetime` | Create DateTime object atau null |

**Contoh:**

```php
class Residents_model extends BaseModel
{
    protected $casts = [
        'datebirth'  => 'date',    // otomatis jadi DateTime
        'id_job'     => 'int',     // otomatis jadi integer
        'id_marital' => 'int',     // otomatis jadi integer
        'metadata'   => 'json',    // otomatis jadi array
        'is_active'  => 'bool'     // otomatis jadi boolean
    ];
}

// Usage:
$m = (new Residents_model())->find(4563);
var_dump($m->datebirth);  // DateTime object, bukan string
var_dump($m->id_job);      // integer 5, bukan string '5'
var_dump($m->is_active);   // boolean true, bukan string
```

---

### Custom Accessor: `get{Field}Attribute`

Buat method dengan nama `get{Field}Attribute` untuk custom getter. Method ini dipanggil setiap kali atribut di-akses.

**Format:** `public function get{CamelCaseField}Attribute($value)`

**Contoh:**

```php
class Residents_model extends BaseModel
{
    // Uppercase nama saat diakses
    public function getNameAttribute($value) {
        return strtoupper($value);
    }

    // Format NIK dengan separator
    public function getNikAttribute($value) {
        return substr($value, 0, 6) . '-' . substr($value, 6, 6) . '-' . substr($value, 12);
    }
}

// Usage:
$m = new Residents_model();
$m->name = 'budi';
echo $m->name; // Output: "BUDI" (uppercase otomatis)
```

---

### Custom Mutator: `set{Field}Attribute`

Buat method dengan nama `set{Field}Attribute` untuk custom setter. Method ini dipanggil setiap kali atribut di-set.

**Format:** `public function set{CamelCaseField}Attribute($value)`

**Return:** Nilai yang sudah diproses

**Contoh:**

```php
class Residents_model extends BaseModel
{
    // Sanitasi NIK: hapus non-digit
    public function setNikAttribute($value) {
        return preg_replace('/\D/', '', $value);
    }

    // Hash password
    public function setPasswordAttribute($value) {
        return password_hash($value, PASSWORD_BCRYPT);
    }
}

// Usage:
$m = new Residents_model();
$m->nik = '1802-2668-0791'; // setNikAttribute akan sanitasi
echo $m->nik; // Output: "1802266807918881" (tanpa dash)
```

---

## FITUR TAMBAHAN: MASS ASSIGNMENT

Mass assignment adalah pengisian multiple atribut sekaligus. `BaseModel` melindungi dengan `$fillable` dan `$guarded`.

### Whitelist dengan `$fillable`

Hanya kolom yang tercantum yang boleh di-fill:

```php
class Residents_model extends BaseModel
{
    protected $fillable = ['name', 'nik', 'placebirth', 'datebirth'];
}

$m = new Residents_model();
$m->fill(['name' => 'Budi', 'nik' => '123', 'id' => 999, 'password' => 'secret']);
// Hanya 'name' dan 'nik' yang ter-set; 'id' dan 'password' diabaikan
```

### Blacklist dengan `$guarded`

Semua kolom kecuali yang tercantum boleh di-fill:

```php
class Residents_model extends BaseModel
{
    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];
}

$m = new Residents_model();
$m->fill(['name' => 'Budi', 'id' => 999]); // 'id' diabaikan, 'name' di-set
```

**Best Practice:** Gunakan `$fillable` (whitelist) lebih baik daripada `$guarded`.

---

## FITUR TAMBAHAN: SOFT DELETE

Soft delete menandai record sebagai terhapus tanpa menghapus fisik dari DB.

**Setup:**

```php
class Residents_model extends BaseModel
{
    protected $softDelete = true;
    protected $deletedAtColumn = 'deleted_at';
}
```

**Behavior:**

- `delete()` akan `UPDATE deleted_at = current_timestamp` bukan `DELETE`
- `find()` dan `query()` otomatis filter `WHERE deleted_at IS NULL`
- Record masih ada di DB tapi tersembunyi dari query normal

**Contoh:**

```php
$m = (new Residents_model())->find(4563);
$m->delete(); // set deleted_at, bukan hapus

// find() tidak akan menemukan record terhapus:
$m = (new Residents_model())->find(4563); // return null

// Untuk query record terhapus, gunakan QueryBuilder langsung:
$allRecs = Database::init()->table('residents')->getResultArray(); // termasuk deleted
```

---

## CONTOH IMPLEMENTASI LENGKAP DI CONTROLLER

### Contoh 1: CRUD Sederhana

```php
<?php

class Residents extends Controller
{
    public function create()
    {
        // GET form
        return View::render('residents/create');
    }

    public function store()
    {
        // POST create
        $input = Request::init()->input();

        $m = new Residents_model();
        $m->fill($input);
        $id = $m->save();

        if ($id) {
            return Response::json(['id' => $id], 201);
        }
        return Response::json(['error' => 'Gagal menyimpan'], 500);
    }

    public function show($id)
    {
        // GET detail
        $m = (new Residents_model())->find($id);
        if (!$m) {
            return Response::json(['error' => 'Tidak ditemukan'], 404);
        }
        return Response::json($m->toArray());
    }

    public function update($id)
    {
        // PUT update
        $input = Request::init()->input();

        $m = (new Residents_model())->find($id);
        if (!$m) {
            return Response::json(['error' => 'Tidak ditemukan'], 404);
        }

        $m->fill($input);
        $ok = $m->save();

        if ($ok) {
            return Response::json(['status' => 'ok']);
        }
        return Response::json(['error' => 'Gagal update'], 500);
    }

    public function delete($id)
    {
        // DELETE remove
        $ok = (new Residents_model())->deleteById($id);
        if ($ok) {
            return Response::json(['status' => 'deleted']);
        }
        return Response::json(['error' => 'Tidak ditemukan'], 404);
    }
}
```

### Contoh 2: Dengan Event dan Validasi

```php
<?php

class Residents extends Controller
{
    public function store()
    {
        $input = Request::init()->input();

        $m = new Residents_model();

        // Validasi sebelum save
        $m->on('before:save', function($model) {
            if (!$model->name) {
                throw new Exception('Nama harus diisi');
            }
            if (!$model->nik || strlen($model->nik) != 16) {
                throw new Exception('NIK harus 16 digit');
            }
        });

        // Logging setelah create
        $m->on('after:create', function($model) {
            error_log("Resident baru dibuat: ID=" . $model->id);
        });

        try {
            $m->fill($input);
            $id = $m->save();
            return Response::json(['id' => $id], 201);
        } catch (Exception $e) {
            return Response::json(['error' => $e->getMessage()], 400);
        }
    }
}
```

### Contoh 3: Dengan Transaction

```php
<?php

class Residents extends Controller
{
    public function batchDelete($ids)
    {
        // DELETE multiple records
        $m = new Residents_model();
        $m->beginTransaction();

        try {
            foreach ($ids as $id) {
                $rec = $m->find($id);
                if ($rec) {
                    $rec->delete();
                }
            }
            $m->commit();
            return Response::json(['status' => 'ok', 'count' => count($ids)]);
        } catch (Exception $e) {
            $m->rollBack();
            return Response::json(['error' => $e->getMessage()], 500);
        }
    }
}
```

---

## BEST PRACTICES

1. **Gunakan `$fillable` bukan `$guarded`**

   - Whitelist lebih aman daripada blacklist

2. **Selalu check return value `save()` dan `delete()`**

   ```php
   $id = $m->save();
   if ($id === false) { /* handle error */ }
   ```

3. **Gunakan `deleteById($id)` bukan `find()->delete()`**

   - `deleteById()` tidak throw exception saat tidak ditemukan
   - Lebih clean dan aman

4. **Leverage Event untuk Logging dan Validasi**

   ```php
   $m->on('before:delete', function($model) {
       error_log("Delete record: " . $model->id);
   });
   ```

5. **Use Transaction untuk Multi-Step Operations**

   ```php
   $m->beginTransaction();
   try {
       // multiple operations
       $m->commit();
   } catch (Exception $e) {
       $m->rollBack();
   }
   ```

6. **Define `$casts` untuk Type Safety**

   ```php
   protected $casts = ['datebirth' => 'date', 'id_job' => 'int'];
   ```

7. **Use Mutator untuk Sanitasi Input**

   ```php
   public function setNikAttribute($value) {
       return preg_replace('/\D/', '', $value);
   }
   ```

8. **Jangan Override Method Internal**
   - Jangan panggil `insertNew()`, `update()`, `getChangedAttributes()` langsung
   - Gunakan public API: `save()`, `delete()`, `find()`

---

## FAQ DAN TROUBLESHOOTING

### Q1: Error "Cannot delete: primary key not set on model"

**A:** Anda memanggil `delete()` pada model yang tidak punya primary key.

**Solusi:**

```php
// Salah:
$m = new Residents_model();
$m->delete(); // Error!

// Benar:
$m = (new Residents_model())->find(4563);
$m->delete();

// atau gunakan helper:
(new Residents_model())->deleteById(4563);
```

---

### Q2: `save()` tidak menyimpan perubahan, padahal tidak ada error

**A:** Jika tidak ada atribut yang berubah, `update()` akan return `true` (no-op).

**Debug:**

```php
$m = (new Residents_model())->find(4563);
$m->name = 'Budi'; // ubah
$ok = $m->save();
if ($ok) echo "Saved";
```

---

### Q3: Casting date tidak bekerja, hasilnya null

**A:** Format tanggal di DB tidak valid untuk DateTime parser.

**Solusi:**

```php
protected $casts = ['datebirth' => 'date'];

// Pastikan format di DB adalah: YYYY-MM-DD atau format valid lain
// Jika format tidak valid, accessor akan return null
```

---

### Q4: Mutator/Accessor tidak dipanggil

**A:** Nama method harus benar (camelCase field name).

**Contoh:**

```php
// Field 'date_birth'
// Accessor harus: getDatebirthAttribute (bukan getDateBirthAttribute)
public function getDatebirthAttribute($value) { ... }

// Field 'nik'
// Mutator harus: setNikAttribute
public function setNikAttribute($value) { ... }
```

---

### Q5: Soft delete tidak bekerja, record masih terlihat

**A:** Pastikan `$softDelete = true` dan tabel punya kolom `deleted_at`.

**Check:**

```php
class Residents_model extends BaseModel {
    protected $softDelete = true; // harus true
    protected $deletedAtColumn = 'deleted_at'; // harus ada di tabel
}
```

---

### Q6: Event listener tidak dipanggil

**A:** Event hanya untuk instance-level, bukan global/static.

**Benar:**

```php
$m = (new Residents_model())->find(123);
$m->on('after:delete', function($model) { ... });
$m->delete();
```

**Salah:**

```php
// Tidak bisa register listener di class level
// atau expect listener untuk semua instance
```

---

### Q7: Mass assignment tidak memasukkan kolom tertentu

**A:** Kolom tersebut tidak ada di `$fillable` (whitelist mode).

**Solusi:**

```php
class Residents_model extends BaseModel {
    protected $fillable = ['name', 'nik', 'placebirth', 'datebirth'];
    // 'id' tidak ada, jadi tidak akan di-fill
}

// Untuk fill kolom tertentu saja:
$m->fill(['name' => 'A', 'nik' => 'B']);
```

---

## RINGKASAN QUICK REFERENCE

| Operation              | Method                           | Return                   |
| ---------------------- | -------------------------------- | ------------------------ |
| Buat instance          | `new Model()`                    | instance                 |
| Isi atribut            | `fill($data)`                    | $this                    |
| Simpan (insert/update) | `save()`                         | insert_id / true / false |
| Cari by PK             | `find($id)`                      | instance / null          |
| Cari by field          | `findBy($field, $value)`         | instance / null          |
| Hapus (soft/hard)      | `delete()`                       | mixed / false            |
| Hapus by ID            | `deleteById($id)`                | mixed / false            |
| Update or Create       | `updateOrCreate($search, $data)` | instance                 |
| First or Create        | `firstOrCreate($attrs, $values)` | instance                 |
| Reload from DB         | `refresh()`                      | $this                    |
| Event register         | `on($event, $callback)`          | $this                    |
| Event unregister       | `off($event, $callback)`         | $this                    |
| To Array               | `toArray()`                      | array                    |
| To JSON                | `toJson()`                       | string                   |
| Begin TX               | `beginTransaction()`             | mixed                    |
| Commit TX              | `commit()`                       | mixed                    |
| Rollback TX            | `rollBack()`                     | mixed                    |

---

**END OF DOCUMENTATION**

Dokumentasi ini mencakup semua fitur `BaseModel`. Untuk pertanyaan lebih lanjut atau laporan bug, hubungi tim development.
