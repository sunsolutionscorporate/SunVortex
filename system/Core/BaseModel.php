<?php

use LDAP\Result;

/**
 * BaseModel - Class Induk untuk semua Model
 * 
 * @author SunVortex Framework
 * @version 1.0
 */
abstract class BaseModel
{
   public $profiler;
   private $db;
   protected $table = '';
   protected $primaryKey = 'id';
   protected $fillable = [];
   protected $guarded = ['id'];
   protected $casts = [];
   protected $timestamps = true;
   protected $createdAtColumn = 'created_at';
   protected $updatedAtColumn = 'updated_at';
   protected $deletedAtColumn = 'deleted_at';
   protected $softDelete = false;
   protected $attributes = [];
   protected $original = [];
   protected $relations = [];
   protected $mutators = [];
   protected $accessors = [];
   protected $events = [];
   protected $scopes = [];
   protected $appends = [];
   // Instance event listeners
   protected $globalEvents = [];

   public function __construct($attributes = [], $db = null)
   {
      // Allow injecting a Database instance for testability; fallback to singleton
      $this->db = $db ?? Database::init();
      // Determine table name automatically if not provided
      if (empty($this->table)) {
         $this->determineTableName();
      }
      if (!empty($attributes)) {
         $this->fill($attributes);
      };

      // Attempt to obtain profiler if available (some DB implementations may not provide it)
      try {
         if (method_exists($this->db, 'getProfiler')) {
            $this->profiler = $this->db->getProfiler();
         }
      } catch (Exception $e) {
         // ignore profiler retrieval errors
      }

      $this->enableStandardLogging();
   }

   /**
    * setDatabase($db)
    * Allow swapping Database instance after model creation (useful for tests)
    */
   public function setDatabase($db)
   {
      $this->db = $db;
      return $this;
   }

   /**
    * enableStandardLogging()
    * Setup standar event listeners untuk mencatat perubahan data ke Logger framework.
    * Mencatat: INSERT, UPDATE, DELETE dengan detail atribut yang berubah.
    * 
    * Gunakan di constructor model turunan untuk auto-enable logging:
    *   public function __construct($attrs = [], $db = null) {
    *       parent::__construct($attrs, $db);
    *       $this->enableStandardLogging();
    *   }
    * 
    * @return $this (untuk chaining)
    */
   public function enableStandardLogging()
   {
      $modelClass = get_class($this);
      $tableName = $this->table;

      // ===== BEFORE CREATE =====
      $this->on('before:create', function ($model) use ($modelClass, $tableName) {
         Logger::debug("Creating new {$tableName} record");
      });

      // ===== AFTER CREATE SUCCESS =====
      $this->on('after:create', function ($model) use ($modelClass, $tableName) {
         $pk = $model->getAttribute($model->primaryKey);
         $attrs = $model->toArray();
         Logger::info(
            "INSERT berhasil: {$tableName} (ID: {$pk})",
            "Tabel: {$tableName}, Record baru dibuat dengan ID {$pk}"
         )->addContext(['table' => $tableName, 'action' => 'INSERT', 'id' => $pk, 'data' => $attrs]);
      });

      // ===== AFTER CREATE FAILED =====
      $this->on('after:create:failed', function ($model) use ($modelClass, $tableName) {
         Logger::warning(
            "INSERT gagal: {$tableName}",
            "Gagal membuat record baru di tabel {$tableName}"
         )->addContext(['table' => $tableName, 'action' => 'INSERT_FAILED']);
      });

      // ===== BEFORE UPDATE =====
      $this->on('before:update', function ($model) use ($modelClass, $tableName) {
         $changes = $model->getChangedAttributes();
         if (!empty($changes)) {
            $changedFields = implode(', ', array_keys($changes));
            Logger::debug("Updating {$tableName} record - Fields: {$changedFields}");
         }
      });

      // ===== AFTER UPDATE SUCCESS =====
      $this->on('after:update', function ($model) use ($modelClass, $tableName) {
         $pk = $model->getAttribute($model->primaryKey);
         $changes = $model->getChangedAttributes();

         if (!empty($changes)) {
            $changedFields = implode(', ', array_keys($changes));
            Logger::info(
               "UPDATE berhasil: {$tableName} (ID: {$pk})",
               "Tabel: {$tableName}, Record ID {$pk} diupdate. Fields berubah: {$changedFields}"
            )->addContext([
               'table' => $tableName,
               'action' => 'UPDATE',
               'id' => $pk,
               'changed_fields' => $changes
            ]);
         }
      });

      // ===== AFTER UPDATE FAILED =====
      $this->on('after:update:failed', function ($model) use ($modelClass, $tableName) {
         $pk = $model->getAttribute($model->primaryKey);
         Logger::warning(
            "UPDATE gagal: {$tableName} (ID: {$pk})",
            "Gagal mengupdate record ID {$pk} di tabel {$tableName}"
         )->addContext(['table' => $tableName, 'action' => 'UPDATE_FAILED', 'id' => $pk]);
      });

      // ===== BEFORE DELETE =====
      $this->on('before:delete', function ($model) use ($modelClass, $tableName) {
         $pk = $model->getAttribute($model->primaryKey);
         Logger::debug("Deleting {$tableName} record - ID: {$pk}");
      });

      // ===== AFTER DELETE SUCCESS =====
      $this->on('after:delete', function ($model) use ($modelClass, $tableName) {
         $pk = $model->getAttribute($model->primaryKey);
         $deleteType = $model->softDelete ? 'SOFT DELETE' : 'HARD DELETE';
         Logger::warning(
            "DELETE berhasil: {$tableName} (ID: {$pk})",
            "Tabel: {$tableName}, Record ID {$pk} dihapus ({$deleteType})"
         )->addContext([
            'table' => $tableName,
            'action' => 'DELETE',
            'delete_type' => $deleteType,
            'id' => $pk
         ]);
      });

      // ===== AFTER DELETE FAILED =====
      $this->on('after:delete:failed', function ($model) use ($modelClass, $tableName) {
         $pk = $model->getAttribute($model->primaryKey);
         Logger::warning(
            "DELETE gagal: {$tableName} (ID: {$pk})",
            "Gagal menghapus record ID {$pk} di tabel {$tableName}"
         )->addContext(['table' => $tableName, 'action' => 'DELETE_FAILED', 'id' => $pk]);
      });

      return $this;
   }

