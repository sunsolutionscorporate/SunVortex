<?php

/**
 * tests/test_products.php
 *
 * Verify products table contents. Run via: php sun test products
 */

CLI::print("Running Products table test...");

try {
   $db = Database::init();

   // Count rows
   $c = $db->selectOne('SELECT COUNT(*) AS cnt FROM products');
   $count = (int)($c['cnt'] ?? $c['COUNT(*)'] ?? 0);
   CLI::print("Products count: " . $count, CLI::GREEN);

   // Fetch sample rows (limit 10) including description
   $rows = $db->select('SELECT id, name, stock, price, description FROM products LIMIT 10');
   if (empty($rows)) {
      CLI::print("No product rows found.", CLI::YELLOW);
   } else {
      CLI::print("Sample rows:");
      foreach ($rows as $r) {
         $desc = isset($r['description']) ? trim($r['description']) : null;
         $descPart = $desc !== '' && $desc !== null ? " - {$desc}" : '';
         CLI::print("- [{$r['id']}] {$r['name']} (stock: {$r['stock']}, price: {$r['price']}){$descPart}");
      }
   }

   CLI::print("Products test finished.", CLI::GREEN);
} catch (Throwable $e) {
   CLI::print("ERROR during products test: " . $e->getMessage(), CLI::RED);
}
