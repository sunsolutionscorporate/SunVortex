<?php

/**
 * Database Seeder Base Class
 * 
 * Untuk mengisi data awal/dummy ke database
 * 
 * Usage:
 *   class UserSeeder extends Seeder {
 *       public function run() {
 *           $this->db->insert('users', [...]);
 *       }
 *   }
 */

abstract class Seeder
{
   protected $db;

   public function __construct()
   {
      $this->db = Database::init();
   }

   /**
    * Run the seeder
    */
   abstract public function run();

   /**
    * Truncate table (hapus semua data)
    */
   protected function truncate(string $table): void
   {
      $sql = "TRUNCATE TABLE `{$table}`";
      $this->db->statement($sql);
   }

   /**
    * Insert single row
    */
   protected function insert(string $table, array $data): void
   {
      $columns = implode(',', array_keys($data));
      $placeholders = implode(',', array_fill(0, count($data), '?'));
      $sql = "INSERT INTO `{$table}` ({$columns}) VALUES ({$placeholders})";
      $this->db->insert($sql, array_values($data));
   }

   /**
    * Insert multiple rows
    */
   protected function insertBulk(string $table, array $rows): void
   {
      foreach ($rows as $row) {
         $this->insert($table, $row);
      }
   }

   /**
    * Delete rows by condition
    */
   protected function delete(string $table, string $condition = '1=1'): void
   {
      $sql = "DELETE FROM `{$table}` WHERE {$condition}";
      $this->db->statement($sql);
   }

   /**
    * Call another seeder
    */
   protected function call(string $seederClass): void
   {
      if (!class_exists($seederClass)) {
         throw new Exception("Seeder class {$seederClass} not found");
      }

      $seeder = new $seederClass();
      echo "Seeding: {$seederClass}\n";
      $seeder->run();
   }

   /**
    * Generate faker data (helper)
    */
   protected function faker($type = 'name', $options = [])
   {
      return FakerHelper::generate($type, $options);
   }
}

/**
 * Faker Helper - Generate dummy data
 */
class FakerHelper
{
   private static $firstNames = ['John', 'Jane', 'Bob', 'Alice', 'Charlie', 'Diana', 'Eve', 'Frank'];
   private static $lastNames = ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller'];
   private static $domains = ['gmail.com', 'yahoo.com', 'outlook.com', 'example.com'];

   public static function generate(string $type, array $options = [])
   {
      switch ($type) {
         case 'name':
            return self::name();
         case 'email':
            return self::email();
         case 'phone':
            return self::phone();
         case 'address':
            return self::address();
         case 'company':
            return self::company();
         case 'text':
            return self::text($options['length'] ?? 50);
         case 'number':
            return self::number($options['min'] ?? 1, $options['max'] ?? 100);
         case 'boolean':
            return self::boolean();
         case 'date':
            return self::date($options['format'] ?? 'Y-m-d');
         case 'uuid':
            return self::uuid();
         default:
            return null;
      }
   }

   public static function name(): string
   {
      $first = self::$firstNames[array_rand(self::$firstNames)];
      $last = self::$lastNames[array_rand(self::$lastNames)];
      return "{$first} {$last}";
   }

   public static function email(): string
   {
      $name = strtolower(str_replace(' ', '.', self::name()));
      $domain = self::$domains[array_rand(self::$domains)];
      return "{$name}@{$domain}";
   }

   public static function phone(): string
   {
      return '+62' . rand(100000000, 999999999);
   }

   public static function address(): string
   {
      $streets = ['Jl. Main', 'Jl. Oak', 'Jl. Elm', 'Jl. Maple'];
      $cities = ['Jakarta', 'Surabaya', 'Bandung', 'Yogyakarta'];
      return rand(1, 999) . ' ' . $streets[array_rand($streets)] . ', ' . $cities[array_rand($cities)];
   }

   public static function company(): string
   {
      $companies = ['Acme Corp', 'Tech Solutions', 'Global Industries', 'Digital Systems', 'Smart Tech'];
      return $companies[array_rand($companies)];
   }

   public static function text(int $length = 50): string
   {
      $words = ['lorem', 'ipsum', 'dolor', 'sit', 'amet', 'consectetur', 'adipiscing', 'elit'];
      $text = '';
      while (strlen($text) < $length) {
         $text .= $words[array_rand($words)] . ' ';
      }
      return trim(substr($text, 0, $length));
   }

   public static function number(int $min = 1, int $max = 100): int
   {
      return rand($min, $max);
   }

   public static function boolean(): bool
   {
      return (bool) rand(0, 1);
   }

   public static function date(string $format = 'Y-m-d'): string
   {
      $timestamp = rand(strtotime('-1 year'), time());
      return date($format, $timestamp);
   }

   public static function uuid(): string
   {
      return sprintf(
         '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
         rand(0, 0xffff),
         rand(0, 0xffff),
         rand(0, 0xffff),
         rand(0, 0x0fff) | 0x4000,
         rand(0, 0x3fff) | 0x8000,
         rand(0, 0xffff),
         rand(0, 0xffff),
         rand(0, 0xffff)
      );
   }
}
