<?php

/**
 * Cache Configuration Manager
 * 
 * Load dan manage cache configuration dari .env file
 */
class CacheConfig
{
   /**
    * Default configuration
    */
   protected static $defaults = [
      'driver' => 'file',
      'ttl' => 3600,
      'query_ttl' => 3600,
      'throttle_ttl' => 60,
      'session_ttl' => 1800,
      'render_ttl' => 7200,
      'redis_host' => '127.0.0.1',
      'redis_port' => 6379,
      'redis_password' => '',
      'redis_db' => 0,
      'cache_path' => '.cache',
   ];

   /**
    * Loaded configuration
    */
   protected static $config = [];

   /**
    * Load configuration from .env file
    * 
    * @param string $envPath Path to .env file
    * @return void
    */
   public static function load($envPath = null)
   {
      if ($envPath === null) {
         $envPath = dirname(dirname(dirname(__FILE__))) . '/.env';
      }

      self::$config = self::$defaults;

      if (!file_exists($envPath)) {
         return;
      }

      $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

      foreach ($lines as $line) {
         // Skip comments
         if (strpos(trim($line), '#') === 0) {
            continue;
         }

         // Parse KEY=VALUE
         if (strpos($line, '=') === false) {
            continue;
         }

         [$key, $value] = explode('=', $line, 2);
         $key = strtolower(trim($key));
         $value = trim($value);

         // Map to config
         $configKey = str_replace('cache_', '', $key);

         // Type casting
         if ($value === 'true') {
            $value = true;
         } elseif ($value === 'false') {
            $value = false;
         } elseif (is_numeric($value)) {
            $value = (int)$value;
         }

         self::$config[$configKey] = $value;
      }
   }

   /**
    * Get config value
    * 
    * @param string $key Config key
    * @param mixed $default Default value if not found
    * @return mixed
    */
   public static function get($key, $default = null)
   {
      $key = strtolower($key);
      return self::$config[$key] ?? $default ?? (self::$defaults[$key] ?? null);
   }

   /**
    * Get all config
    * 
    * @return array
    */
   public static function getAll()
   {
      return self::$config;
   }

   /**
    * Set config value (runtime)
    * 
    * @param string $key
    * @param mixed $value
    * @return void
    */
   public static function set($key, $value)
   {
      self::$config[strtolower($key)] = $value;
   }
}

// Auto-load .env on include
CacheConfig::load();
