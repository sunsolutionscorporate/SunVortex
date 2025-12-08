# File Utility — `system/Support/File.php`

**Bahasa:** Bahasa Indonesia

**Tujuan:** Dokumentasi lengkap dan terstruktur untuk class `File` (helper utilitas file dan pemindaian direktori). Disusun agar konsisten dengan gaya dokumentasi `doc/DATABASE_CORE.md` (TOC, per-section detail, contoh nyata, best-practices).

---

## Daftar Isi

1. Ringkasan
2. Persyaratan
3. Instansiasi & Konsep Dasar
4. API — Daftar Method & Detail
   - scanFiles
   - getMimeType
   - extension
   - raw / toArray
   - exists
   - getContents
   - putContents
   - delete
   - size
   - mimeType
   - getAbsolutePath
   - getRelativePath
5. Contoh Penggunaan Lengkap
6. Best Practices & Catatan Keamanan
7. Tips Performansi
8. Quick-check / Tes Singkat
9. Referensi & Lanjutan

---

## 1. Ringkasan

Class `File` menyediakan helper utilitas untuk operasi file umum (baca, tulis, hapus, ukuran, mime) dan fungsi pemindaian direktori (`scanFiles`). Dirancang untuk bekerja dengan path absolut maupun relatif (menggunakan `basePath` pada konstruktor).

## 2. Persyaratan

- PHP >= 7.3
- Extension `fileinfo` direkomendasikan (untuk deteksi MIME via `finfo`).

## 3. Instansiasi & Konsep Dasar

**Signature konstruktor**

```php
new \System\Support\File(array $files = [], string $basePath = '')
```

Parameter:

- `$files` — (opsional) array metadata awal. Format elemen: `['name' => string, 'ext' => string, 'location' => string]`.
- `$basePath` — (opsional) path root untuk meresolusi path relatif. Dinormalisasi (forward-slashes) dan diberi trailing slash otomatis.

Contoh:

```php
use System\Support\File;

$f = new File([], __DIR__ . '/../');
```

## 4. API — Daftar Method & Detail

> Semua contoh menggunakan namespace `System\Support\File`.

### scanFiles

- Signature:

```php
public static function scanFiles(string $dir, $extensionFilter = 'php', bool $recursive = true): self
```

- Deskripsi: Memindai direktori dan mengembalikan instance `File` yang memuat daftar file yang cocok.
- Parameter:
  - `$dir` (string): direktori root.
  - `$extensionFilter` (string|array|null): ekstensi (tanpa titik) atau array ekstensi. `null` = semua file.
  - `$recursive` (bool): `true` untuk rekursif.
- Return: `self` (instance `File`).
- Format item hasil:

```php
[
  'name' => 'basename tanpa ekstensi',
  'ext' => 'php',
  'location' => '/abs/path/to/file.php'
]
```

- Contoh:

```php
$files = File::scanFiles(__DIR__ . '/../app', ['php','html']);
foreach ($files->toArray() as $row) {
    echo $row['location'] . PHP_EOL;
}
```

- Edge-cases & Catatan:
  - Untuk folder besar, gunakan `$extensionFilter` dan/atau `$recursive = false` untuk mengurangi I/O.
  - Menggunakan SPL iterators — kompatibel Linux/Windows.

### getMimeType

- Signature:

```php
public static function getMimeType(string $file): string
```

- Deskripsi: Mengembalikan MIME type. Jika `finfo` tersedia dan file ada, `finfo` diprioritaskan. Jika tidak tersedia, fallback menggunakan map ekstensi internal. Jika tidak dikenali, mengembalikan `application/octet-stream`.

- Contoh:

```php
echo File::getMimeType('/var/www/public/images/logo.png'); // image/png
```

- Catatan:
  - Jika path tidak mengarah ke file yang ada, deteksi berbasis ekstensi.

### extension

- Signature:

```php
public static function extension(string $file): string
```

