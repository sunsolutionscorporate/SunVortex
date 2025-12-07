<?php

/**
 * QueryBuilder sederhana dengan API mirip CodeIgniter4
 */
class QueryBuilder
{
   /** @var PDO */
   protected $pdo;
   protected $select = '*';
   protected $from = '';
   protected $wheres = [];
   protected $joins = [];
   protected $params = [];
   protected $order = '';
   protected $limit = null;
   protected $offset = null;
   protected $group = '';
   protected $having = [];
   protected $profiler = null;

   // Caching controls (null = follow global, false = disable for this query)
   protected $useCache = null;
   protected $cacheTtl = null;

   /**
    * Normalize a key to be safe for use in parameter names (remove dots/other chars)
    */
   protected function normalizeKey($key)
   {
      return preg_replace('/[^a-zA-Z0-9_]/', '_', $key);
   }

   public function __construct(PDO $pdo)
   {
      $this->pdo = $pdo;
      // Attempt to get profiler from Database singleton
      if (class_exists('Database')) {
         try {
            $db = Database::init();
            if (method_exists($db, 'getProfiler')) {
               $this->profiler = $db->getProfiler();
            }
         } catch (Exception $e) {
            // Profiler not available - continue without it
         }
      }
   }

   public function select($columns = '*')
   {
      $this->select = is_array($columns) ? implode(', ', $columns) : $columns;
      return $this;
   }

   public function from($table)
   {
      $this->from = $table;
      return $this;
   }

   public function table($table)
   {
      return $this->from($table);
   }

   /**
    * Disable cache for this query chain
    */
   public function noCache()
   {
      $this->useCache = false;
      return $this;
   }

   /**
    * Set cache TTL (seconds) for this query
    */
   public function cacheTtl($seconds)
   {
      $this->cacheTtl = (int)$seconds;
      return $this;
   }

   /**
    * Join table
    * Examples:
    *   ->join('residents r', 'r.id = o.id_pend', 'LEFT')
    */
   public function join($table, $condition, $type = 'INNER')
   {
      $t = strtoupper(trim($type));
      $this->joins[] = ($t ? $t . ' JOIN ' : 'JOIN ') . $table . ' ON ' . $condition;
      return $this;
   }

   public function leftJoin($table, $condition)
   {
      return $this->join($table, $condition, 'LEFT');
   }

   public function rightJoin($table, $condition)
   {
      return $this->join($table, $condition, 'RIGHT');
   }

   public function innerJoin($table, $condition)
   {
      return $this->join($table, $condition, 'INNER');
   }

   /**
    * GROUP BY
    */
   public function groupBy($expr)
   {
      $this->group = $expr;
      return $this;
   }

   /**
    * HAVING (accepts string condition or associative array + params)
    */
   public function having($condition, $params = [])
   {
      if (is_array($condition)) {
         foreach ($condition as $k => $v) {
            $clean = $this->normalizeKey($k);
            $ph = 'having_' . $clean . count($this->params);
            $this->having[] = "$k = :$ph";
            $this->params[$ph] = $v;
         }
      } else {
         $this->having[] = $condition;
         if (!empty($params)) {
            foreach ($params as $key => $val) {
               $k = is_string($key) ? ltrim($key, ':') : $key;
               $this->params[$k] = $val;
            }
         }
      }
      return $this;
   }

   /**
    * where can accept string condition with params, or associative array
    * where(['col' => 'val']) => "col = :where_col"
    */
   /**
    * where() mendukung:
    * - where('id', 1)
    * - where(["id"=>1, "name"=>"foo"])
    * - where('id < 10')
    * - where('id = :id', ["id"=>1])
    */
   public function where($key, $value = null, $escape = true)
   {
      if (is_array($key)) {
         foreach ($key as $k => $v) {
            $clean = $this->normalizeKey($k);
            $ph = 'where_' . $clean . count($this->params);
            $this->wheres[] = "$k = :$ph";
            $this->params[$ph] = $v;
         }
      } elseif (is_string($key) && is_array($value)) {
         // expression with bound params: where('a = :a', ['a' => 1])
         $this->wheres[] = $key;
         foreach ($value as $pkey => $pval) {
            $k = is_string($pkey) ? ltrim($pkey, ':') : $pkey;
            $this->params[$k] = $pval;
         }
      } elseif ($value === null) {
         // string ekspresi bebas
         $this->wheres[] = $key;
      } else {
         $clean = $this->normalizeKey($key);
         $ph = 'where_' . $clean . count($this->params);
         $this->wheres[] = "$key = :$ph";
         $this->params[$ph] = $value;
      }
      return $this;
   }

