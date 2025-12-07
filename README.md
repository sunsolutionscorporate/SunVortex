# SunVortex Framework

Dokumentasi ini menjelaskan secara rinci framework ringan "SunVortex" yang berada di repository ini.
Dokumentasi ditulis dalam bahasa Indonesia dan ditujukan untuk developer yang ingin memahami, memasang, dan mengembangkan aplikasi menggunakan SunVortex.

**Versi:** Informasi versi ada pada `composer.json` dan file terkait paket.

## Ringkasan

SunVortex adalah micro-framework PHP yang dirancang untuk aplikasi web kecil hingga menengah. Tujuan utamanya:

- Ringkas dan mudah dipahami.
- Mudah dipakai untuk pembuatan CRUD, API, dan sistem modular.
- Menyediakan lapisan-lapisan penting: routing/bootstrap, controller, model, view, database (query builder), migrasi, dan seeder.

Framework menempatkan kode aplikasi di direktori `app/` dan fungsi core di `system/`.

## Struktur Proyek (Highlight)

- `app/` — Kode aplikasi (controllers, models, views, middleware).

  - `controllers/` — Controller MVC (contoh: `Residents.php`).
  - `models/` — Model aplikasi (contoh: `Residents_model.php`).
  - `views/` — Template view.

- `system/` — Core framework

  - `Autoload.php`, `Bootstrap.php`, `index.php` — entrypoint dan autoloading.
  - `Core/` — Kelas dasar (Controller, BaseModel, View, dsb).
  - `database/` — Abstraksi database, QueryBuilder, migrasi dan seeder.
  - `Http/` — Request, Response, Middleware.
  - `Support/` — Utility helpers (File, Collection, Helpers).

- `public/` — Public web root (file `index.php`).
- `storage/` — Penyimpanan file runtime (log, images, generated migrations/seeders).
- `doc/` — Dokumen tambahan fitur, panduan migrasi, seeder, dll.

## Instalasi & Persiapan

1. Pastikan server menggunakan PHP 7.3+ (atau sesuai requirement `composer.json`).
2. Fork/clone repository ke environment Anda. Contoh Windows (PowerShell):

```powershell
git clone <repo-url> C:\xampp\htdocs\sun
cd C:\xampp\htdocs\sun
composer install
```

3. Konfigurasi koneksi database: sesuaikan pengaturan di file konfigurasi environment atau di tempat konfigurasi database (tergantung implementasi proyek — cek `system/database/Database.php`).

4. Pastikan document root server mengarah ke `public/`.

## Cara Menjalankan (CLI dan Web)

- Untuk menjalankan aplikasi via browser: buka `http://localhost/sun/public/` (tergantung konfigurasi virtual host).
- Framework menyediakan entrypoint CLI `sun` (file `sun` pada root) — contoh perintah migrasi:

```bash
php sun migrate status
php sun migrate make:create create_users_table
php sun migrate run
php sun migrate rollback
php sun migrate make:seed DemoSeeder
php sun migrate seed DemoSeeder
```

Lihat dokumentasi di `doc/` untuk panduan migrasi dan seeder secara lengkap.

## Komponen Utama

Berikut penjelasan komponen utama yang ada di `system/`.

**Autoload & Bootstrap**

- `system/Autoload.php` — Autoloader PSR-like sederhana untuk memuat kelas dari `system/` dan `app/`.
- `system/Bootstrap.php` — Inisialisasi framework, memasang error handler, memuat konfigurasi, dan menyiapkan route/dispatcher.

**Core**

- `system/Core/Controller.php` — Base Controller yang bisa diperluas oleh controller aplikasi.
- `system/Core/BaseModel.php` — Base model dengan lapisan akses database dasar.
- `system/Core/View.php` — Renderer view sederhana.

**HTTP Layer**

- `system/Http/Request.php` — Objek request (input, headers, method, path).
- `system/Http/Response.php` — Object response untuk mengirimkan header & body.
- Middleware ada di `system/Http/Middleware/` (contoh: `Auth_Ms.php`, `Cors_Ms.php`) untuk memproses request sebelum controller dijalankan.

**Database & Query**

- `system/database/Database.php` — Koneksi PDO wrapper dan helper.
- `system/database/QueryBuilder.php` — Builder query untuk memudahkan pembuatan SELECT/INSERT/UPDATE/DELETE.
- `system/database/QueryManager.php` — Manajemen eksekusi query dan hasil.

**Migrasi & Seeder**

- `system/database/Migration/` berisi migrasi lengkap:
  - `Migration.php` — kelas dasar untuk membuat file migrasi.
  - `MigrationManager.php` — menjalankan migrate/rollback, menyimpan tabel `migrations`.
  - `Schema.php` — helper untuk membangun definisi kolom SQL (CREATE/ALTER).
  - `MigrationCLI.php` & `MigrationCLIEnhanced.php` — integrasi CLI untuk membuat dan menjalankan migrasi/seeder.
  - `Seeder.php` & `SeederManager.php` — sistem seeder/dummy-data.

Contoh alur migrasi singkat:

