
<?php

// Load cache configuration
require_once __DIR__ . '/CacheConfig.php';

// Polyfill untuk Redis class
if (!class_exists('Redis')) {
   class Redis
   {
      /**
       * @param string $host
       * @param int $port
       * @return bool
       */
      public function connect($host, $port = 6379)
      {
         return false;
      }

      /**
       * @param string $password
       * @return bool
       */
      public function auth($password)
      {
         return false;
      }

      /**
       * @param int $db
       * @return bool
       */
      public function select($db)
      {
         return false;
      }

      /**
       * @param string $key
       * @return string|false|null
       */
      public function get($x)
      {
         return null;
      }

      /**
       * @param string $key
       * @param mixed $value
       * @param int|null $timeout
       * @return bool
       */
      public function set($key, $value, $timeout = null)
      {
         return true;
      }

      /**
       * @param string $key
       * @param int $ttl
       * @param mixed $value
       * @return bool
       */
      public function setex($key, $ttl, $value)
      {
         return true;
      }

      /**
       * @param string $key
       * @return int
       */
      public function incr($key)
      {
         return 0;
      }

      /**
       * @param string $key
       * @return int
       */
      public function del($key)
      {
         return 0;
      }
   };
};

// Polyfill untuk APCu functions
if (!function_exists('apcu_fetch')) {
   function apcu_fetch($x)
   {
      return false;
   };
};
if (!function_exists('apcu_inc')) {
   function apcu_inc($x, $step = 1)
   {
      return 0;
   };
};
if (!function_exists('apcu_store')) {
   function apcu_store($x, $y, $z = 0)
   {
      return true;
   };
};
if (!function_exists('apcu_delete')) {
   function apcu_delete($x)
   {
      return true;
   };
};

/**
 * Centralized Cache Manager
 * 
 * Supports multiple drivers (Redis, APCu, File) and cache types (Query, Throttle, Session, Render)
 * Configuration dari .env file
 */
class Cache
{
   /**
    * Cache type constants
    */
   const TYPE_QUERY = 'query';
   const TYPE_THROTTLE = 'throttle';
   const TYPE_SESSION = 'session';
   const TYPE_RENDER = 'render';

   protected $driver;
   protected $redis;
   protected $cacheDir;
   protected $type = 'default';
   protected $tags = []; // Tags untuk cache entry saat ini

   /**
    * Constructor
    * 
    * @param string $type Cache type (query, throttle, session, render)
    */
   public function __construct($type = 'default')
   {
      $this->type = $type;

      // Get driver from config atau fallback ke detection
      $configDriver = CacheConfig::get('driver');

      if ($configDriver === 'redis' && extension_loaded('redis')) {
         $this->driver = 'redis';
         $this->initRedis();
      } elseif ($configDriver === 'apcu' && extension_loaded('apcu')) {
         $this->driver = 'apcu';
      } else {
         $this->driver = 'file';
         $this->initFileDriver();
      }
   }

   /**
    * Initialize Redis connection
    */
   private function initRedis()
   {
      $this->redis = new Redis();

      $host = CacheConfig::get('redis_host', '127.0.0.1');
      $port = CacheConfig::get('redis_port', 6379);
      $password = CacheConfig::get('redis_password', '');
      $db = CacheConfig::get('redis_db', 0);

      try {
         $this->redis->connect($host, $port);
         if ($password) {
            $this->redis->auth($password);
         }
         $this->redis->select($db);
      } catch (Exception $e) {
         // Fallback to file driver
         $this->driver = 'file';
         $this->initFileDriver();
      }
   }

   /**
    * Initialize file driver
    */
   private function initFileDriver()
   {
      $cachePath = CacheConfig::get('cache_path', '.cache');
      $this->cacheDir = DISK_PATH . $cachePath . '/' . $this->type;
      if (!is_dir($this->cacheDir)) {
         @mkdir($this->cacheDir, 0755, true);
      }
   }