   /**
    * orWhere() mendukung sama seperti where(), tapi digabung OR
    */
   public function orWhere($key, $value = null, $escape = true)
   {
      if (empty($this->wheres)) {
         return $this->where($key, $value, $escape);
      }
      $or = '';
      if (is_array($key)) {
         $conds = [];
         foreach ($key as $k => $v) {
            $clean = $this->normalizeKey($k);
            $ph = 'orwhere_' . $clean . count($this->params);
            $conds[] = "$k = :$ph";
            $this->params[$ph] = $v;
         }
         $or = '(' . implode(' AND ', $conds) . ')';
      } elseif (is_string($key) && is_array($value)) {
         // expression with params
         $or = $key;
         foreach ($value as $pkey => $pval) {
            $k = is_string($pkey) ? ltrim($pkey, ':') : $pkey;
            $this->params[$k] = $pval;
         }
      } elseif ($value === null) {
         $or = $key;
      } else {
         $clean = $this->normalizeKey($key);
         $ph = 'orwhere_' . $clean . count($this->params);
         $or = "$key = :$ph";
         $this->params[$ph] = $value;
      }
      $last = array_pop($this->wheres);
      $this->wheres[] = "($last OR $or)";
      return $this;
   }

   /**
    * LIKE
    */
   public function like($field, $match, $side = 'both')
   {
      $clean = $this->normalizeKey($field);
      $ph = 'like_' . $clean . count($this->params);
      $val = $match;
      if ($side === 'before') $val = "%$match";
      elseif ($side === 'after') $val = "$match%";
      else $val = "%$match%";
      $this->wheres[] = "$field LIKE :$ph";
      $this->params[$ph] = $val;
      return $this;
   }

   public function orLike($field, $match, $side = 'both')
   {
      if (empty($this->wheres)) return $this->like($field, $match, $side);
      $clean = $this->normalizeKey($field);
      $ph = 'orlike_' . $clean . count($this->params);
      $val = $match;
      if ($side === 'before') $val = "%$match";
      elseif ($side === 'after') $val = "$match%";
      else $val = "%$match%";
      $last = array_pop($this->wheres);
      $this->wheres[] = "($last OR $field LIKE :$ph)";
      $this->params[$ph] = $val;
      return $this;
   }

   /**
    * whereIn
    */
   public function whereIn($field, array $values)
   {
      if (empty($values)) return $this;
      $phs = [];
      foreach ($values as $i => $v) {
         $clean = $this->normalizeKey($field);
         $ph = 'in_' . $clean . $i . count($this->params);
         $phs[] = ":$ph";
         $this->params[$ph] = $v;
      }
      $this->wheres[] = "$field IN (" . implode(',', $phs) . ")";
      return $this;
   }

   public function orWhereIn($field, array $values)
   {
      if (empty($values)) return $this;
      $phs = [];
      foreach ($values as $i => $v) {
         $clean = $this->normalizeKey($field);
         $ph = 'orin_' . $clean . $i . count($this->params);
         $phs[] = ":$ph";
         $this->params[$ph] = $v;
      }
      $last = array_pop($this->wheres);
      $this->wheres[] = "($last OR $field IN (" . implode(',', $phs) . "))";
      return $this;
   }

   public function whereNotIn($field, array $values)
   {
      if (empty($values)) return $this;
      $phs = [];
      foreach ($values as $i => $v) {
         $clean = $this->normalizeKey($field);
         $ph = 'notin_' . $clean . $i . count($this->params);
         $phs[] = ":$ph";
         $this->params[$ph] = $v;
      }
      $this->wheres[] = "$field NOT IN (" . implode(',', $phs) . ")";
      return $this;
   }