```bash
# Buat migration
php sun migrate make:create create_testing_table

# Jalankan migration (aplikasikan ke DB)
php sun migrate run

# Buat seeder
php sun migrate make:seed TestingSeeder

# Jalankan seeder tertentu
php sun migrate seed TestingSeeder

# Jalankan semua seeder
php sun migrate seed:all
```

## Contoh Kode Singkat

Contoh controller `app/controllers/Residents.php`:

```php
<?php
class Residents extends Controller {
	public function index() {
		$model = $this->loadModel('resident/Residents_model');
		$data = $model->all();
		return $this->view('resident/page', $data);
	}
}
```

Contoh model menggunakan BaseModel (`app/models/resident/Residents_model.php`):

```php
<?php
class Residents_model extends BaseModel {
	protected $table = 'residents';

	public function all() {
		return $this->db->table($this->table)->get();
	}
}
```

Contoh seeder sederhana (`storage/database/seeders/TestingSeeder.php`):

```php
<?php
use System\Database\Seeder;

class TestingSeeder extends Seeder {
	public function run() {
		$this->insert('testing', [
			'name' => 'Demo',
			'email' => 'demo@example.com'
		]);
	}
}
```

## Logging & Error Handling

- Framework menyediakan error handling dasar di `system/Bootstrap.php`.
- Gunakan `storage/.logs/` untuk menaruh log runtime dan pastikan folder dapat ditulis oleh webserver.

## Testing & Debugging

- Folder `tests/` memuat contoh script test sederhana.
- Untuk debugging aktifkan display errors di environment development dan periksa log di `storage/.logs`.

## Best Practices

- Jangan menjalankan migrasi langsung di database produksi tanpa cadangan.
- Gunakan seeders hanya untuk data awal non-sensitif.
- Letakkan kode aplikasi di `app/` dan jangan mengedit core di `system/` kecuali Anda paham implikasinya.

## Kontribusi

- Jika Anda ingin berkontribusi, buat branch baru dan kirimkan PR yang jelas menjelaskan perubahan.
- Tambahkan dokumentasi dan/atau test untuk perubahan fitur.

## Dokumen Tambahan

Lihat direktori `doc/` untuk dokumentasi terperinci tentang migrasi (`MIGRATION_*`), seeder, dan penggunaan `BaseModel`.

---

Jika Anda ingin, saya bisa menambahkan contoh langkah-demi-langkah pembuatan endpoint CRUD lengkap atau menambahkan panduan deploy ke Apache / nginx.

© Tim Pengembang SunVortex

## Contoh CRUD Lengkap (Contoh Praktis)

Di bawah ini contoh alur pembuatan CRUD lengkap menggunakan file contoh yang sudah tersedia di repo:

- Controller: `app/controllers/ExampleCrud.php`
- Model: `app/models/example/Example_model.php`
- Views: `app/views/example/index.php`, `app/views/example/form.php`
- Migration (contoh): `storage/database/migrations/2025_12_07_101500_create_examples_table.php`
- Seeder (contoh): `storage/database/seeders/ExampleSeeder.php`

Langkah singkat menjalankan contoh di environment development:

1. Buat/migrasi tabel contoh:

```bash
php sun migrate run
```

2. Isi data demo menggunakan seeder:

```bash
php sun migrate seed ExampleSeeder
```

3. Akses endpoint CRUD:

- Daftar: `GET /examplecrud` — menampilkan daftar data.
- Buat: `GET /examplecrud/create` — menampilkan form; `POST /examplecrud/store` — menyimpan data.
- Edit: `GET /examplecrud/edit/{id}` — form edit; `POST /examplecrud/update/{id}` — simpan perubahan.
- Hapus: `GET /examplecrud/delete/{id}` — hapus data (endpoint ini sederhana; untuk produksi gunakan method yang sesuai dan proteksi CSRF).

Catatan: Routes/URL mapping bergantung pada sistem routing di `system/Bootstrap.php` dan konfigurasi Routes project Anda. Jika framework Anda menggunakan pola controller/method otomatis, akses URL sesuai konvensi tersebut. Sesuaikan route jika perlu di sistem routing.

## Contoh Konfigurasi `.env`

Berikut contoh variabel environment yang umum dipakai untuk menjalankan SunVortex. Simpan di file `.env` pada root (jika implementasi Autoload/Bootstrap membaca file ini).

```
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost/sun

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sun_db
DB_USERNAME=root
DB_PASSWORD=

# Optional logging
LOG_CHANNEL=stack
LOG_LEVEL=debug
```

Catatan: Periksa `system/database/Database.php` untuk memetakan bagaimana variabel env tersebut digunakan. Jika project tidak membaca file `.env`, sesuaikan konfigurasi di file konfigurasi DB yang digunakan.

## Informasi Proyek & Kepemilikan

- Repository GitHub: https://github.com/sunsolutionscorporate/SunVortex
- Perusahaan: PT. SUN Solutons Corporate
- Pemimpin / Pemilik proyek: Wahyu Widodo

Terima kasih telah menggunakan SunVortex. Jika Anda ingin, saya dapat menambahkan contoh route registration otomatis atau file `routes.php` contoh untuk mempermudah integrasi CRUD di atas.