   /**
    * Get cache key dengan type prefix
    * 
    * @param string $key
    * @return string
    */
   protected function buildKey($key)
   {
      if ($this->type !== 'default') {
         return $this->type . ':' . $key;
      }
      return $key;
   }

   /**
    * Get TTL untuk cache type ini
    * 
    * @param int|null $customTtl Custom TTL atau null untuk use default
    * @return int
    */
   protected function getTtl($customTtl = null)
   {
      if ($customTtl !== null) {
         return $customTtl;
      }

      $configKey = $this->type . '_ttl';
      return CacheConfig::get($configKey, CacheConfig::get('ttl', 3600));
   }

   /**
    * Get value from cache
    * 
    * @param string $key
    * @return mixed|null|false
    */
   public function get($key)
   {
      $fullKey = $this->buildKey($key);

      switch ($this->driver) {
         case 'apcu':
            return apcu_fetch($fullKey);

         case 'redis':
            $raw = $this->redis->get($fullKey);
            if ($raw === false || $raw === null) {
               return false;
            }
            $decoded = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE) {
               return $decoded;
            }
            return $raw;

         default:
            $file = $this->cacheDir . '/' . md5($fullKey) . '.cache';
            if (!file_exists($file)) {
               return false;
            }
            $data = json_decode(file_get_contents($file), true);
            if ($data['expires_at'] !== 0 && time() > $data['expires_at']) {
               @unlink($file);
               return false;
            }
            return $data['value'];
      }
   }

   /**
    * Set value to cache
    * 
    * @param string $key
    * @param mixed $value
    * @param int|null $ttl Custom TTL atau null untuk use default
    * @return bool
    */
   public function set($key, $value, $ttl = null)
   {
      $fullKey = $this->buildKey($key);
      $ttl = $this->getTtl($ttl);

      switch ($this->driver) {
         case 'apcu':
            $result = apcu_store($fullKey, $value, $ttl);
            // Untuk APCu, store tags terpisah
            if ($result && !empty($this->tags)) {
               foreach ($this->tags as $tag) {
                  $tagKey = $this->buildKey('tag:' . $tag . ':keys');
                  $keys = apcu_fetch($tagKey) ?: [];
                  if (!in_array($fullKey, $keys)) {
                     $keys[] = $fullKey;
                     apcu_store($tagKey, $keys, $ttl);
                  }
               }
            }
            $this->tags = [];
            return $result;

         case 'redis':
            $payload = json_encode($value);
            if ($ttl > 0 && method_exists($this->redis, 'setex')) {
               $result = $this->redis->setex($fullKey, (int)$ttl, $payload);
            } else {
               $result = $this->redis->set($fullKey, $payload, $ttl ?: null);
            }

            // Store tags association di Redis
            if ($result && !empty($this->tags)) {
               foreach ($this->tags as $tag) {
                  $tagKey = $this->buildKey('tag:' . $tag . ':keys');
                  if (method_exists($this->redis, 'sadd')) {
                     $this->redis->sadd($tagKey, $fullKey);
                     if ($ttl > 0 && method_exists($this->redis, 'expire')) {
                        $this->redis->expire($tagKey, $ttl);
                     }
                  }
               }
            }
            $this->tags = [];
            return $result;

         default:
            $file = $this->cacheDir . '/' . md5($fullKey) . '.cache';
            $data = [
               'value' => $value,
               'expires_at' => $ttl ? time() + $ttl : 0,
               'tags' => $this->tags
            ];
            $result = (bool)file_put_contents($file, json_encode($data));
            $this->tags = [];
            return $result;
      }
   }

   /**
    * Increment value (untuk counter, throttling, dll)
    * 
    * @param string $key
    * @param int $increment
    * @return int
    */
   public function inc($key, $increment = 1)
   {
      $fullKey = $this->buildKey($key);

      if ($this->driver === 'apcu') {
         return apcu_inc($fullKey, $increment);
      } elseif ($this->driver === 'redis') {
         return $this->redis->incr($fullKey);
      } else {
         $val = $this->get($key) ?: 0;
         $val += $increment;
         $ttl = CacheConfig::get($this->type . '_ttl', CacheConfig::get('ttl', 60));
         $this->set($key, $val, $ttl);
         return $val;
      }
   }

   /**
    * Delete cache key
    * 
    * @param string $key
    * @return bool
    * @noinspection PhpUndefinedFunctionInspection
    */
   public function delete($key)
   {
      $fullKey = $this->buildKey($key);

      switch ($this->driver) {
         case 'apcu':
            if (function_exists('apcu_delete')) {
               @apcu_delete($fullKey);
               return true;
            }
            return false;

         case 'redis':
            if (method_exists($this->redis, 'del')) {
               return (bool)$this->redis->del($fullKey);
            }
            return false;

         default:
            $file = $this->cacheDir . '/' . md5($fullKey) . '.cache';
            if (file_exists($file)) {
               return @unlink($file);
            }
            return false;
      }
   }

   /**
    * Check if key exists
    * 
    * @param string $key
    * @return bool
    */
   public function has($key)
   {
      return $this->get($key) !== false;
   }

   /**
    * Get driver being used
    * 
    * @return string redis|apcu|file
    */
   public function getDriver()
   {
      return $this->driver;
   }

   /**
    * Get cache type
    * 
    * @return string
    */
   public function getType()
   {
      return $this->type;
   }

   /**
    * Set tags untuk cache entry berikutnya (fluent interface)
    * 
    * @param array|string $tags
    * @return $this
    */
   public function tags($tags)
   {
      if (is_string($tags)) {
         $this->tags = [$tags];
      } else {
         $this->tags = is_array($tags) ? $tags : [];
      }
      return $this;
   }

   /**
    * Invalidate/flush semua cache dengan tag tertentu
    * 
    * @param string|array $tags
    * @return bool
    */
   public function flushTag($tags)
   {
      if (is_string($tags)) {
         $tags = [$tags];
      }

      $success = true;

      foreach ($tags as $tag) {
         switch ($this->driver) {
            case 'redis':
               $tagKey = $this->buildKey('tag:' . $tag . ':keys');
               if (method_exists($this->redis, 'smembers')) {
                  $cacheKeys = $this->redis->smembers($tagKey);
                  if (is_array($cacheKeys) && count($cacheKeys) > 0) {
                     foreach ($cacheKeys as $key) {
                        if (method_exists($this->redis, 'del')) {
                           $this->redis->del($key);
                        }
                     }
                  }
               }
               if (method_exists($this->redis, 'del')) {
                  $this->redis->del($tagKey);
               }
               break;

            case 'apcu':
               $tagKey = $this->buildKey('tag:' . $tag . ':keys');
               $keys = apcu_fetch($tagKey) ?: [];
               if (is_array($keys) && count($keys) > 0) {
                  foreach ($keys as $key) {
                     if (function_exists('apcu_delete')) {
                        @apcu_delete($key);
                     }
                  }
               }
               if (function_exists('apcu_delete')) {
                  @apcu_delete($tagKey);
               }
               break;

            default:
               // File driver: scan semua file cache dan hapus yang memiliki tag
               $files = @glob($this->cacheDir . '/*.cache');
               if (is_array($files)) {
                  foreach ($files as $file) {
                     try {
                        $content = file_get_contents($file);
                        if ($content !== false) {
                           $data = json_decode($content, true);
                           if (isset($data['tags']) && is_array($data['tags']) && in_array($tag, $data['tags'])) {
                              @unlink($file);
                           }
                        }
                     } catch (Exception $e) {
                        // Ignore errors
                     }
                  }
               }
         }
      }

      return $success;
   }

   /**
    * Invalidate cache untuk tabel tertentu
    * 
    * @param string|array $tables
    * @return bool
    */
   public function flushTable($tables)
   {
      if (is_string($tables)) {
         $tables = [$tables];
      }

      // Setiap table di-tag sebagai "table:{table_name}"
      $tags = array_map(function ($table) {
         return 'table:' . $table;
      }, $tables);

      return $this->flushTag($tags);
   }
}