   public function orWhereNotIn($field, array $values)
   {
      if (empty($values)) return $this;
      $phs = [];
      foreach ($values as $i => $v) {
         $clean = $this->normalizeKey($field);
         $ph = 'ornotin_' . $clean . $i . count($this->params);
         $phs[] = ":$ph";
         $this->params[$ph] = $v;
      }
      $last = array_pop($this->wheres);
      $this->wheres[] = "($last OR $field NOT IN (" . implode(',', $phs) . "))";
      return $this;
   }

   public function whereNull($field)
   {
      $this->wheres[] = "$field IS NULL";
      return $this;
   }

   public function orWhereNull($field)
   {
      if (empty($this->wheres)) return $this->whereNull($field);
      $last = array_pop($this->wheres);
      $this->wheres[] = "($last OR $field IS NULL)";
      return $this;
   }

   public function whereNotNull($field)
   {
      $this->wheres[] = "$field IS NOT NULL";
      return $this;
   }

   public function orWhereNotNull($field)
   {
      if (empty($this->wheres)) return $this->whereNotNull($field);
      $last = array_pop($this->wheres);
      $this->wheres[] = "($last OR $field IS NOT NULL)";
      return $this;
   }

   public function orderBy($expr)
   {
      $this->order = $expr;
      return $this;
   }

   public function limit($limit)
   {
      $this->limit = (int)$limit;
      return $this;
   }

   public function offset($offset)
   {
      $this->offset = (int)$offset;
      return $this;
   }

   /**
    * Extract table names from SQL query for cache tagging
    * 
    * @param string $sql
    * @return array
    */
   protected function extractTablesFromQuery($sql)
   {
      $tables = [];

      // Extract main table from FROM clause
      if (!empty($this->from)) {
         // Handle table aliases: "residents r" -> "residents"
         $fromPart = trim($this->from);
         $parts = preg_split('/\s+/', $fromPart);
         if (!empty($parts[0])) {
            $tables[] = $parts[0];
         }
      }

      // Extract tables from JOIN clauses
      if (!empty($this->joins)) {
         foreach ($this->joins as $join) {
            // Pattern: "LEFT JOIN table t ON ..." or "JOIN table ON ..."
            if (preg_match('/JOIN\s+(\w+)(?:\s+\w+)?/i', $join, $matches)) {
               if (!empty($matches[1])) {
                  $tables[] = $matches[1];
               }
            }
         }
      }

      // Remove duplicates and return
      return array_unique($tables);
   }

   protected function buildSelectSql()
   {
      if (empty($this->from)) {
         throw new DBException('Table not specified');
      }
      $sql = 'SELECT ' . $this->select . ' FROM ' . $this->from;
      if (!empty($this->joins)) {
         $sql .= ' ' . implode(' ', $this->joins);
      }
      if (!empty($this->wheres)) {
         $sql .= ' WHERE ' . implode(' AND ', $this->wheres);
      }
      if (!empty($this->group)) {
         $sql .= ' GROUP BY ' . $this->group;
      }
      if (!empty($this->having)) {
         $sql .= ' HAVING ' . implode(' AND ', $this->having);
      }
      if (!empty($this->order)) {
         $sql .= ' ORDER BY ' . $this->order;
      }
      if ($this->limit !== null) {
         $sql .= ' LIMIT ' . $this->limit;
      }
      if ($this->offset !== null) {
         $sql .= ' OFFSET ' . $this->offset;
      }
      return $sql;
   }