- Deskripsi: Mengembalikan ekstensi file (lowercase, tanpa titik).

- Contoh:

```php
echo File::extension('document.PDF'); // pdf
```

### raw / toArray

- Signature:

```php
public function raw(): array
public function toArray(): array
```

- Deskripsi: Mengembalikan array internal `$files` (hasil `scanFiles` atau input konstruktor).

### exists

- Signature:

```php
public function exists(string $path): bool
```

- Deskripsi: Mengecek apakah path ada; jika relatif, akan di-resolve terhadap `basePath`.

- Contoh:

```php
$f = new File([], __DIR__ . '/../');
var_dump($f->exists('README.md'));
```

### getContents

- Signature:

```php
public function getContents(string $path)
```

- Deskripsi: Membaca isi file dan mengembalikan string, atau `false` jika file tidak ditemukan.
- Catatan: Method ini tidak melempar exception; periksa nilai kembalian.

### putContents

- Signature:

```php
public function putContents(string $path, $data, $flags = 0)
```

- Deskripsi: Menulis data ke file. Membuat direktori parent bila perlu (mode 0755). Mengembalikan jumlah byte yang ditulis atau `false`.

- Contoh:

```php
$f = new File([], __DIR__ . '/../');
$f->putContents('storage/tmp/hello.txt', "Halo dunia\n", FILE_APPEND);
```

- Catatan: Periksa permission saat menulis di environment produksi.

### delete

- Signature: `public function delete(string $path): bool`
- Deskripsi: Menghapus file jika ada.

### size

- Signature: `public function size(string $path)`
- Deskripsi: Mengembalikan ukuran file (bytes) atau `false` bila tidak ada.

### mimeType

- Signature: `public function mimeType(string $file): string`
- Deskripsi: Wrapper instance ke `getMimeType`, berguna saat bekerja dengan `basePath`.

### getAbsolutePath

