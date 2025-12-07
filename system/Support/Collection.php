<?php

/**
 * Collection class untuk manipulasi array hasil query
 * Mirip Laravel Collections
 */
class Collection
{
   protected $items = [];

   public function __construct($items = [])
   {
      $this->items = is_array($items) ? array_values($items) : [];
   }

   /**
    * Map setiap item dengan callback
    */
   public function map($callback)
   {
      return new self(array_map($callback, $this->items));
   }

   /**
    * Filter items berdasarkan callback
    */
   public function filter($callback = null)
   {
      if ($callback === null) {
         return new self(array_filter($this->items));
      }
      return new self(array_filter($this->items, $callback, ARRAY_FILTER_USE_BOTH));
   }

   /**
    * Ambil nilai field tertentu dari setiap item
    */
   public function pluck($key, $indexKey = null)
   {
      $result = [];
      foreach ($this->items as $item) {
         $value = is_array($item) ? ($item[$key] ?? null) : ($item->$key ?? null);
         if ($indexKey !== null) {
            $index = is_array($item) ? ($item[$indexKey] ?? null) : ($item->$indexKey ?? null);
            $result[$index] = $value;
         } else {
            $result[] = $value;
         }
      }
      return $indexKey !== null ? new self($result) : $result;
   }

   /**
    * Chunk items menjadi beberapa bagian
    */
   public function chunk($size)
   {
      $chunks = [];
      foreach (array_chunk($this->items, $size) as $chunk) {
         $chunks[] = new self($chunk);
      }
      return $chunks;
   }

   /**
    * First item atau null
    */
   public function first()
   {
      return isset($this->items[0]) ? $this->items[0] : null;
   }

   /**
    * Last item atau null
    */
   public function last()
   {
      return isset($this->items[count($this->items) - 1]) ? $this->items[count($this->items) - 1] : null;
   }

   /**
    * Jumlah items
    */
   public function count()
   {
      return count($this->items);
   }

   /**
    * Apakah collection kosong
    */
   public function isEmpty()
   {
      return count($this->items) === 0;
   }

   /**
    * Reduce items ke single value
    */
   public function reduce($callback, $initial = null)
   {
      return array_reduce($this->items, $callback, $initial);
   }

   /**
    * Unique items
    */
   public function unique($key = null)
   {
      if ($key === null) {
         return new self(array_unique($this->items, SORT_REGULAR));
      }
      $seen = [];
      $result = [];
      foreach ($this->items as $item) {
         $value = is_array($item) ? ($item[$key] ?? null) : ($item->$key ?? null);
         if (!in_array($value, $seen, true)) {
            $seen[] = $value;
            $result[] = $item;
         }
      }
      return new self($result);
   }

   /**
    * Sort items
    */
   public function sort($callback = null)
   {
      $items = $this->items;
      if ($callback === null) {
         sort($items);
      } else {
         usort($items, $callback);
      }
      return new self($items);
   }

   /**
    * Reverse items
    */
   public function reverse()
   {
      return new self(array_reverse($this->items));
   }

   /**
    * Group items by key
    */
   public function groupBy($key)
   {
      $groups = [];
      foreach ($this->items as $item) {
         $groupKey = is_array($item) ? ($item[$key] ?? 'null') : ($item->$key ?? 'null');
         if (!isset($groups[$groupKey])) {
            $groups[$groupKey] = [];
         }
         $groups[$groupKey][] = $item;
      }
      $result = [];
      foreach ($groups as $key => $items) {
         $result[$key] = new self($items);
      }
      return $result;
   }

   /**
    * Ambil subset items
    */
   public function slice($offset, $length = null)
   {
      return new self(array_slice($this->items, $offset, $length));
   }

   /**
    * Join items sebagai string
    */
   public function implode($key, $glue = ', ')
   {
      $values = $this->pluck($key);
      return implode($glue, $values);
   }

   /**
    * Convert ke array
    */
   public function toArray()
   {
      $result = [];
      foreach ($this->items as $item) {
         if (is_object($item) && method_exists($item, 'toArray')) {
            $result[] = $item->toArray();
         } elseif (is_object($item)) {
            $result[] = (array)$item;
         } else {
            $result[] = $item;
         }
      }
      return $result;
   }

   /**
    * Convert ke JSON
    */
   public function toJson($options = 0)
   {
      return json_encode($this->items, $options);
   }

   /**
    * Iterator support
    */
   public function getIterator()
   {
      return new ArrayIterator($this->items);
   }

   /**
    * Countable interface
    */
   public function __invoke()
   {
      return $this->items;
   }
}