   public function get()
   {
      $sql = $this->buildSelectSql();

      // Attempt to read from cache if enabled
      try {
         $db = null;
         try {
            if (class_exists('Database')) {
               $db = Database::init();
            }
         } catch (Exception $e) {
            $db = null;
         }

         $cacheUsed = false;
         $cacheKey = null;
         if ($db) {
            $cache = $db->getCache();
            $globalCacheEnabled = ($cache !== null);
            if ($this->useCache !== false && $globalCacheEnabled) {
               $cacheKey = 'qb:' . md5($sql . '|' . json_encode($this->params));
               $cached = $cache->get($cacheKey);
               if ($cached !== null) {
                  if (class_exists('Logger')) Logger::debug('Database cache HIT', ['key' => $cacheKey]);

                  // Return QueryResult wrapper around cached array
                  $cachedResult = QueryResult::fromArray($cached);

                  // Log to profiler as a 0ms cached hit (optional)
                  if ($this->profiler && method_exists($this->profiler, 'log')) {
                     $this->profiler->log($sql, array_values($this->params), 0);
                  }

                  return $cachedResult;
               }
            }
         }
      } catch (Exception $e) {
         // If cache subsystem fails, continue to execute query normally
      }

      // if (class_exists('Logger')) {
      //    if (!empty($this->params)) {
      //       Logger::debug('DBQuery get SQL: "', $sql, '" params: ')->addContext($this->params);
      //    } else {
      //       Logger::debug('DBQuery get SQL: "', $sql, '"');
      //    }
      // }
      try {
         $startTime = microtime(true);

         $stmt = $this->pdo->prepare($sql);
         $execParams = [];
         foreach ($this->params as $k => $v) {
            $execParams[$k] = $v;
         }
         $stmt->execute($execParams);

         // Fetch all rows now (we will return a lightweight wrapper)
         $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

         $duration = (int)((microtime(true) - $startTime) * 1000);  // Convert to milliseconds

         $sqlStr = DBException::toQueryStr($sql, $this->params);
         if (class_exists('Logger')) {
            Logger::info('Database executed: "', $sqlStr, '"');
         }

         // Log to QueryProfiler if available
         if ($this->profiler && method_exists($this->profiler, 'log')) {
            $this->profiler->log($sql, array_values($this->params), $duration);
         }

         // Attempt to cache the result if cache available and not disabled for this query
         try {
            if (class_exists('Database')) {
               $db2 = Database::init();
               $cache2 = $db2->getCache();
               if ($cache2 && $this->useCache !== false) {
                  $cacheKey2 = 'qb:' . md5($sql . '|' . json_encode($this->params));

                  // Ekstrak tabel dari query dan set tags
                  $tables = $this->extractTablesFromQuery($sql);
                  if (!empty($tables)) {
                     $cacheTags = array_map(function ($table) {
                        return 'table:' . $table;
                     }, $tables);
                     $cache2->tags($cacheTags);
                  }

                  $cache2->put($cacheKey2, $rows, $this->cacheTtl);
                  if (class_exists('Logger')) Logger::debug('Database cache PUT', ['key' => $cacheKey2, 'tables' => $tables]);
               }
            }
         } catch (Exception $e) {
            // ignore cache write errors
         }

         // Return a lightweight result object (mirrors QueryResult API)
         $resultObj = QueryResult::fromArray($rows);

         return $resultObj;
      } catch (PDOException $e) {
         throw new DBException("DBQuery failed:", $e, $sql, $this->params);
      }
   }

   public function getResultArray()
   {
      return $this->get()->fetchAll();
   }

   public function getRow()
   {
      return $this->get()->fetch();
   }

   public function first()
   {
      $this->limit(1);
      return $this->getRow();
   }

