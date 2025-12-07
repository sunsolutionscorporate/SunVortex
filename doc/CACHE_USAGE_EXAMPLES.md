# IMPLEMENTASI CACHE INVALIDATION - CONTOH PENGGUNAAN

## Tanpa perlu mengubah kode aplikasi Anda!

Sistem cache invalidation otomatis sudah terintegrasi di QueryBuilder.
Setiap kali Anda menggunakan QueryBuilder, cache otomatis di-handle.

---

### CONTOH 1: Model dengan Query Caching Otomatis

```php
<?php
// app/models/Residents_model.php

class Residents_model extends BaseModel
{
    public function __construct()
    {
        parent::__construct();
    }

    // Method ini AKAN MENGGUNAKAN CACHE OTOMATIS
    public function get_all()
    {
        return $this->db->table('residents')->getResultArray();
    }

    // Pencarian - CACHE HIT jika query sama
    public function find_by_status($status)
    {
        return $this->db->table('residents')
            ->where('status', $status)
            ->getResultArray();
    }

    // INSERT - Otomatis INVALIDATE cache residents
    public function add_resident($data)
    {
        return $this->db->table('residents')->insert($data);
    }

    // UPDATE - Otomatis INVALIDATE cache residents
    public function update_resident($id, $data)
    {
        return $this->db->table('residents')
            ->where('id', $id)
            ->update($data);
    }

    // DELETE - Otomatis INVALIDATE cache residents
    public function delete_resident($id)
    {
        return $this->db->table('residents')
            ->where('id', $id)
            ->delete();
    }
}
```

---

### CONTOH 2: Controller - Tidak perlu perubahan

```php
<?php
// app/controllers/Residents.php

class Residents extends Controller
{
    protected $model;

    public function __construct()
    {
        parent::__construct();
        $this->model = new Residents_model();
    }

    // GET LIST - Otomatis CACHE jika query sama
    public function get_all()
    {
        $residents = $this->model->get_all();
        return $this->response->json($residents);
    }

    // GET BY ID - Otomatis CACHE
    public function get_by_status($status)
    {
        $residents = $this->model->find_by_status($status);
        return $this->response->json($residents);
    }

    // CREATE - Otomatis INVALIDATE cache
    public function create()
    {
        $data = $this->request->getJSON();
        $id = $this->model->add_resident($data);

        // Cache otomatis dihapus, query berikutnya ambil data terbaru
        return $this->response->json(['id' => $id]);
    }

    // UPDATE - Otomatis INVALIDATE cache
    public function update($id)
    {
        $data = $this->request->getJSON();
        $this->model->update_resident($id, $data);

        // Cache otomatis dihapus, query berikutnya ambil data terbaru
        return $this->response->json(['success' => true]);
    }

    // DELETE - Otomatis INVALIDATE cache
    public function delete($id)
    {
        $this->model->delete_resident($id);

        // Cache otomatis dihapus, query berikutnya ambil data terbaru
        return $this->response->json(['success' => true]);
    }
}
```

---

### CONTOH 3: Alur Penggunaan

```
Scenario: User membuka halaman Residents

1. REQUEST GET /residents
   ↓
2. Controller memanggil: $this->model->get_all()
   ↓
3. Model memanggil: $this->db->table('residents')->getResultArray()
   ↓
4. QueryBuilder CHECK CACHE dengan key 'qb:md5(...)'
   → CACHE MISS (first request)
   ↓
5. QueryBuilder EXECUTE query ke database
   ↓
6. QueryBuilder EXTRACT table: 'residents'
   ↓
7. QueryBuilder SET TAG: 'table:residents'
   ↓
8. QueryBuilder SAVE hasil ke cache dengan tag
   ↓
9. RESPONSE ke user dengan data ✓

---

Request kedua sama persis (dalam TTL cache):

1. REQUEST GET /residents
   ↓
2. QueryBuilder CHECK CACHE
   → CACHE HIT! ✓
   ↓
3. RESPONSE ke user lebih CEPAT dari sebelumnya ✓

---

User melakukan UPDATE:

1. REQUEST POST /residents/1 (UPDATE)
   ↓
2. Model memanggil: $this->db->table('residents')
                        ->where('id', 1)
                        ->update($data)
   ↓
3. QueryBuilder EXECUTE UPDATE ke database
   ↓
4. UPDATE SUKSES
   ↓
5. QueryBuilder EXTRACT table: 'residents'
   ↓
6. QueryBuilder PANGGIL: $cache->flushTable('residents')
   ↓
7. SEMUA cache dengan tag 'table:residents' DIHAPUS ✓

---

Request GET setelah UPDATE:

1. REQUEST GET /residents
   ↓
2. QueryBuilder CHECK CACHE
   → CACHE MISS (baru dihapus)
   ↓
3. QueryBuilder EXECUTE query ke database
   ↓
4. RESPONSE ke user dengan DATA TERBARU ✓
```

---

### IMPLEMENTASI BENEFIT

✅ **TRANSPARENT** - Tidak perlu ubah kode aplikasi
✅ **AUTOMATIC** - Sistem handle sendiri
✅ **EFFICIENT** - Cache tetap berjalan optimal
✅ **ACCURATE** - Data selalu up-to-date
✅ **SAFE** - Tidak ada stale data issue

---

### TESTING

Jalankan test untuk verifikasi:

```bash
php public/test_final.php
```

Output akan menunjukkan:

- ✓ Cache tagging works
- ✓ Automatic invalidation works
- ✓ Multiple tables support
- ✓ JOIN queries support

Semua test PASSED ✓✓✓
