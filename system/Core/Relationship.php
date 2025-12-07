<?php

/**
 * Base Relationship class
 * Handle eager/lazy loading dengan N+1 prevention
 */
abstract class Relationship
{
   protected $parent;
   protected $related;
   protected $foreignKey;
   protected $ownerKey;

   /**
    * @param mixed $parent
    * @param string $related
    * @param string $foreignKey
    * @param string $ownerKey
    */
   public function __construct($parent, $related, $foreignKey, $ownerKey = 'id')
   {
      $this->parent = $parent;
      $this->related = $related;
      $this->foreignKey = $foreignKey;
      $this->ownerKey = $ownerKey;
   }

   abstract public function getResults();
}

/**
 * BelongsTo Relationship: Many-to-One
 * Contoh: Post belongsTo User
 */
class BelongsTo extends Relationship
{
   public function getResults()
   {
      $parentId = $this->parent->getAttribute($this->foreignKey);
      if (!$parentId) return null;

      $relatedModel = $this->related;
      // Use instance method to be compatible with Model refactor
      return (new $relatedModel())->find($parentId);
   }

   /**
    * @param array $models
    * @param string $relation
    * @param string $foreignKey
    * @param string $primaryKey
    * @return array
    */
   public static function eagerLoad($models, $relation, $foreignKey, $primaryKey = 'id')
   {
      // Collect all foreign keys
      $keys = [];
      foreach ($models as $model) {
         $key = $model->getAttribute($foreignKey);
         if ($key && !in_array($key, $keys)) {
            $keys[] = $key;
         }
      }

      if (empty($keys)) return [];

      // Load all related models sekaligus (prevent N+1)
      $relatedClass = $relation;
      // Use instance query() to match Model instance API

      $rows = (new $relatedClass())->query()->whereIn($primaryKey, $keys)->getResultArray();

      $map = [];
      foreach ($rows as $item) {
         $map[$item[$primaryKey]] = new $relatedClass($item);
      }

      return $map;
   }
}

/**
 * HasMany Relationship: One-to-Many
 * Contoh: User hasMany Post
 */
class HasMany extends Relationship
{
   public function getResults()
   {
      $parentId = $this->parent->getAttribute($this->ownerKey);
      if (!$parentId) return new Collection();

      $relatedModel = $this->related;
      $rows = (new $relatedModel())->query()
         ->where($this->foreignKey, $parentId)
         ->getResultArray();

      $collection = [];
      foreach ($rows as $row) {
         $model = new $relatedModel($row);
         $collection[] = $model;
      }
      return new Collection($collection);
   }

   /**
    * @param array $models
    * @param string $relation
    * @param string $foreignKey
    * @param string $ownerKey
    * @return array
    */
   public static function eagerLoad($models, $relation, $foreignKey, $ownerKey = 'id')
   {
      $keys = [];
      foreach ($models as $model) {
         $key = $model->getAttribute($ownerKey);
         if ($key !== null) {
            $keys[] = (string)$key;
         }
      }
      $keys = array_values(array_unique($keys));

      if (empty($keys)) return [];

      $relatedClass = $relation;
      // Normalize keys as strings to avoid type-mismatch when mapping
      $rows = (new $relatedClass())->query()->whereIn($foreignKey, $keys)->getResultArray();

      $map = [];
      foreach ($rows as $item) {
         $fkValue = (string)$item[$foreignKey];
         if (!isset($map[$fkValue])) {
            $map[$fkValue] = [];
         }
         $map[$fkValue][] = new $relatedClass($item);
      }

      return $map;
   }
}

/**
 * HasOne Relationship: One-to-One
 * Contoh: User hasOne Profile
 */
class HasOne extends Relationship
{
   public function getResults()
   {
      $parentId = $this->parent->getAttribute($this->ownerKey);
      if (!$parentId) return null;

      $relatedModel = $this->related;
      $data = (new $relatedModel())->query()
         ->where($this->foreignKey, $parentId)
         ->limit(1)
         ->first();

      if ($data) {
         return new $relatedModel($data);
      }
      return null;
   }

   /**
    * @param array $models
    * @param string $relation
    * @param string $foreignKey
    * @param string $ownerKey
    * @return array
    */
   public static function eagerLoad($models, $relation, $foreignKey, $ownerKey = 'id')
   {
      $keys = [];
      foreach ($models as $model) {
         $key = $model->getAttribute($ownerKey);
         if ($key && !in_array($key, $keys)) {
            $keys[] = $key;
         }
      }

      if (empty($keys)) return [];

      $relatedClass = $relation;
      $rows = (new $relatedClass())->query()->whereIn($foreignKey, $keys)->getResultArray();

      $map = [];
      foreach ($rows as $item) {
         $fkValue = $item[$foreignKey];
         $map[$fkValue] = new $relatedClass($item);
      }

      return $map;
   }
}