   public function insert(array $data)
   {
      if (empty($this->from)) throw new DBException('Table not specified for insert');
      $cols = array_keys($data);
      $placeholders = [];
      $bind = [];
      foreach ($cols as $c) {
         $ph = 'ins_' . $c;
         $placeholders[] = ':' . $ph;
         $bind[$ph] = $data[$c];
      }
      $sql = 'INSERT INTO ' . $this->from . ' (' . implode(',', $cols) . ') VALUES (' . implode(',', $placeholders) . ')';
      $stmt = $this->pdo->prepare($sql);
      foreach ($bind as $k => $v) $stmt->bindValue(':' . $k, $v);
      try {
         $startTime = microtime(true);
         if (class_exists('Logger')) Logger::debug('DBQuery insert', ['sql' => $sql, 'bind' => $bind]);
         $stmt->execute();
         $duration = (int)((microtime(true) - $startTime) * 1000);
         if (class_exists('Logger')) Logger::info('DBQuery insert executed', ['sql' => $sql]);

         // Log to QueryProfiler if available
         if ($this->profiler && method_exists($this->profiler, 'log')) {
            $this->profiler->log($sql, array_values($bind), $duration);
         }

         // Invalidate cache untuk table ini
         try {
            if (class_exists('Database')) {
               $db = Database::init();
               $cache = $db->getCache();
               if ($cache) {
                  $cache->flushTable($this->from);
                  if (class_exists('Logger')) Logger::debug('QueryBuilder cache invalidated for table', ['table' => $this->from]);
               }
            }
         } catch (Exception $e) {
            // ignore cache invalidation errors
         }

         return $this->pdo->lastInsertId();
      } catch (PDOException $err) {
         throw new DBException("DBQuery insert failed:", $err, $sql, $this->params);
      }
   }

   public function update(array $data)
   {
      if (empty($this->from)) throw new DBException('Table not specified for update');
      if (empty($this->wheres)) throw new DBException('Unsafe update: no WHERE clause');
      $sets = [];
      $bind = [];
      foreach ($data as $k => $v) {
         $ph = 'upd_' . $k;
         $sets[] = "$k = :$ph";
         $bind[$ph] = $v;
      }
      $sql = 'UPDATE ' . $this->from . ' SET ' . implode(', ', $sets) . ' WHERE ' . implode(' AND ', $this->wheres);
      $stmt = $this->pdo->prepare($sql);
      // bind update values
      foreach ($bind as $k => $v) $stmt->bindValue(':' . $k, $v);
      // bind where params
      foreach ($this->params as $k => $v) $stmt->bindValue(':' . $k, $v);
      try {
         $startTime = microtime(true);
         // if (class_exists('Logger')) Logger::debug('DBQuery update', ['sql' => $sql, 'bind' => $bind, 'where' => $this->params]);
         $stmt->execute();
         $duration = (int)((microtime(true) - $startTime) * 1000);
         $rc = $stmt->rowCount();
         if (class_exists('Logger')) Logger::info('Database update executed', ['sql' => $sql, 'rowCount' => $rc]);

         // Log to QueryProfiler if available
         if ($this->profiler && method_exists($this->profiler, 'log')) {
            $this->profiler->log($sql, array_values(array_merge($bind, $this->params)), $duration);
         }

         // Invalidate cache untuk table ini
         try {
            if (class_exists('Database')) {
               $db = Database::init();
               $cache = $db->getCache();
               if ($cache) {
                  $cache->flushTable($this->from);
                  if (class_exists('Logger')) Logger::debug('Database cache invalidated for table', ['table' => $this->from]);
               }
            }
         } catch (Exception $e) {
            // ignore cache invalidation errors
         }

         return $rc;
      } catch (PDOException $err) {
         throw new DBException("Database update failed:", $err, $sql, $this->params);
      }
   }

   public function delete()
   {
      if (empty($this->from)) throw new DBException('Table not specified for delete');
      if (empty($this->wheres)) throw new DBException('Unsafe delete: no WHERE clause');
      $sql = 'DELETE FROM ' . $this->from . ' WHERE ' . implode(' AND ', $this->wheres);
      $stmt = $this->pdo->prepare($sql);
      foreach ($this->params as $k => $v) $stmt->bindValue(':' . $k, $v);
      try {
         $startTime = microtime(true);
         if (class_exists('Logger')) Logger::debug('DBQuery delete', ['sql' => $sql, 'where' => $this->params]);
         $stmt->execute();
         $duration = (int)((microtime(true) - $startTime) * 1000);
         $rc = $stmt->rowCount();
         if (class_exists('Logger')) Logger::info('DBQuery delete executed', ['sql' => $sql, 'rowCount' => $rc]);

         // Log to QueryProfiler if available
         if ($this->profiler && method_exists($this->profiler, 'log')) {
            $this->profiler->log($sql, array_values($this->params), $duration);
         }

         // Invalidate cache untuk table ini
         try {
            if (class_exists('Database')) {
               $db = Database::init();
               $cache = $db->getCache();
               if ($cache) {
                  $cache->flushTable($this->from);
                  if (class_exists('Logger')) Logger::debug('QueryBuilder cache invalidated for table', ['table' => $this->from]);
               }
            }
         } catch (Exception $e) {
            // ignore cache invalidation errors
         }

         return $rc;
      } catch (PDOException $err) {
         throw new DBException("DBQuery delete failed:", $err, $sql, $this->params);
      }
   }