   /**
    * determineTableName()
    * Simple convention-based table name inference:
    * - Removes suffix `_model` or `Model` from class name
    * - Lowercases and appends `s` (very simple pluralization)
    */
   private function determineTableName()
   {
      try {
         $ref = new ReflectionClass($this);
         $name = $ref->getShortName();
      } catch (ReflectionException $e) {
         $name = get_class($this);
      }
      $name = preg_replace('/(_?model|Model)$/i', '', $name);
      $name = strtolower($name);
      // very naive pluralize
      if (substr($name, -1) !== 's') $name;
      $this->table = $name;
   }

   /**
    * __get(string $name)
    * Magic getter: mem-forward ke getAttribute bila attribute ada.
    * Return: nilai attribute atau null.
    */
   public function __get($name)
   {
      if (array_key_exists($name, $this->attributes)) {
         return $this->getAttribute($name);
      }

      // Fallback: jika relasi sudah di-set di $this->relations, kembalikan relasi
      if (array_key_exists($name, $this->relations)) {
         return $this->relations[$name];
      }

      // Lazy-load relation jika ada method relation{Name}
      $method = 'relation' . ucfirst($name);
      if (method_exists($this, $method)) {
         $rel = $this->$method();
         $this->relations[$name] = $rel;
         return $rel;
      }

      return null;
   }

   /**
    * __set(string $name, $value)
    * Magic setter: mem-forward ke setAttribute sehingga mutator (jika ada) dijalankan.
    */
   public function __set($name, $value)
   {
      $this->setAttribute($name, $value);
   }

   /**
    * __call(string $name, array $arguments)
    * Fungsi: Memungkinkan pemanggilan dynamic scope seperti $model->active() yang akan memanggil scopeActive().
    * Jika method scope tidak ditemukan, lempar Exception.
    */
   public function __call($name, $arguments)
   {
      $method = 'scope' . ucfirst($name);
      if (method_exists($this, $method)) {
         $qb = $this->db->table($this->table);
         if ($this->softDelete) {
            $qb->whereNull($this->deletedAtColumn);
         }
         return $this->$method($qb, ...$arguments);
      }
      throw new Exception("Method {$name} not found on model " . static::class);
   }