- Signature: `public function getAbsolutePath(string $path): string`
- Deskripsi: Mengkonversi path relatif menjadi absolut berdasarkan `basePath`. Jika input sudah absolut (Unix `/' atau Windows drive-letter), dikembalikan setelah normalisasi.

### getRelativePath

- Signature: `public function getRelativePath(string $path): string`
- Deskripsi: Jika `basePath` diset dan path absolut mengandung `basePath`, mengembalikan path relatif.

## 5. Contoh Penggunaan Lengkap

1. Memindai project dan menyimpan manifest file PHP

```php
use System\Support\File;

$projectRoot = __DIR__ . '/..';
$files = File::scanFiles($projectRoot . '/app', 'php');

$manifest = array_map(function($f) { return $f['location']; }, $files->toArray());
file_put_contents($projectRoot . '/storage/cache/php-files.json', json_encode($manifest, JSON_PRETTY_PRINT));
```

2. Menulis log harian dengan basePath

```php
$file = new File([], __DIR__ . '/..');
$file->putContents('storage/logs/daily.log', date('c') . " - Test log\n", FILE_APPEND);
```

3. Scan non-recursive untuk aset statis

```php
$assets = File::scanFiles(__DIR__ . '/public', ['js','css'], false);
foreach ($assets->toArray() as $a) {
    // proses asset
}
```

## 6. Best Practices & Catatan Keamanan

- Validasi path yang berasal dari input user untuk mencegah directory traversal (`../`).
- Jangan set `basePath` dari input user tanpa validasi.
- Periksa permission direktori saat menulis file di production.
- Simpan file upload di direktori terkontrol dan sanitasi nama file.

## 7. Tips Performansi

- Untuk direktori besar: batasi ekstensi dan/atau non-recursive.
- Cache hasil `scanFiles` bila dipanggil sering (mis. simpan manifest di storage/cache).

## 8. Quick-check / Tes Singkat

Dari root project jalankan:

```powershell
php -r "require 'vendor/autoload.php'; $f = new \System\Support\File([], __DIR__); var_dump($f->exists('README.md'));"
```

## 9. Referensi & Lanjutan

- Lihat juga: `doc/API.md`, `doc/SUPPORT_UTILITIES.md` untuk integrasi.

---

_Dokumentasi diperbarui agar konsisten dengan gaya `doc/DATABASE_CORE.md`. Jika Anda ingin, saya bisa menambahkan unit tests (PHPUnit) untuk method kunci atau menyamakan format header di seluruh file `doc/`._

## 10. Advanced Options & Examples (pencarian konten dan nama)

Bagian ini menunjukkan contoh praktis penggunaan opsi lanjutan pada `scanFiles` untuk mencari file yang mengandung kata seperti `tes_` baik pada nama file maupun di isi file.

Catatan singkat tentang opsi tersedia di `scanFiles`: `namePattern`, `searchContent`, `contentRegex`, `caseSensitive`, `minSize`, `maxSize`, `modifiedAfter`, `modifiedBefore`, `maxReadBytes`, `forceContentSearch`.

10.1 Cari file yang mengandung kata `tes_` di ISI file (substring)

```php
use System\Support\File;

$results = File::scanFiles(__DIR__ . '/app', 'php', true, [
  'searchContent' => 'tes_',       // cari substring
  'caseSensitive' => false,         // tidak sensitif huruf
  'maxReadBytes'  => 1024 * 1024,   // baca sampai 1MB per file
]);

foreach ($results->toArray() as $r) {
  echo $r['location'] . " (" . $r['size'] . " bytes)\n";
}
```

Penjelasan: ini akan membaca isi file PHP hingga 1MB dan mencari substring `tes_` (case-insensitive). File biner besar dilewati; jika ingin memaksa baca, atur `forceContentSearch => true`.

10.2 Cari file yang namanya mengandung `tes_` (nama file tanpa ekstensi)

```php
$results = File::scanFiles(__DIR__ . '/app', ['php','inc'], true, [
  'namePattern' => '/tes_/',   // regex sederhana
]);

foreach ($results->toArray() as $r) echo $r['location'] . PHP_EOL;
```

10.3 Cari menggunakan regex pada isi file

```php
$results = File::scanFiles(__DIR__ . '/app', 'php', true, [
  'contentRegex' => '/\btes_\w+/i'  // kata yang dimulai dengan tes_
]);

foreach ($results->toArray() as $r) echo $r['location'] . PHP_EOL;
```

10.4 Kombinasi filter: nama + isi + batas ukuran

```php
$results = File::scanFiles(__DIR__ . '/app', 'php', true, [
  'namePattern'  => '/Controller$/i',
  'searchContent' => 'tes_',
  'maxReadBytes'  => 512 * 1024, // 512KB
  'minSize'       => 102,       // skip file < 102 bytes
]);
```

10.5 Quick-check via command line (PowerShell)

```powershell
php -r "require 'vendor/autoload.php'; $r = \System\Support\File::scanFiles(__DIR__.'/app','php',true,['searchContent'=>'tes_','maxReadBytes'=>1048576]); print_r(array_map(function($i){return $i['location'];}, $r->toArray()));"
```

10.6 Tips praktis

- Jika hasil pencarian terlalu lambat: batasi ekstensi dan non-recursive atau indeks hasil `scanFiles` ke file manifest di `storage/cache`.
- Gunakan `contentRegex` bila perlu pencarian pola kompleks; pastikan regex valid.
- Jangan set `forceContentSearch` pada direktori yang berisi banyak file biner (mis. vendor, node_modules).

---

Jika Anda setuju, saya akan:

- Menambahkan contoh-contoh ini ke bagian awal `doc/INDEX.md` sebagai "I want to... find files containing 'tes\_'" task.
- Menambahkan unit-test PHPUnit sederhana untuk `searchContent` dan `contentRegex`.

Beritahu saya langkah selanjutnya yang Anda inginkan.
