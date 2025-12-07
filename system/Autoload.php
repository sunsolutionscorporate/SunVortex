<?php

// class Autoload
// {
//    public static function from(string $directory, bool $recursive = true): void
//    {
//       if (!is_dir($directory)) return;

//       $iterator = $recursive
//          ? new \RecursiveIteratorIterator(
//             new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS)
//          )
//          : new \FilesystemIterator($directory, \FilesystemIterator::SKIP_DOTS);

//       /** @var \SplFileInfo $file */
//       foreach ($iterator as $file) {
//          $filename = $file->getFilename();

//          // ❌ Abaikan file yang mengandung '.bak'
//          if (strpos($filename, '.bak') !== false) {
//             continue;
//          }


//          // Folder lain: load semua file PHP seperti biasa
//          if ($file->isFile() && $file->getExtension() === 'php') {
//             require_once $file->getPathname();
//          }
//       }
//    }

//    public static function helpers(string $path = __DIR__ . '/../app/Helpers'): void
//    {
//       self::from($path, true);
//    }
// }

class Autoload
{
   /**
    * Daftar file blacklist.
    * Bisa berupa prefix, substring, atau exact filename.
    */
   private static $blacklist = [
      '.bak',         // substring
      'migration',    // prefix (lowercase)
      'Migration',    // prefix (uppercase)
   ];

   /**
    * Menambah item blacklist dari luar.
    */
   public static function addBlacklist($pattern)
   {
      self::$blacklist[] = $pattern;
   }

   /**
    * Mengecek apakah filename masuk blacklist
    */
   private static function isBlacklisted($filename)
   {
      foreach (self::$blacklist as $pattern) {

         // Substring check
         if (strpos($filename, $pattern) !== false) {
            return true;
         }

         // Prefix check (case-insensitive)
         if (stripos($filename, $pattern) === 0) {
            return true;
         }

         // Exact match (optional)
         if ($filename === $pattern) {
            return true;
         }
      }

      return false;
   }

   /**
    * Autoload directory
    */
   public static function from(string $directory, bool $recursive = true): void
   {
      if (!is_dir($directory)) return;

      $iterator = $recursive
         ? new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS)
         )
         : new \FilesystemIterator($directory, \FilesystemIterator::SKIP_DOTS);

      /** @var \SplFileInfo $file */
      foreach ($iterator as $file) {
         $filename = $file->getFilename();

         // ❌ Abaikan file blacklist
         if (self::isBlacklisted($filename)) {
            continue;
         }

         // ✔ Hanya load file PHP
         if ($file->isFile() && $file->getExtension() === 'php') {
            require_once $file->getPathname();
         }
      }
   }

   public static function helpers(string $path = __DIR__ . '/../app/Helpers'): void
   {
      self::from($path, true);
   }
}


Autoload::from(__DIR__ . '/Interfaces');
Autoload::from(__DIR__ . '/Exceptions');
Autoload::from(__DIR__ . '/Cache');
Autoload::from(__DIR__ . '/Core');
Autoload::from(__DIR__ . '/Support');
Autoload::from(__DIR__ . '/Http');
Autoload::from(__DIR__ . '/database');