   public function toSql()
   {
      return $this->buildSelectSql();
   }

   /**
    * Build a COUNT(*) SQL from the current builder state.
    * If GROUP BY / HAVING present, wraps the grouped query in a subselect.
    */
   protected function buildCountSql()
   {
      if (empty($this->from)) {
         throw new DBException('Table not specified');
      }

      // base FROM + JOINs + WHERE
      $base = 'FROM ' . $this->from;
      if (!empty($this->joins)) {
         $base .= ' ' . implode(' ', $this->joins);
      }
      if (!empty($this->wheres)) {
         $base .= ' WHERE ' . implode(' AND ', $this->wheres);
      }

      // if there's a GROUP BY or HAVING, we need to count the grouped rows via subquery
      if (!empty($this->group) || !empty($this->having)) {
         $inner = 'SELECT 1 ' . $base;
         if (!empty($this->group)) {
            $inner .= ' GROUP BY ' . $this->group;
         }
         if (!empty($this->having)) {
            $inner .= ' HAVING ' . implode(' AND ', $this->having);
         }
         return 'SELECT COUNT(*) as total FROM (' . $inner . ') AS tmp_count';
      }

      // simple count without grouping
      $sql = 'SELECT COUNT(*) as total ' . $base;
      // HAVING without GROUP: wrap as subquery
      if (!empty($this->having)) {
         $inner = 'SELECT 1 ' . $base . ' HAVING ' . implode(' AND ', $this->having);
         return 'SELECT COUNT(*) as total FROM (' . $inner . ') AS tmp_count';
      }

      return $sql;
   }

   /**
    * Execute a COUNT(*) based on current builder state and return integer total.
    * @param bool $reset Jika true, reset builder state (select, where, joins, etc) agar siap untuk next query
    * @return int Total baris sesuai kondisi saat ini
    */
   public function countAllResults($reset = true)
   {
      $sql = $this->buildCountSql();
      $stmt = $this->pdo->prepare($sql);
      $execParams = [];
      foreach ($this->params as $k => $v) {
         $execParams[$k] = $v;
      }
      try {
         $stmt->execute($execParams);
         $row = $stmt->fetch(PDO::FETCH_ASSOC);
         $total = ($row === false) ? 0 : (isset($row['total']) ? (int)$row['total'] : 0);

         if (class_exists('Logger')) Logger::debug("DBQuery countAllResults total {$total} SQL", ['sql' => $sql, 'params' => $execParams]);
         // if (class_exists('Logger')) Logger::info('DBQuery countAllResults result', ['total' => $total]);

         // Reset builder state jika diminta (untuk pagination)
         if ($reset) {
            $this->resetBuilder();
         }

         return $total;
      } catch (PDOException $err) {
         throw new DBException("DBQuery countAllResults failed:", $err, $sql, $this->params);
      }
   }

   /**
    * Reset builder state: clear select, wheres, joins, group, having, order, limit, offset, params
    * Gunakan setelah countAllResults(false) jika Anda ingin reset manual.
    */
   public function resetBuilder()
   {
      $this->select = '*';
      $this->wheres = [];
      $this->joins = [];
      $this->params = [];
      $this->order = '';
      $this->limit = null;
      $this->offset = null;
      $this->group = '';
      $this->having = [];
      return $this;
   }
}
