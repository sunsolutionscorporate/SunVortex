<?php

/**
 * Wrapper hasil query PDO untuk API yang nyaman (mirip CI4)
 */
class QueryResult
{
   /** @var PDOStatement|null */
   protected $stmt;
   protected $cache = null;

   public function __construct(?PDOStatement $stmt = null)
   {
      $this->stmt = $stmt;
   }

   /**
    * Ambil semua rows sebagai array asosiatif
    */
   public function fetchAll($fetchMode = PDO::FETCH_ASSOC)
   {
      if ($this->cache === null) {
         $this->cache = $this->stmt->fetchAll($fetchMode);
      }
      return $this->cache;
   }

   /** Ambil satu row */
   public function fetch($fetchMode = PDO::FETCH_ASSOC)
   {
      // jika sudah di-cache, kembalikan dan hapus first element
      if ($this->cache !== null) {
         return count($this->cache) ? $this->cache[0] : null;
      }
      return $this->stmt->fetch($fetchMode);
   }

   public function getResultArray()
   {
      return $this->fetchAll();
   }

   public function getRow($index = 0)
   {
      $rows = $this->fetchAll();
      return isset($rows[$index]) ? $rows[$index] : null;
   }

   public function getFirstRow()
   {
      return $this->getRow(0);
   }

   public function getNumRows()
   {
      $rows = $this->fetchAll();
      return is_array($rows) ? count($rows) : 0;
   }

   public function rowCount()
   {
      // For array-based results (from cache or direct array), use row count
      // For PDO-based results, use PDOStatement::rowCount()
      if ($this->stmt !== null && method_exists($this->stmt, 'rowCount')) {
         return $this->stmt->rowCount();
      }
      // For array results, return number of rows in cache
      return $this->getNumRows();
   }

   public function getStatement()
   {
      return $this->stmt;
   }

   /**
    * Factory method: Create QueryResult from array (untuk cache atau hasil query)
    * Berguna untuk membungkus hasil array (cache hit atau setelah fetchAll) dalam API QueryResult yang konsisten
    * 
    * @param array $rows - Array of result rows
    * @return QueryResult
    */
   public static function fromArray(array $rows)
   {
      $result = new self(null);
      $result->cache = $rows;
      return $result;
   }
}