   /**
    * setAttribute(string $key, $value)
    * Fungsi: Men-set sebuah atribut pada model; mendukung mutator otomatis.
    * Perilaku: Jika ada method mutator set{Field}Attribute, nilai akan diproses oleh mutator.
    * Return: $this
    */
   public function setAttribute($key, $value)
   {
      $mutatorMethod = 'set' . ucfirst(str_replace('_', '', $key)) . 'Attribute';
      if (method_exists($this, $mutatorMethod)) {
         $value = $this->$mutatorMethod($value);
      }
      $this->attributes[$key] = $value;
      return $this;
   }

   /**
    * getAttribute(string $key)
    * Fungsi: Mengambil nilai atribut; mendukung accessor dan casting.
    * Return: nilai attribute yang sudah diproses atau null jika tidak ada.
    * Perilaku: Jika accessor get{Field}Attribute ada, panggil accessor; kemudian lakukan casting sesuai $casts.
    */
   public function getAttribute($key)
   {
      if (!array_key_exists($key, $this->attributes)) {
         return null;
      }
      $value = $this->attributes[$key];

      // Check untuk accessor method
      $accessorMethod = 'get' . ucfirst(str_replace('_', '', $key)) . 'Attribute';
      if (method_exists($this, $accessorMethod)) {
         return $this->$accessorMethod($value);
      }

      // Apply casting jika ada
      if (isset($this->casts[$key])) {
         return $this->castAttribute($key, $value);
      }

      return $value;
   }

   /**
    * castAttribute(string $key, $value)
    * Fungsi: Mengonversi nilai attribute ke tipe yang didefinisikan di $casts.
    * Supported: int, float, bool, array, object, json, date/datetime.
    * Catatan: Untuk date/datetime, mengembalikan objek DateTime; input harus format yang valid.
    */
   protected function castAttribute($key, $value)
   {
      $type = $this->casts[$key];
      switch ($type) {
         case 'int':
         case 'integer':
            return (int)$value;
         case 'float':
            return (float)$value;
         case 'bool':
         case 'boolean':
            return (bool)$value;
         case 'array':
            return is_array($value) ? $value : json_decode($value, true);
         case 'object':
            return is_object($value) ? $value : json_decode($value);
         case 'json':
            return is_string($value) ? json_decode($value, true) : $value;
         case 'date':
         case 'datetime':
            if ($value === null || $value === '') return null;
            try {
               return new DateTime($value);
            } catch (Exception $e) {
               return null;
            }
         default:
            return $value;
      }
   }

   /**
    * fill(array $data)
    * Fungsi: Mass-assignment - mengisi atribut model dari array input.
    * Parameter: array $data - pasangan key=>value untuk atribut model.
    * Return: $this (instance) untuk chaining.
    * Perilaku: Mematuhi $fillable dan $guarded; hanya atribut yang diizinkan yang diset.
    * Contoh: $user->fill($_POST)->save();
    */
   public function fill($data)
   {
      foreach ($data as $key => $value) {
         if ($this->isFillable($key)) {
            $this->setAttribute($key, $value);
         }
      }
      return $this;
   }

   /**
    * isFillable(string $key)
    * Fungsi: Mengecek apakah sebuah atribut boleh diisi melalui mass-assignment.
    * Return: bool
    * Logika: Jika $fillable di-set maka hanya key di $fillable yang diizinkan. Jika tidak, gunakan $guarded.
    */
   protected function isFillable($key)
   {
      if (!empty($this->fillable)) {
         return in_array($key, $this->fillable);
      }
      if (!empty($this->guarded)) {
         return !in_array($key, $this->guarded);
      }
      return true;
   }

   /**
    * syncOriginal()
    * Fungsi: Menyimpan snapshot atribut saat ini ke `$original`.
    * Catatan: Dipanggil setelah save() agar perubahan berikutnya bisa dideteksi.
    */
   protected function syncOriginal()
   {
      $this->original = $this->attributes;
   }

