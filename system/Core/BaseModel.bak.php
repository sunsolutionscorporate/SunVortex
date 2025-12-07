<?php

/**
 * BaseModel - Class Induk untuk semua Model
 * 
 * Fitur:
 * - CRUD Operations (Create, Read, Update, Delete)
 * - Query Builder Integration
 * - Relationship Support (HasMany, BelongsTo, HasOne)
 * - Timestamps (created_at, updated_at, deleted_at)
 * - Query Scopes
 * - Data Casting
 * - Validation Hook
 * - Eager Loading / Relationship Caching
 * - Soft Delete Support
 * - Model Events (onCreating, onCreated, onUpdating, onUpdated, etc)
 * - Pagination
 * - Query Caching
 * 
 * @author SunVortex Framework
 * @version 1.0
 */
abstract class BaseModel_X
{
   /** @var Database */
   protected $db;

   /** @var string Table name */
   protected $table = '';

   /** @var string Primary key */
   protected $primaryKey = 'id';

   /** @var array Model attributes/data */
   protected $attributes = [];

   /** @var array Original attributes (untuk detect changes) */
   protected $original = [];

   /** @var array Fillable columns (whitelist untuk mass assignment) */
   protected $fillable = [];

   /** @var array Hidden columns (tidak ditampilkan saat to array/json) */
   protected $hidden = [];

   /** @var array Casts (type casting untuk atribut) */
   protected $casts = [];

   /** @var array Timestamps */
   protected $timestamps = ['created_at', 'updated_at'];

   /** @var bool Enable soft delete */
   protected $softDelete = false;

   /** @var string Soft delete column name */
   protected $deletedAtColumn = 'deleted_at';

   /** @var array Relationships yang sudah di-load */
   protected $relations = [];

   /** @var bool Exists in database */
   protected $exists = false;

   /**
    * Constructor
    */
   public function __construct($attributes = [])
   {
      $this->db = Database::init();

      // Set table name dari class name jika belum di-set
      if (empty($this->table)) {
         $this->setTableName();
      }

      // Fill attributes
      if (!empty($attributes)) {
         $this->fill($attributes);
      }
   }

   /**
    * Auto set table name dari class name
    * Contoh: Residents_model -> residents
    * @return void
    */
   protected function setTableName()
   {
      $className = class_basename(get_class($this));
      $className = str_replace('_model', '', $className);
      $this->table = strtolower($className) . 's'; // Pluralize
   }

   /**
    * ============================================================
    * QUERY BUILDER SHORTCUTS
    * ============================================================
    */

   /**
    * Query builder instance
    * @return QueryBuilder
    */
   public function query()
   {
      return $this->db->table($this->table);
   }

   /**
    * Get all records
    * @return array
    */
   public function all()
   {
      return $this->query()->getResultArray();
   }

   /**
    * Get with pagination
    * @param int $perPage
    * @param int $page
    * @return array ['data' => [], 'total' => 0, 'per_page' => 15, 'current_page' => 1]
    */
   public function paginate($perPage = 15, $page = 1)
   {
      $query = $this->query();

      // Count total
      $total = $query->count();

      // Fetch records
      $data = $query
         ->limit($perPage)
         ->offset(($page - 1) * $perPage)
         ->getResultArray();

      return [
         'data' => $data,
         'total' => $total,
         'per_page' => $perPage,
         'current_page' => $page,
         'last_page' => ceil($total / $perPage)
      ];
   }

   /**
    * Find by primary key
    * @param mixed $id
    * @return BaseModel|null
    */
   public function find($id)
   {
      $result = $this->query()
         ->where($this->primaryKey, $id)
         ->first();

      if ($result) {
         return $this->newInstance($result, true);
      }
      return null;
   }

   /**
    * Find by custom field
    * @param string $field
    * @param mixed $value
    * @return BaseModel|null
    */
   public function findBy($field, $value)
   {
      $result = $this->query()
         ->where($field, $value)
         ->first();

      if ($result) {
         return $this->newInstance($result, true);
      }
      return null;
   }

   /**
    * Get first record
    * @return BaseModel|null
    */
   public function first()
   {
      $result = $this->query()->first();

      if ($result) {
         return $this->newInstance($result, true);
      }
      return null;
   }

   /**
    * Where clause
    * @param string $column
    * @param mixed $operator
    * @param mixed $value
    * @return BaseModel (untuk query chaining)
    */
   public function where($column, $operator = null, $value = null)
   {
      $this->query()->where($column, $operator, $value);
      return $this;
   }

   /**
    * Get results dari query
    * @return array
    */
   public function get()
   {
      $results = $this->query()->getResultArray();
      return array_map(function ($row) {
         return $this->newInstance($row, true);
      }, $results);
   }

   /**
    * Count records
    * @return int
    */
   public function count()
   {
      return $this->query()->count();
   }

   /**
    * ============================================================
    * CRUD OPERATIONS
    * ============================================================
    */

   /**
    * Create new record
    * @param array $data
    * @return BaseModel
    */
   public function create($data = [])
   {
      $model = $this->newInstance($data);
      $model->save();
      return $model;
   }

