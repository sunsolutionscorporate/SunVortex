# PANDUAN INTEGRASI BASEMODEL DENGAN LOGGER FRAMEWORK

**File:** `system/Core/BaseModel.php`  
**Method:** `enableStandardLogging()`  
**Framework Logger:** `Logger::debug()`, `Logger::info()`, `Logger::warning()`  
**Bahasa:** Indonesia

---

## DAFTAR ISI

1. [Pengenalan](#pengenalan)
2. [Konsep Standar Logging](#konsep-standar-logging)
3. [Setup Logging pada Model](#setup-logging-pada-model)
4. [Event yang Dicatat](#event-yang-dicatat)
5. [Detail Logging untuk Setiap Event](#detail-logging-untuk-setiap-event)
6. [Contoh Implementasi](#contoh-implementasi)
7. [Log Output Example](#log-output-example)
8. [Best Practices](#best-practices)
9. [Troubleshooting](#troubleshooting)

---

## PENGENALAN

`enableStandardLogging()` adalah method di `BaseModel` yang secara otomatis mendaftarkan event listeners untuk mencatat semua perubahan data (INSERT, UPDATE, DELETE) ke sistem Logger framework.

**Manfaat:**

- ✅ Audit trail otomatis untuk setiap perubahan data
- ✅ Tracking siapa/kapan/apa yang diubah (via Logger context)
- ✅ Debugging mudah saat ada masalah data
- ✅ Compliance/keamanan: dokumentasi lengkap semua operasi database
- ✅ Performance monitoring: durasi setiap operasi tercatat

**Cara Kerja:**

- Mendaftarkan 8 event listeners pada model instance
- Capture setiap CREATE, UPDATE, DELETE (before & after)
- Log success, failure, dan changed fields
- Terintegrasi dengan framework Logger (DEBUG, INFO, WARNING levels)

---

## KONSEP STANDAR LOGGING

### Event Flow dalam BaseModel

```
CREATE (INSERT):
┌─ before:create ─┐
│  [DEBUG log]    │
│                 ├─→ insertNew()
└─────────────────┘
                   ├─ Success
                   │  └─ after:create [INFO log] ✓
                   │     └─ after:save
                   │
                   └─ Failure
                      └─ after:create:failed [WARNING log] ✗

UPDATE:
┌─ before:update ─┐
│  [DEBUG log]    │
│                 ├─→ update()
└─────────────────┘
                   ├─ Success (ada perubahan)
                   │  └─ after:update [INFO log] ✓
                   │     └─ after:save
                   │
                   └─ Failure atau No Change
                      └─ after:update:failed [WARNING log] ✗

DELETE:
┌─ before:delete ─┐
│  [DEBUG log]    │
│                 ├─→ delete()
└─────────────────┘
                   ├─ Success
                   │  └─ after:delete [WARNING log] ✓
                   │
                   └─ Failure
                      └─ after:delete:failed [WARNING log] ✗
```

### Logger Levels yang Digunakan

| Level     | Penggunaan                                  | Deskripsi                                          |
| --------- | ------------------------------------------- | -------------------------------------------------- |
| `DEBUG`   | before:create, before:update, before:delete | Info detail untuk development, bukan di production |
| `INFO`    | after:create, after:update                  | Operasi berhasil, informasi normal                 |
| `WARNING` | after:delete, after:\*:failed               | Perubahan penting atau error                       |

---

## SETUP LOGGING PADA MODEL

### Opsi 1: Enable di Constructor (Recommended)

Override constructor di model turunan dan panggil `enableStandardLogging()`:

```php
<?php

class Residents_model extends BaseModel
{
    protected $table = 'residents';
    protected $fillable = ['name', 'nik', 'placebirth', 'datebirth'];

    /**
     * Constructor dengan automatic logging
     */
    public function __construct($attributes = [], $db = null)
    {
        parent::__construct($attributes, $db);

        // Enable standar logging untuk model ini
        $this->enableStandardLogging();
    }
}
```

Sekarang setiap operasi CRUD akan otomatis tercatat ke Logger.

### Opsi 2: Enable Manual saat Diperlukan

Panggil `enableStandardLogging()` kapan saja:

```php
$m = new Residents_model();
$m->enableStandardLogging(); // enable logging

$m->fill(['name' => 'Budi', 'nik' => '123456']);
$m->save(); // akan log ke Logger
```

### Opsi 3: Enable untuk Operasi Spesifik Saja

Jika Anda hanya ingin log untuk operasi tertentu, daftarkan listener manual:

```php
$m = (new Residents_model())->find(123);

// Hanya log saat delete
$m->on('after:delete', function($model) {
    Logger::warning("DELETE: " . $model->name);
});

$m->delete();
```

---

## EVENT YANG DICATAT

`enableStandardLogging()` mendaftarkan 8 event listeners:

### CREATE (INSERT) Events

1. **before:create**

   - Level: DEBUG
   - Message: "Creating new {table} record"
   - Context: Kosong (hanya untuk tracking)

2. **after:create**

   - Level: INFO
   - Message: "INSERT berhasil: {table} (ID: {pk})"
   - Context: `{table, action, id, data}`

3. **after:create:failed**
   - Level: WARNING
   - Message: "INSERT gagal: {table}"
   - Context: `{table, action}`

### UPDATE Events

4. **before:update**

   - Level: DEBUG
   - Message: "Updating {table} record - Fields: {fields}"
   - Context: Kosong

5. **after:update**

   - Level: INFO
   - Message: "UPDATE berhasil: {table} (ID: {pk})"
   - Context: `{table, action, id, changed_fields}`

6. **after:update:failed**
   - Level: WARNING
   - Message: "UPDATE gagal: {table} (ID: {pk})"
   - Context: `{table, action, id}`

### DELETE Events

7. **before:delete**

   - Level: DEBUG
   - Message: "Deleting {table} record - ID: {pk}"
   - Context: Kosong

8. **after:delete**
   - Level: WARNING
   - Message: "DELETE berhasil: {table} (ID: {pk})"
   - Context: `{table, action, delete_type, id}`

---

## DETAIL LOGGING UNTUK SETIAP EVENT

### CREATE (INSERT)

**Scenario 1: Insert Sukses**

```php
$m = new Residents_model();
$m->fill(['name' => 'Budi', 'nik' => '1802266807918881']);
$id = $m->save();
```

**Log Output:**

```
[DEBUG]     --> [0.23ms] Creating new residents record

[INFO]      --> [1.45ms] INSERT berhasil: residents (ID: 4563)
            {
                "table": "residents",
                "action": "INSERT",
                "id": 4563,
                "data": {
                    "id": 4563,
                    "name": "Budi",
                    "nik": "1802266807918881",
                    "placebirth": null,
                    "datebirth": null,
                    "created_at": "2025-12-07 14:30:00",
                    "updated_at": "2025-12-07 14:30:00"
                }
            }
```

**Scenario 2: Insert Gagal**

```php
$m = new Residents_model();
$m->fill(['nik' => '1802266807918881']); // nama kosong
try {
    $m->save();
} catch (Exception $e) {
    // exception dari validation di before:create
}
```

**Log Output:**

```
[DEBUG]     --> [0.15ms] Creating new residents record

[WARNING]   --> [2.10ms] INSERT gagal: residents
            {
                "table": "residents",
                "action": "INSERT_FAILED"
            }
```

---

### UPDATE

**Scenario 1: Update Sukses dengan Perubahan**

```php
$m = (new Residents_model())->find(4563);
$m->name = 'BUDI UPDATED';
$m->placebirth = 'Jakarta';
$ok = $m->save();
```

**Log Output:**

```
[DEBUG]     --> [0.18ms] Updating residents record - Fields: name, placebirth

[INFO]      --> [1.23ms] UPDATE berhasil: residents (ID: 4563)
            {
                "table": "residents",
                "action": "UPDATE",
                "id": 4563,
                "changed_fields": {
                    "name": "BUDI UPDATED",
                    "placebirth": "Jakarta"
                }
            }
```

**Scenario 2: Update Tanpa Perubahan (No-op)**

```php
$m = (new Residents_model())->find(4563);
// Tidak ada perubahan
$ok = $m->save();
```

**Log Output:**

```
[DEBUG]     --> [0.12ms] Updating residents record - Fields: (kosong, tidak ada perubahan)
// after:update tidak dipanggil karena tidak ada perubahan
```

**Scenario 3: Update Gagal**

```php
$m = (new Residents_model())->find(4563);
$m->name = null; // validation gagal
try {
    $m->save();
} catch (Exception $e) {
}
```

**Log Output:**

```
[DEBUG]     --> [0.14ms] Updating residents record - Fields: name

[WARNING]   --> [1.50ms] UPDATE gagal: residents (ID: 4563)
            {
                "table": "residents",
                "action": "UPDATE_FAILED",
                "id": 4563
            }
```

---

### DELETE

**Scenario 1: Soft Delete Sukses**

```php
$m = (new Residents_model())->find(4563);
$ok = $m->delete();
```

**Log Output:**

```
[DEBUG]     --> [0.16ms] Deleting residents record - ID: 4563

[WARNING]   --> [1.08ms] DELETE berhasil: residents (ID: 4563)
            {
                "table": "residents",
                "action": "DELETE",
                "delete_type": "SOFT DELETE",
                "id": 4563
            }
```

**Scenario 2: Hard Delete Sukses**

```php
class User_model extends BaseModel {
    protected $softDelete = false;

    public function __construct($attrs = [], $db = null) {
        parent::__construct($attrs, $db);
        $this->enableStandardLogging();
    }
}

$m = (new User_model())->find(123);
$ok = $m->delete();
```

**Log Output:**

```
[DEBUG]     --> [0.19ms] Deleting user record - ID: 123

[WARNING]   --> [1.34ms] DELETE berhasil: user (ID: 123)
            {
                "table": "user",
                "action": "DELETE",
                "delete_type": "HARD DELETE",
                "id": 123
            }
```

**Scenario 3: Delete Gagal**

```php
$m = new Residents_model();
try {
    $m->delete(); // PK belum ter-set
} catch (Exception $e) {
}
```

**Log Output:**

```
[WARNING]   --> [0.45ms] DELETE gagal: residents
            {
                "table": "residents",
                "action": "DELETE_FAILED"
            }
```

---

## CONTOH IMPLEMENTASI

### Contoh 1: Residents Model dengan Logging

```php
<?php

class Residents_model extends BaseModel
{
    protected $table = 'residents';
    protected $primaryKey = 'id';
    protected $softDelete = true;
    protected $fillable = ['name', 'nik', 'placebirth', 'datebirth', 'id_job', 'id_marital'];
    protected $casts = [
        'datebirth' => 'date',
        'id_job' => 'int',
        'id_marital' => 'int'
    ];

    /**
     * Constructor dengan automatic logging
     */
    public function __construct($attributes = [], $db = null)
    {
        parent::__construct($attributes, $db);

        // Enable standar logging otomatis
        $this->enableStandardLogging();
    }

    /**
     * Custom validation sebelum save
     */
    public function setupEventListeners()
    {
        $this->on('before:save', function($model) {
            if (!$model->name || trim($model->name) === '') {
                throw new Exception('Nama tidak boleh kosong');
            }
        });
    }
}
```

### Contoh 2: Controller dengan Logging

```php
<?php

class Residents extends Controller
{
    public function store()
    {
        $input = Request::init()->input();

        $m = new Residents_model(); // logging otomatis enabled

        try {
            $m->fill($input);
            $id = $m->save(); // akan log: INSERT berhasil

            return Response::json(['id' => $id], 201);
        } catch (Exception $e) {
            // akan log: INSERT gagal
            return Response::json(['error' => $e->getMessage()], 400);
        }
    }

    public function update($id)
    {
        $input = Request::init()->input();

        $m = (new Residents_model())->find($id); // logging otomatis enabled
        if (!$m) {
            return Response::json(['error' => 'Tidak ditemukan'], 404);
        }

        try {
            $m->fill($input);
            $ok = $m->save(); // akan log: UPDATE berhasil

            return Response::json(['status' => 'ok']);
        } catch (Exception $e) {
            // akan log: UPDATE gagal
            return Response::json(['error' => $e->getMessage()], 400);
        }
    }

    public function delete($id)
    {
        $m = (new Residents_model())->find($id); // logging otomatis enabled
        if (!$m) {
            return Response::json(['error' => 'Tidak ditemukan'], 404);
        }

        try {
            $ok = $m->delete(); // akan log: DELETE berhasil atau DELETE gagal
            return Response::json(['status' => 'deleted']);
        } catch (Exception $e) {
            return Response::json(['error' => $e->getMessage()], 500);
        }
    }
}
```

---

## LOG OUTPUT EXAMPLE

File log (`/storage/logs/2025-12-07.log`):

```
== START [req-abc-123] TYPE=browser METHOD=POST PATH=/api/residents IP=192.168.1.100 UA=Windows/Chrome
[DEBUG]     --> [0.23ms] Creating new residents record
[INFO]      --> [1.45ms] INSERT berhasil: residents (ID: 4563)
                {
                    "table": "residents",
                    "action": "INSERT",
                    "id": 4563,
                    "data": {
                        "id": 4563,
                        "name": "Budi",
                        ...
                    }
                }
[DEBUG]     --> [0.15ms] Creating new residents record
[WARNING]   --> [2.10ms] INSERT gagal: residents
                {
                    "table": "residents",
                    "action": "INSERT_FAILED"
                }
[DEBUG]     --> [0.18ms] Updating residents record - Fields: name, placebirth
[INFO]      --> [1.23ms] UPDATE berhasil: residents (ID: 4563)
                {
                    "table": "residents",
                    "action": "UPDATE",
                    "id": 4563,
                    "changed_fields": {
                        "name": "BUDI UPDATED",
                        "placebirth": "Jakarta"
                    }
                }
[DEBUG]     --> [0.16ms] Deleting residents record - ID: 4563
[WARNING]   --> [1.08ms] DELETE berhasil: residents (ID: 4563)
                {
                    "table": "residents",
                    "action": "DELETE",
                    "delete_type": "SOFT DELETE",
                    "id": 4563
                }
== END [req-abc-123] DURATION=6.94ms MEMORY=2.00MB HTTP=200 CONTENT=application/json
```

---

## BEST PRACTICES

1. **Always Enable Logging di Constructor**

   ```php
   public function __construct($attributes = [], $db = null) {
       parent::__construct($attributes, $db);
       $this->enableStandardLogging(); // jangan lupa!
   }
   ```

2. **Combine dengan Custom Event Listeners**

   ```php
   $m = new Residents_model(); // sudah ada standar logging

   // Tambah custom listener jika diperlukan
   $m->on('after:create', function($model) {
       // Custom logic di sini
   });
   ```

3. **Check Log Files Regularly**

   - Log files ada di: `/storage/logs/YYYY-MM-DD.log`
   - Review daily untuk detect anomali atau errors

4. **Use Context untuk Additional Data**

   - Standar logging sudah include context otomatis
   - Jangan duplikasi info di multiple listeners

5. **Performance Note**

   - Standar logging menggunakan Logger framework (efficiency dihandle framework)
   - Minimal overhead untuk production
   - Logger hanya menulis jika `config('APP_LOG')` = true

6. **Security Consideration**
   - Sensitive data (password, token) akan ter-log jika ada di `$data` context
   - Gunakan `$guarded` untuk exclude sensitive fields
   - Review log file access permissions

---

## TROUBLESHOOTING

### Q1: Logging tidak jalan, padahal sudah call `enableStandardLogging()`

**A:** Pastikan `APP_LOG` config sudah `true`:

```php
// config/app.php atau sesuai lokasi config Anda
return [
    'APP_LOG' => true, // harus true
    ...
];
```

### Q2: Log context terlalu besar karena `$data`

**A:** Jika ingin exclude field tertentu, gunakan `$guarded`:

```php
class Residents_model extends BaseModel {
    protected $guarded = ['id', 'password', 'secret_token'];
    // field ini tidak akan ter-log
}
```

### Q3: Mau custom message untuk specific event?

**A:** Override dengan listener custom:

```php
$m = new Residents_model();
$m->enableStandardLogging(); // enable standar dulu

// Override dengan custom message
$m->off('after:create'); // hapus listener bawaan
$m->on('after:create', function($model) {
    Logger::info("Custom: Record baru dibuat dengan ID " . $model->id);
});
```

### Q4: Logging untuk bulk operations?

**A:** Standar logging bekerja per instance. Untuk bulk, coba:

```php
$ids = [1, 2, 3, 4, 5];

foreach ($ids as $id) {
    $m = (new Residents_model())->find($id); // setiap instance punya logging
    $m->delete(); // akan log per delete
}
```

### Q5: Mau cek apa fields yang berubah?

**A:** Gunakan `getChangedAttributes()` dalam listener:

```php
$m = (new Residents_model())->find(4563);
$m->on('after:update', function($model) {
    $changes = $model->getChangedAttributes(); // sudah ada di context
    var_dump($changes);
});
$m->name = 'Budi';
$m->save();
```

---

## RINGKASAN

| Aspek             | Detail                                           |
| ----------------- | ------------------------------------------------ |
| **Method**        | `enableStandardLogging()` di BaseModel           |
| **Setup**         | Call di constructor model turunan                |
| **Events**        | 8 listeners (create, update, delete, failed)     |
| **Logger Levels** | DEBUG, INFO, WARNING                             |
| **Context**       | Auto-include table, action, id, changed data     |
| **Performance**   | Minimal overhead, controlled by `APP_LOG` config |
| **Use Case**      | Audit trail, debugging, compliance               |

---

**END OF DOCUMENTATION**

Untuk pertanyaan atau masalah, review dokumentasi `BASEMODEL_USAGE.md` untuk detail method lain di BaseModel.