   /**
    * setRelation(string $name, $value)
    * Fungsi: Menetapkan relasi secara manual tanpa perlu Reflection.
    * Return: $this
    */
   public function setRelation($name, $value)
   {
      $this->relations[$name] = $value;
      return $this;
   }

   /**
    * query()
    * Fungsi: Mengembalikan instance QueryBuilder untuk tabel model ini.
    * Perilaku: Jika softDelete aktif, builder akan menambahkan whereNull pada kolom deleted_at.
    * Return: QueryBuilder
    */
   public function query()
   {
      $qb = $this->db->table($this->table);
      if ($this->softDelete) {
         $qb->whereNull($this->deletedAtColumn);
      }
      return $qb;
   }

   /**
    * toArray()
    * Fungsi: Mengonversi model (beserta relasi yang telah di-load) menjadi array bersarang.
    * Return: array
    */
   public function toArray()
   {
      $array = $this->attributes;

      foreach ($this->relations as $key => $value) {
         // Collection: rely on Collection->toArray() which will convert contained models
         if ($value instanceof Collection) {
            $array[$key] = $value->toArray();
            continue;
         }

         // Single Model instance
         if ($value instanceof BaseModel) {
            $array[$key] = $value->toArray();
            continue;
         }

         // Plain array (possibly array of models)
         if (is_array($value)) {
            $arr = [];
            foreach ($value as $item) {
               if ($item instanceof BaseModel) {
                  $arr[] = $item->toArray();
               } elseif (is_object($item) && method_exists($item, 'toArray')) {
                  $arr[] = $item->toArray();
               } else {
                  $arr[] = $item;
               }
            }
            $array[$key] = $arr;
            continue;
         }

         // Any object with toArray()
         if (is_object($value) && method_exists($value, 'toArray')) {
            $array[$key] = $value->toArray();
            continue;
         }

         // Fallback: scalar or mixed
         $array[$key] = $value;
      }

      // Normalize DateTime in attributes to string to avoid JSON issues
      foreach ($array as $k => $v) {
         if ($v instanceof DateTime) {
            $array[$k] = $v->format('Y-m-d H:i:s');
         }
      }

      return $array;
   }

   /**
    * fireEvent(string $event)
    * Fungsi: Menjalankan semua listener yang terdaftar untuk event tertentu, baik instance maupun global.
    */
   /**
    * fireEvent
    * Protected so child models can override behavior or intercept events.
    */
   protected function fireEvent($event)
   {
      if (isset($this->events[$event])) {
         foreach ($this->events[$event] as $callback) {
            call_user_func($callback, $this);
         }
      }

      if (isset($this->globalEvents[$event])) {
         foreach ($this->globalEvents[$event] as $callback) {
            call_user_func($callback, $this);
         }
      }
   }

   /**
    * on(string $event, callable $callback)
    * Register an instance event listener
    */
   public function on($event, callable $callback)
   {
      $this->events[$event][] = $callback;
      return $this;
   }

   /**
    * off(string $event, callable|null $callback = null)
    * Remove event listeners; if callback null remove all for event.
    */
   public function off($event, $callback = null)
   {
      if (!isset($this->events[$event])) return $this;
      if ($callback === null) {
         unset($this->events[$event]);
         return $this;
      }
      foreach ($this->events[$event] as $i => $cb) {
         if ($cb === $callback) {
            unset($this->events[$event][$i]);
         }
      }
      // Reindex
      $this->events[$event] = array_values($this->events[$event]);
      return $this;
   }

   /**
    * Convenience: toJson
    */
   public function toJson($options = 0)
   {
      return json_encode($this->toArray(), $options);
   }

   /**
    * findBy - cari satu record berdasarkan kolom tertentu
    * @return BaseModel|null
    */
   public function findBy($field, $value)
   {
      $row = $this->db->table($this->table)->where($field, $value)->first();
      if ($row) {
         $m = new static($row);
         $m->syncOriginal();
         return $m;
      }
      return null;
   }

   /**
    * deleteById - helper untuk menghapus berdasarkan id tanpa memuat controller
    */
   public function deleteById($id)
   {
      $base = $this->find($id);
      if (!$base) return false;
      return $base->delete();
   }

