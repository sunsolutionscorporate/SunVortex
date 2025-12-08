<?php
// Lightweight check for 'products' table using framework Database singleton
require_once __DIR__ . '/../system/Bootstrap.php';

try {
   $db = Database::init();
   $pdo = $db->getPdo();
   $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

   if ($driver === 'mysql') {
      $stmt = $pdo->query("SHOW TABLES LIKE 'products'");
      $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_NUM) : [];
      echo count($rows) ? "products table exists\n" : "products table NOT found\n";
   } elseif ($driver === 'sqlite') {
      $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='products'");
      $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
      echo count($rows) ? "products table exists\n" : "products table NOT found\n";
   } elseif ($driver === 'pgsql' || $driver === 'postgres') {
      $stmt = $pdo->query("SELECT to_regclass('public.products')");
      $val = $stmt ? $stmt->fetchColumn() : null;
      echo $val ? "products table exists\n" : "products table NOT found\n";
   } else {
      echo "Unknown driver: $driver\n";
   }
} catch (Throwable $e) {
   echo "ERROR: " . $e->getMessage() . "\n";
}