   /**
    * Fill model dengan data (mass assignment)
    * @param array $attributes
    * @return $this
    */
   public function fill($attributes = [])
   {
      $fillable = empty($this->fillable) ? array_keys($attributes) : $this->fillable;

      foreach ($attributes as $key => $value) {
         if (in_array($key, $fillable)) {
            $this->setAttribute($key, $value);
         }
      }

      return $this;
   }

   /**
    * Save model (Create or Update)
    * @return bool
    */
   public function save()
   {
      // Call onSaving hook
      if (method_exists($this, 'onSaving')) {
         $this->onSaving();
      }

      if ($this->exists) {
         return $this->update();
      } else {
         return $this->insert();
      }
   }

   /**
    * Insert new record
    * @return bool
    */
   protected function insert()
   {
      // Call onCreating hook
      if (method_exists($this, 'onCreating')) {
         $this->onCreating();
      }

      // Add timestamps
      if ($this->timestamps && in_array('created_at', $this->timestamps)) {
         $this->setAttribute('created_at', time());
         $this->setAttribute('updated_at', time());
      }

      $data = $this->getAttributes();

      $result = $this->query()->insert($data);

      if ($result) {
         // Set primary key dari last insert id
         $lastId = $this->db->lastInsertId();
         $this->setAttribute($this->primaryKey, $lastId);
         $this->exists = true;
         $this->original = $this->attributes;

         // Call onCreated hook
         if (method_exists($this, 'onCreated')) {
            $this->onCreated();
         }

         return true;
      }

      return false;
   }

   /**
    * Update record
    * @return bool
    */
   protected function update()
   {
      // Call onUpdating hook
      if (method_exists($this, 'onUpdating')) {
         $this->onUpdating();
      }

      // Update timestamp
      if ($this->timestamps && in_array('updated_at', $this->timestamps)) {
         $this->setAttribute('updated_at', time());
      }

      $data = $this->getDirtyAttributes();

      if (empty($data)) {
         return true; // Tidak ada yang berubah
      }

      $result = $this->query()
         ->where($this->primaryKey, $this->getAttribute($this->primaryKey))
         ->update($data);

      if ($result) {
         $this->original = $this->attributes;

         // Call onUpdated hook
         if (method_exists($this, 'onUpdated')) {
            $this->onUpdated();
         }

         return true;
      }

      return false;
   }

   /**
    * Delete record
    * @return bool
    */
   public function delete()
   {
      if (!$this->exists) {
         return true;
      }

      // Call onDeleting hook
      if (method_exists($this, 'onDeleting')) {
         $this->onDeleting();
      }

      if ($this->softDelete) {
         // Soft delete - update deleted_at column
         return $this->query()
            ->where($this->primaryKey, $this->getAttribute($this->primaryKey))
            ->update([$this->deletedAtColumn => time()]);
      } else {
         // Hard delete
         $result = $this->query()
            ->where($this->primaryKey, $this->getAttribute($this->primaryKey))
            ->delete();

         if ($result) {
            // Call onDeleted hook
            if (method_exists($this, 'onDeleted')) {
               $this->onDeleted();
            }
         }

         return $result;
      }
   }

   /**
    * Force delete (hard delete bahkan untuk soft delete model)
    * @return bool
    */
   public function forceDelete()
   {
      if (!$this->exists) {
         return true;
      }

      return $this->query()
         ->where($this->primaryKey, $this->getAttribute($this->primaryKey))
         ->delete();
   }

   /**
    * ============================================================
    * ATTRIBUTE MANAGEMENT
    * ============================================================
    */

   /**
    * Set attribute
    * @param string $key
    * @param mixed $value
    * @return void
    */
   public function setAttribute($key, $value)
   {
      // Apply casting jika ada
      if (isset($this->casts[$key])) {
         $value = $this->castValue($value, $this->casts[$key]);
      }

      $this->attributes[$key] = $value;
   }

   /**
    * Get attribute
    * @param string $key
    * @return mixed
    */
   public function getAttribute($key)
   {
      return $this->attributes[$key] ?? null;
   }

   /**
    * Get all attributes
    * @return array
    */
   public function getAttributes()
   {
      return $this->attributes;
   }

   /**
    * Get dirty attributes (yang berubah)
    * @return array
    */
   public function getDirtyAttributes()
   {
      $dirty = [];

      foreach ($this->attributes as $key => $value) {
         if (!isset($this->original[$key]) || $this->original[$key] !== $value) {
            $dirty[$key] = $value;
         }
      }

      return $dirty;
   }

   /**
    * Check apakah attribute telah diubah
    * @param string|array $keys
    * @return bool
    */
   public function isDirty($keys = [])
   {
      if (empty($keys)) {
         return !empty($this->getDirtyAttributes());
      }

      $keys = is_array($keys) ? $keys : [$keys];

      foreach ($keys as $key) {
         if (!isset($this->original[$key]) || $this->original[$key] !== $this->attributes[$key]) {
            return true;
         }
      }

      return false;
   }