   /**
    * refresh - reload attributes from DB (useful after external changes)
    */
   public function refresh()
   {
      $pk = $this->attributes[$this->primaryKey] ?? null;
      if ($pk === null) return $this;
      $fresh = $this->find($pk);
      if ($fresh) {
         $this->attributes = $fresh->attributes;
         $this->syncOriginal();
      }
      return $this;
   }

   /**
    * updateOrCreate - cari record berdasarkan $search, jika ada update, jika tidak create baru
    */
   public function updateOrCreate(array $search, array $data)
   {
      $qb = $this->db->table($this->table);
      foreach ($search as $k => $v) $qb->where($k, $v);
      $row = $qb->first();
      if ($row) {
         $model = new static($row);
         $model->fill(array_merge($row, $data));
         $model->save();
         return $model;
      }
      $model = new static(array_merge($search, $data));
      $model->save();
      return $model;
   }

   /**
    * firstOrCreate - cari record matching attrs, atau buat baru dengan attrs+values
    */
   public function firstOrCreate(array $attrs, array $values = [])
   {
      $qb = $this->db->table($this->table);
      foreach ($attrs as $k => $v) $qb->where($k, $v);
      $row = $qb->first();
      if ($row) {
         $m = new static($row);
         $m->syncOriginal();
         return $m;
      }
      $model = new static(array_merge($attrs, $values));
      $model->save();
      return $model;
   }

   /**
    * Transaction helpers (proxy to Database if available)
    */
   public function beginTransaction()
   {
      if (method_exists($this->db, 'beginTransaction')) return $this->db->beginTransaction();
      return false;
   }

   public function commit()
   {
      if (method_exists($this->db, 'commit')) return $this->db->commit();
      return false;
   }

   public function rollBack()
   {
      if (method_exists($this->db, 'rollBack')) return $this->db->rollBack();
      return false;
   }

   /**
    * exists()
    * Fungsi: Mengecek apakah instance model merepresentasikan record yang sudah tersimpan.
    * Catatan: Ini pengecekan in-memory (cek ada primary key di attributes), bukan cek ke DB.
    * Return: bool
    */
   protected function exists()
   {
      return isset($this->attributes[$this->primaryKey]) && $this->attributes[$this->primaryKey] !== null;
   }

   /**
    * save()
    * Fungsi: Menyimpan model. Jika primary key ada -> update(), jika tidak -> insertNew().
    * Behavior: Memicu event sebelum/sesudah, lalu mensinkronisasi original attributes.
    * Return: hasil operasi (insert id atau boolean untuk update)
    */
   public function save()
   {
      $this->fireEvent('before:save');

      if ($this->exists()) {
         // Updating existing record
         $this->fireEvent('before:update');
         try {
            $result = $this->update();
         } catch (Exception $e) {
            $result = false;
         }

         if ($result) {
            $this->fireEvent('after:update');
            $this->fireEvent('after:save');
            $this->syncOriginal();
            return $result;
         }

         // failed update
         $this->fireEvent('after:update:failed');
         return false;
      } else {
         // Inserting new record
         $this->fireEvent('before:create');
         try {
            $result = $this->insertNew();
         } catch (Exception $e) {
            $result = false;
         }

         if ($result !== false && $result !== null) {
            $this->fireEvent('after:create');
            $this->fireEvent('after:save');
            $this->syncOriginal();
            return $result;
         }

         $this->fireEvent('after:create:failed');
         return false;
      }
   }

   /**
    * getChangedAttributes()
    * Fungsi: Mengembalikan array atribut yang berubah dibandingkan `$original`.
    * Return: array key=>value untuk atribut yang berubah (digunakan untuk update minimal).
    */
   protected function getChangedAttributes()
   {
      $changed = [];
      foreach ($this->attributes as $key => $value) {
         if (!isset($this->original[$key]) || $this->original[$key] !== $value) {
            $changed[$key] = $value;
         }
      }
      return $changed;
   }

   /**
    * insertNew()
    * Fungsi internal: membuat record baru di database.
    * Behavior: menambahkan timestamps bila diaktifkan, menyimpan primary key hasil insert ke attributes.
    * Return: insert id
    */
   private function insertNew()
   {
      $data = $this->attributes;
      if ($this->timestamps) {
         $data[$this->createdAtColumn] = date('Y-m-d H:i:s');
         $data[$this->updatedAtColumn] = date('Y-m-d H:i:s');
      }

      try {
         $id = $this->db->table($this->table)->insert($data);
      } catch (Exception $e) {
         return false;
      }

      if ($id === false || $id === null) return false;

      $this->attributes[$this->primaryKey] = $id;
      return $id;
   }

