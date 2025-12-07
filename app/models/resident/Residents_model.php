<?php

use LDAP\Result;

/**
 * Model Resident
 * 
 * Representasi data penduduk dari tabel 'residents'
 * Demonstrasi implementasi BaseModel dengan:
 * - Mass-assignment (fillable)
 * - Casting tipe data
 * - Mutator & Accessor
 * - Cache invalidation otomatis (getCacheKeys)
 * - Named scopes
 * - Event listeners
 * 
 * Struktur tabel:
 * - id (auto increment, primary key)
 * - name (varchar) - nama penduduk
 * - nik (varchar, unique) - nomor induk kependudukan
 * - placebirth (varchar) - tempat lahir
 * - datebirth (date) - tanggal lahir
 * - created_at (timestamp)
 * - updated_at (timestamp)
 */
class Residents_model extends BaseModel
{
   protected $table = "residents";
   protected $primaryKey = 'id';
   protected $softDelete = true;
   protected $fillable = [
      'id',
      'name',
      'nik',
      'placebirth',
      'datebirth',
      'id_job',
      'id_marital',
      // 'job',
      // 'marital',
   ];

   /**
    * Ambil satu resident berdasarkan id (dengan relasi job & marital)
    *
    * @param int $id
    * @return array ['data' => [...] atau null, 'pagination' => null]
    */
   public function find($id)
   {
      $id = (int)$id;
      if ($id <= 0) return $this->results([]);
      $model = parent::find($id);

      // Pastikan model relasi ter-registered
      model('resident/Resident_Job', 'job');
      model('resident/Resident_marital', 'marital');

      // Eager-load relasi (walau hanya 1 model, metode eagerLoad menerima array)
      $mapJob = BelongsTo::eagerLoad([$model], Resident_Job_Model::class, 'id_job', 'id');
      $mapMarital = BelongsTo::eagerLoad([$model], Resident_Marital_Model::class, 'id_marital', 'id');

      $fk_job = $model->getAttribute('id_job');
      $fk_marital = $model->getAttribute('id_marital');

      $model->setRelation('job', $mapJob[$fk_job] ?? null);
      $model->setRelation('marital', $mapMarital[$fk_marital] ?? null);

      $output = $model->toArray();
      $output['job'] = $output['job']['name'];
      $output['marital'] = $output['marital']['name'];

      return $this->results($output);
   }

   /**
    * Get paginated residents with eager-loaded relations (job, marital)
    *
    * @param int $page
    * @param int $limit
    * @param array $conditions
    * @return Results ['data' => [...], 'meta' => [...]]
    */
   public function paginate(int $page = 1, int $limit = 10, array $conditions = []): Results
   {
      $base = parent::paginate($page, $limit, $conditions);

      $residentModels = $base['data'];
      $total = $base['total'];
      $limit = $base['limit'];
      $page = $base['page'];

      // register related models
      model('resident/Resident_Job', 'job');
      model('resident/Resident_marital', 'marital');

      // eager load relations
      $jobsMap = BelongsTo::eagerLoad(
         $residentModels,
         Resident_Job_Model::class,
         'id_job',
         'id'
      );
      $maritalMap = BelongsTo::eagerLoad(
         $residentModels,
         Resident_Marital_Model::class,
         'id_marital',
         'id'
      );

      // set relations
      foreach ($residentModels as $model) {
         $fk_job = $model->getAttribute('id_job');
         $fk_marital = $model->getAttribute('id_marital');
         $model->setRelation('job', $jobsMap[$fk_job] ?? null);
         $model->setRelation('marital', $maritalMap[$fk_marital] ?? null);
      }

      // build output array using Model::toArray() which now serializes relations
      $output = [];
      foreach ($residentModels as $r) {
         $entry = $r->toArray();
         $entry['marital'] = $entry['marital']['name'];
         $entry['job'] = $entry['job']['name'];
         $output[] = $entry;
      }

      return $this->results($output)
         ->setLimit($limit)
         ->setPage($page)
         ->setTotal($total);
   }


   /**
    * Constructor dengan setup event listeners
    * 
    * @param array $attributes
    * @param Database $db
    */
   public function __construct($attributes = [], $db = null)
   {
      parent::__construct($attributes, $db);
      // $this->setupEventListeners();
   }