   /**
    * Cast value ke tipe yang sesuai
    * @param mixed $value
    * @param string $type
    * @return mixed
    */
   protected function castValue($value, $type)
   {
      switch ($type) {
         case 'int':
         case 'integer':
            return (int) $value;
         case 'float':
         case 'double':
            return (float) $value;
         case 'string':
            return (string) $value;
         case 'bool':
         case 'boolean':
            return (bool) $value;
         case 'array':
         case 'json':
            return is_string($value) ? json_decode($value, true) : $value;
         case 'object':
            return is_array($value) ? (object) $value : $value;
         default:
            return $value;
      }
   }

   /**
    * ============================================================
    * MAGIC METHODS
    * ============================================================
    */

   /**
    * Get attribute menggunakan magic method
    * @param string $key
    * @return mixed
    */
   public function __get($key)
   {
      // Cek relationship
      if (isset($this->relations[$key])) {
         return $this->relations[$key];
      }

      // Cek attribute
      if (isset($this->attributes[$key])) {
         return $this->attributes[$key];
      }

      return null;
   }

   /**
    * Set attribute menggunakan magic method
    * @param string $key
    * @param mixed $value
    * @return void
    */
   public function __set($key, $value)
   {
      $this->setAttribute($key, $value);
   }

   /**
    * Check isset attribute
    * @param string $key
    * @return bool
    */
   public function __isset($key)
   {
      return isset($this->attributes[$key]) || isset($this->relations[$key]);
   }

   /**
    * ============================================================
    * RELATIONSHIPS
    * ============================================================
    */

   /**
    * Has many relationship
    * @param string $relatedModel Class name model yang related
    * @param string $foreignKey Foreign key di related table
    * @param string $localKey Primary key di model ini
    * @return array
    */
   public function hasMany($relatedModel, $foreignKey = '', $localKey = '')
   {
      if (empty($foreignKey)) {
         $foreignKey = strtolower(class_basename(get_class($this))) . '_id';
      }
      if (empty($localKey)) {
         $localKey = $this->primaryKey;
      }

      $model = new $relatedModel();
      $results = $model->query()
         ->where($foreignKey, $this->getAttribute($localKey))
         ->getResultArray();

      return array_map(function ($row) use ($model) {
         return $model->newInstance($row, true);
      }, $results);
   }

   /**
    * Has one relationship
    * @param string $relatedModel Class name model yang related
    * @param string $foreignKey Foreign key di related table
    * @param string $localKey Primary key di model ini
    * @return BaseModel|null
    */
   public function hasOne($relatedModel, $foreignKey = '', $localKey = '')
   {
      if (empty($foreignKey)) {
         $foreignKey = strtolower(class_basename(get_class($this))) . '_id';
      }
      if (empty($localKey)) {
         $localKey = $this->primaryKey;
      }

      $model = new $relatedModel();
      $result = $model->query()
         ->where($foreignKey, $this->getAttribute($localKey))
         ->first();

      if ($result) {
         return $model->newInstance($result, true);
      }

      return null;
   }

   /**
    * Belongs to relationship
    * @param string $relatedModel Class name model yang related
    * @param string $foreignKey Foreign key di model ini
    * @param string $ownerKey Primary key di related table
    * @return BaseModel|null
    */
   public function belongsTo($relatedModel, $foreignKey = '', $ownerKey = '')
   {
      if (empty($foreignKey)) {
         $foreignKey = strtolower(class_basename($relatedModel)) . '_id';
      }
      if (empty($ownerKey)) {
         $ownerKey = 'id';
      }

      $model = new $relatedModel();
      $result = $model->query()
         ->where($ownerKey, $this->getAttribute($foreignKey))
         ->first();

      if ($result) {
         return $model->newInstance($result, true);
      }

      return null;
   }

   /**
    * ============================================================
    * OUTPUT METHODS
    * ============================================================
    */

   /**
    * Convert model ke array
    * @return array
    */
   public function toArray()
   {
      $data = $this->attributes;

      // Exclude hidden fields
      foreach ($this->hidden as $field) {
         unset($data[$field]);
      }

      // Include relations
      foreach ($this->relations as $key => $value) {
         if (is_array($value)) {
            $data[$key] = array_map(function ($item) {
               return method_exists($item, 'toArray') ? $item->toArray() : $item;
            }, $value);
         } elseif ($value instanceof BaseModel) {
            $data[$key] = $value->toArray();
         } else {
            $data[$key] = $value;
         }
      }

      return $data;
   }

   /**
    * Convert model ke JSON
    * @return string
    */
   public function toJson()
   {
      return json_encode($this->toArray());
   }

   /**
    * ============================================================
    * UTILITIES
    * ============================================================
    */

   /**
    * Create new instance dari model
    * @param array $attributes
    * @param bool $exists
    * @return BaseModel
    */
   public function newInstance($attributes = [], $exists = false)
   {
      $model = new static($attributes);
      $model->exists = $exists;
      if ($exists) {
         $model->original = $model->attributes;
      }
      return $model;
   }

   /**
    * Get class basename
    * @return string
    */
   public static function className()
   {
      return class_basename(static::class);
   }
}