   /**
    * update()
    * Fungsi internal: meng-update record yang sudah ada. Hanya mengirim atribut yang berubah.
    * Return: boolean (apakah ada row yang diubah)
    */
   protected function update()
   {
      $data = $this->getChangedAttributes();
      if (empty($data)) return true;

      if ($this->timestamps) {
         $data[$this->updatedAtColumn] = date('Y-m-d H:i:s');
      }

      try {
         $rc = $this->db->table($this->table)
            ->where($this->primaryKey, $this->attributes[$this->primaryKey])
            ->update($data);
      } catch (Exception $e) {
         return false;
      }

      // If update executed, treat as success (even if 0 rows changed)
      return $rc !== false;
   }

   /**
    * Create baru record dari static method
    */
   /**
    * Backward-compatible instance create (tetap ada untuk kompatibilitas)
    */
   public function create($data)
   {
      $model = new static($data);
      $model->save();
      return $model;
   }

   /**
    * existsInDb()
    * Public helper untuk memeriksa apakah model merepresentasikan record yang tersimpan.
    */
   public function existsInDb()
   {
      return $this->exists();
   }

   /**
    * delete()
    * Fungsi: Menghapus record. Jika softDelete=true maka hanya men-set kolom deleted_at.
    * Return: hasil operasi DB (boolean/int)
    */
   public function delete()
   {
      $this->fireEvent('before:delete');

      $pk = $this->attributes[$this->primaryKey] ?? null;
      if ($pk === null) {
         throw new Exception('Cannot delete: primary key not set on model');
      }

      try {
         if ($this->softDelete) {
            $result = $this->db->table($this->table)
               ->where($this->primaryKey, $pk)
               ->update([$this->deletedAtColumn => date('Y-m-d H:i:s')]);
         } else {
            $result = $this->db->table($this->table)
               ->where($this->primaryKey, $pk)
               ->delete();
         }
      } catch (Exception $e) {
         $this->fireEvent('after:delete:failed');
         return false;
      }

      $this->fireEvent('after:delete');
      return $result;
   }

   /**
    * Find by primary key
    */
   public function find($id)
   {
      $row = $this->db->table($this->table)
         ->where($this->primaryKey, $id);
      if ($this->softDelete) {
         $row->whereNull($this->deletedAtColumn);
      }
      $data = $row->first();

      if ($data) {
         $model = new static($data);
         $model->syncOriginal();
         return $model;
      };



      return null;
   }

   /**
    * Paginate records
    */
   public function paginate(int $page = 1, int $limit = 10, array $conditions = [])
   {
      $page = max(1, (int)$page);
      $limit = max(1, (int)$limit);
      $offset = ($page - 1) * $limit;

      $qb = $this->db->table($this->table);
      if ($this->softDelete) {
         $qb->whereNull($this->deletedAtColumn);
      };
      foreach ($conditions as $k => $v) {
         $qb->where($k, $v);
      };
      // hitung total (tidak mereset builder karena kita akan pakai limit/offset setelah)
      $total = $qb->countAllResults(false);

      // apply limit/offset dan ambil rows
      $qb->limit($limit)->offset($offset);
      $rows = $qb->getResultArray();

      $collection = [];
      foreach ($rows as $row) {
         $model = new static($row);
         $model->syncOriginal();
         $collection[] = $model;
      }

      return [
         'data' => $collection,
         'total' => $total,
         'limit' => $limit,
         'page' => $page,
      ];
   }

   /**
    * Helper: buat response struktur standar untuk data list (dengan pagination)
    * @param array $data Array item hasil query
    * @param int|null $total Total records (null jika tidak ada pagination)
    * @param int|null $limit Limit per halaman
    * @param int|null $currentPage Halaman saat ini
    * @return Results ['data' => [...], 'pagination' => [...] atau null]
    */
   public function results(array $data): Results
   {
      return new Results($data);
   }
}