   /**
    * Setup event listeners untuk CRUD operations
    * 
    * Menangani:
    * - Validasi sebelum insert/update
    * - Logging setiap operasi
    * - Notifikasi setelah berhasil/gagal
    */
   protected function setupEventListeners()
   {
      // ===== BEFORE CREATE (INSERT) =====
      $this->on('before:create', function ($model) {
         // Validasi data sebelum insert
         if (!$model->name || trim($model->name) === '') {
            throw new Exception('Nama penduduk harus diisi');
         }
         if (!$model->nik || strlen($model->nik) < 10) {
            throw new Exception('NIK harus valid (minimal 10 digit)');
         }
         if ($model->datebirth && !$this->isValidDate($model->datebirth)) {
            throw new Exception('Format tanggal lahir tidak valid (gunakan Y-m-d)');
         }
         slog("[Residents] Validasi INSERT OK - NIK: {$model->nik}");
      });

      // ===== AFTER CREATE (INSERT SUCCESS) =====
      $this->on('after:create', function ($model) {
         $logData = "ID: {$model->id}, Nama: {$model->name}, NIK: {$model->nik}";
         slog("[Residents] INSERT BERHASIL - {$logData}");

         // Bisa trigger notifikasi, webhook, dll
         // sendNotification('resident.created', $model->toArray());
      });

      // ===== AFTER CREATE FAILED =====
      $this->on('after:create:failed', function ($model) {
         slog("[Residents] INSERT GAGAL - Nama: {$model->name}, NIK: {$model->nik}");
      });

      // ===== BEFORE UPDATE =====
      $this->on('before:update', function ($model) {
         // Validasi data sebelum update
         if (!$model->name || trim($model->name) === '') {
            throw new Exception('Nama penduduk harus diisi');
         }
         if ($model->datebirth && !$this->isValidDate($model->datebirth)) {
            throw new Exception('Format tanggal lahir tidak valid (gunakan Y-m-d)');
         }

         $changes = $model->getChangedAttributes();
         if (empty($changes)) {
            slog("[Residents] UPDATE SKIP - ID: {$model->id} (tidak ada perubahan)");
         } else {
            $changedFields = implode(', ', array_keys($changes));
            slog("[Residents] Validasi UPDATE OK - ID: {$model->id}, Fields: {$changedFields}");
         }
      });

      // ===== AFTER UPDATE =====
      $this->on('after:update', function ($model) {
         $changes = $model->getChangedAttributes();
         if (!empty($changes)) {
            $changedFields = implode(', ', array_keys($changes));
            $logData = "ID: {$model->id}, Nama: {$model->name}, Fields Updated: {$changedFields}";
            slog("[Residents] UPDATE BERHASIL - {$logData}");

            // Bisa trigger notifikasi
            // sendNotification('resident.updated', ['id' => $model->id, 'changes' => $changes]);
         }
      });

      // ===== AFTER UPDATE FAILED =====
      $this->on('after:update:failed', function ($model) {
         slog("[Residents] UPDATE GAGAL - ID: {$model->id}, Nama: {$model->name}");
      });

      // ===== BEFORE DELETE =====
      $this->on('before:delete', function ($model) {
         if (!$model->id) {
            throw new Exception('Tidak dapat menghapus: primary key tidak ter-set');
         }
         $logData = "ID: {$model->id}, Nama: {$model->name}, NIK: {$model->nik}";
         slog("[Residents] DELETE DIMULAI - {$logData}");

         // Bisa cek apakah ada relasi yang mencegah delete
         // if ($model->hasChildren('related_records')) {
         //     throw new Exception('Tidak dapat menghapus: masih ada data yang bergantung');
         // }
      });

      // ===== AFTER DELETE SUCCESS =====
      $this->on('after:delete', function ($model) {
         $deleteType = $model->softDelete ? 'SOFT DELETE' : 'HARD DELETE';
         $logData = "ID: {$model->id}, Nama: {$model->name}, Tipe: {$deleteType}";
         slog("[Residents] DELETE BERHASIL - {$logData}");

         // Bisa trigger notifikasi
         // sendNotification('resident.deleted', ['id' => $model->id, 'type' => $deleteType]);
      });

      // ===== AFTER DELETE FAILED =====
      $this->on('after:delete:failed', function ($model) {
         slog("[Residents] DELETE GAGAL - ID: {$model->id}");
      });

      // ===== BEFORE SAVE (INSERT OR UPDATE) =====
      $this->on('before:save', function ($model) {
         // Normalisasi data umum
         if ($model->name) {
            $model->name = trim($model->name);
         }
         if ($model->placebirth) {
            $model->placebirth = trim($model->placebirth);
         }
         // Bisa tambah normalisasi lain sesuai kebutuhan
      });

      // ===== AFTER SAVE (INSERT OR UPDATE SUCCESS) =====
      $this->on('after:save', function ($model) {
         // Cleanup atau post-processing setelah save berhasil
         // Bisa sync cache, update search index, dll
      });
   }

   /**
    * Validasi format tanggal
    * 
    * @param string $date
    * @return bool
    */
   protected function isValidDate($date)
   {
      if (!$date) return false;

      // Check format Y-m-d atau YYYY-MM-DD
      if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
         $d = \DateTime::createFromFormat('Y-m-d', $date);
         return $d && $d->format('Y-m-d') === $date;
      }

      // Jika date sudah DateTime object
      if ($date instanceof \DateTime) {
         return true;
      }

      return false;
   }

   public function entry()
   {
      // slog(Request::init()->input());
      $this->create(Request::init()->input());
   }

   public function delete($id = null)
   {
      // Jika dipanggil tanpa id, gunakan behaviour instance (hapus current instance)
      if (is_null($id)) {
         // Pastikan model ini merepresentasikan record yang ada
         return parent::delete();
      }

      // Jika id diberikan, cari dulu record tersebut via BaseModel::find
      $base = parent::find($id);
      if (!$base) {
         // tidak ditemukan -> kembalikan false agar caller bisa menangani
         return false;
      }

      return $base->delete();
   }
};
