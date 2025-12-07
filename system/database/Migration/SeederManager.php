<?php

/**
 * Seeder Manager - Run seeders to populate database
 */

class SeederManager
{
   private $db;
   private static $seedersPath;

   public function __construct(Database $db, ?string $seedersPath = null)
   {
      $this->db = $db;
      self::$seedersPath = $seedersPath ?? DISK_PATH . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'seeders';

      // Try APP_PATH first
      if (defined('APP_PATH') && is_dir(APP_PATH . 'database' . DIRECTORY_SEPARATOR . 'seeders')) {
         self::$seedersPath = APP_PATH . 'database' . DIRECTORY_SEPARATOR . 'seeders';
      }
   }

   /**
    * Get all seeder files
    */
   public function getAllSeeders(): array
   {
      if (!is_dir(self::$seedersPath)) {
         return [];
      }

      $files = scandir(self::$seedersPath);
      $seeders = [];

      foreach ($files as $file) {
         if ($file === '.' || $file === '..') continue;
         if (substr($file, -4) !== '.php') continue;

         $seeders[] = [
            'file' => $file,
            'path' => self::$seedersPath . DIRECTORY_SEPARATOR . $file,
            'name' => str_replace('.php', '', $file),
         ];
      }

      usort($seeders, function ($a, $b) {
         return strcmp($a['file'], $b['file']);
      });

      return $seeders;
   }

   /**
    * Run specific seeder
    */
   public function runSeeder(string $name): array
   {
      $seeders = $this->getAllSeeders();
      $seeder = null;

      foreach ($seeders as $s) {
         if ($s['name'] === $name) {
            $seeder = $s;
            break;
         }
      }

      if (!$seeder) {
         return [
            'status' => 'error',
            'seeder' => $name,
            'message' => "Seeder {$name} not found"
         ];
      }

      try {
         require_once $seeder['path'];

         $className = $this->getSeederClassName($seeder['name']);

         if (!class_exists($className)) {
            throw new Exception("Seeder class {$className} not found");
         }

         $instance = new $className();
         $instance->run();

         return [
            'status' => 'success',
            'seeder' => $seeder['name'],
            'message' => 'Seeded successfully'
         ];
      } catch (Exception $e) {
         return [
            'status' => 'error',
            'seeder' => $seeder['name'],
            'message' => $e->getMessage()
         ];
      }
   }

   /**
    * Run all seeders
    */
   public function runAll(): array
   {
      $seeders = $this->getAllSeeders();
      $results = [];

      foreach ($seeders as $seeder) {
         $result = $this->runSeeder($seeder['name']);
         $results[] = $result;
      }

      return $results;
   }

   /**
    * Get seeder class name from filename
    */
   private function getSeederClassName(string $filename): string
   {
      $words = explode('_', $filename);
      $className = '';

      foreach ($words as $word) {
         $className .= ucfirst($word);
      }

      return $className;
   }

   /**
    * Create seeder file
    */
   public static function create(string $name): string
   {
      $seedersPath = self::$seedersPath;

      if (!is_dir($seedersPath)) {
         mkdir($seedersPath, 0755, true);
      }

      $filename = ucfirst($name) . '.php';
      $filepath = $seedersPath . DIRECTORY_SEPARATOR . $filename;

      $className = ucfirst($name);

      $stub = <<<'STUB'
<?php

class {$className} extends Seeder
{
    /**
     * Run the seeder
     */
    public function run()
    {
        // Truncate table
        $this->truncate('table_name');

        // Insert data
        $this->insertBulk('table_name', [
            [
                'nama' => 'Example 1',
                'jenis' => 'Type A',
                'lokasi' => 'Jakarta',
            ],
            [
                'nama' => 'Example 2',
                'jenis' => 'Type B',
                'lokasi' => 'Surabaya',
            ],
        ]);

        echo "✓ {$className} seeded successfully\n";
    }
}

STUB;

      $stub = str_replace('{$className}', $className, $stub);
      file_put_contents($filepath, $stub);

      return $filepath;
   }
}
