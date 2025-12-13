<?php

/**
 * Base Controller class
 * @property-read \Request $request - Injected by Bootstrap
 * @property-read mixed $penduduk - Injected by model() helper
 */
// class Controller
// {
//    private static $instance;
//    private static $attributes = [];

//    private array $props = [];

//    public function __construct()
//    {
//       self::$instance = &$this;
//       foreach (self::$attributes as $key => $value) {
//          $this->$key = $value;
//       }
//    }

//    public function __get($name)
//    {
//       // Jika properti sudah ada, kembalikan
//       if (property_exists($this, $name)) {
//          return $this->$name;
//       };
//       // Jika tidak ditemukan, kembalikan null
//       return null;
//    }

//    // public function __set($name, $value)
//    // {
//    //    self::$attributes[$name] = $value;
//    // }

//    public static function &get_instance()
//    {
//       return self::$instance;
//    }

//    public static function setAttribute($key, $value)
//    {
//       self::$attributes[$key] = $value;
//    }
// };

class Controller
{
   private static $instance;
   private static $attributes = [];
   private $props = []; // simpan semua atribut dinamis di sini

   public function __construct()
   {
      self::$instance = &$this;
      foreach (self::$attributes as $key => $value) {
         $this->props[$key] = $value;
      }
   }

   public function __get($name)
   {
      return $this->props[$name] ?? null;
   }

   public function __set($name, $value)
   {
      $this->props[$name] = $value;
   }

   public static function &get_instance()
   {
      return self::$instance;
   }

   public static function setAttribute($key, $value)
   {
      self::$attributes[$key] = $value;
   }
}
