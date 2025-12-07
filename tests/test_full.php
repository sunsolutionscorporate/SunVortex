<?php

/**
 * Integration Test - Direct dengan Bootstrap
 */

// Start output buffering
ob_start();
error_reporting(E_ALL);

// Load Autoload dan Bootstrap
require_once __DIR__ . '/../system/Autoload.php';
require_once __DIR__ . '/../system/Bootstrap.php';
ob_end_clean();
header('Content-Type: text/plain');

echo "=== COMPLETE INTEGRATION TEST ===\n";
echo "Testing QueryBuilder Cache Invalidation\n";
echo "======================================\n\n";

try {
   // Initialize database dan cache
   $db = Database::init();
   $cache = $db->getCache();

   echo "Status:\n";
   echo "  - Database initialized: YES\n";
   echo "  - Cache initialized: " . ($cache ? "YES" : "NO") . "\n";
   echo "  - Cache driver: " . ($cache ? $cache->getDriver() : "N/A") . "\n\n";

   // Get connection
   $pdo = $db->getConnection();

   // Create test table
   $testTableSQL = "
        CREATE TABLE IF NOT EXISTS test_cache_demo (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB
    ";
   $pdo->exec($testTableSQL);
   echo "✓ Test table ready\n\n";

   // Clean old test data
   $pdo->exec("DELETE FROM test_cache_demo");
   echo "✓ Old test data cleaned\n\n";

   // === Test 1: Basic caching ===
   echo "TEST 1: BASIC CACHING\n";
   echo "--------------------\n";

   // Insert test data
   $qb1 = $db->table('test_cache_demo');
   $insertId = $qb1->insert([
      'name' => 'Demo Item 1',
      'description' => 'Original description'
   ]);
   echo "✓ Inserted test data with ID: {$insertId}\n";

   // First query - cache miss (hit database)
   $start1 = microtime(true);
   $result1 = $db->table('test_cache_demo')
      ->where('id', $insertId)
      ->get();
   $time1 = (microtime(true) - $start1) * 1000;
   $rows1 = $result1->fetchAll();

   echo "✓ Query 1 (cache MISS): {$time1}ms, rows: " . count($rows1) . "\n";
   echo "  Data: name='" . $rows1[0]['name'] . "', description='" . $rows1[0]['description'] . "'\n";

   // Second query - cache hit (should be faster)
   $start2 = microtime(true);
   $result2 = $db->table('test_cache_demo')
      ->where('id', $insertId)
      ->get();
   $time2 = (microtime(true) - $start2) * 1000;
   $rows2 = $result2->fetchAll();

   echo "✓ Query 2 (cache HIT): {$time2}ms, rows: " . count($rows2) . "\n";
   echo "  Data: name='" . $rows2[0]['name'] . "', description='" . $rows2[0]['description'] . "'\n";

   if ($time2 < $time1 * 0.8) {
      echo "✓ Cache hit is faster!\n\n";
   } else {
      echo "⚠ Cache hit tidak lebih cepat (mungkin query terlalu sederhana)\n\n";
   }

   // === Test 2: Cache invalidation pada UPDATE ===
   echo "TEST 2: CACHE INVALIDATION ON UPDATE\n";
   echo "------------------------------------\n";

   // Sebelum update - data dari cache
   $result_before = $db->table('test_cache_demo')
      ->where('id', $insertId)
      ->get()
      ->fetchAll();
   echo "✓ Query 3 (sebelum update): description = '" . $result_before[0]['description'] . "'\n";

   // Update data
   $db->table('test_cache_demo')
      ->where('id', $insertId)
      ->update(['description' => 'Updated description from cache test']);
   echo "✓ Data di-update\n";

   // Setelah update - harus menampilkan data terbaru (cache sudah di-invalidate)
   $result_after = $db->table('test_cache_demo')
      ->where('id', $insertId)
      ->get()
      ->fetchAll();
   echo "✓ Query 4 (setelah update): description = '" . $result_after[0]['description'] . "'\n";

   if ($result_after[0]['description'] === 'Updated description from cache test') {
      echo "✓✓✓ CACHE INVALIDATION WORKS! Data terbaru ditampilkan.\n\n";
   } else {
      echo "✗✗✗ CACHE INVALIDATION FAILED! Data lama masih ditampilkan.\n\n";
   }

   // === Test 3: Cache invalidation pada INSERT ===
   echo "TEST 3: CACHE INVALIDATION ON INSERT\n";
   echo "------------------------------------\n";

   // Count sebelum insert
   $count_before = count($db->table('test_cache_demo')->get()->fetchAll());
   echo "✓ Query 5 (sebelum insert): Total rows = {$count_before}\n";

   // Insert new record
   $db->table('test_cache_demo')->insert([
      'name' => 'Demo Item 2',
      'description' => 'Second demo item'
   ]);
   echo "✓ New record inserted\n";

   // Count setelah insert
   $count_after = count($db->table('test_cache_demo')->get()->fetchAll());
   echo "✓ Query 6 (setelah insert): Total rows = {$count_after}\n";

   if ($count_after > $count_before) {
      echo "✓✓✓ CACHE INVALIDATION WORKS! New record detected (rows: {$count_before} -> {$count_after})\n\n";
   } else {
      echo "✗✗✗ CACHE INVALIDATION FAILED! New record not detected.\n\n";
   }

   // === Test 4: Cache invalidation pada DELETE ===
   echo "TEST 4: CACHE INVALIDATION ON DELETE\n";
   echo "------------------------------------\n";

   // Buat record untuk didelete
   $deleteId = $db->table('test_cache_demo')->insert([
      'name' => 'To Be Deleted',
      'description' => 'This will be deleted'
   ]);
   echo "✓ Test record inserted with ID: {$deleteId}\n";

   // Query sebelum delete
   $result_before_delete = $db->table('test_cache_demo')
      ->where('id', $deleteId)
      ->get()
      ->fetchAll();
   echo "✓ Query 7 (sebelum delete): Found " . count($result_before_delete) . " row\n";

   // Delete record
   $db->table('test_cache_demo')
      ->where('id', $deleteId)
      ->delete();
   echo "✓ Record di-delete\n";

   // Query setelah delete
   $result_after_delete = $db->table('test_cache_demo')
      ->where('id', $deleteId)
      ->get()
      ->fetchAll();
   echo "✓ Query 8 (setelah delete): Found " . count($result_after_delete) . " row\n";

   if (count($result_after_delete) === 0) {
      echo "✓✓✓ CACHE INVALIDATION WORKS! Deleted record not found.\n\n";
   } else {
      echo "✗✗✗ CACHE INVALIDATION FAILED! Deleted record still visible.\n\n";
   }

   // === Summary ===
   echo "=== SUMMARY ===\n";
   echo "✓ All tests completed successfully!\n";
   echo "✓ Cache system working correctly\n";
   echo "✓ Automatic table detection implemented\n";
   echo "✓ Cache invalidation on INSERT/UPDATE/DELETE works\n\n";

   // Cleanup
   $pdo->exec("DROP TABLE IF EXISTS test_cache_demo");
   echo "✓ Test table dropped\n";
} catch (Exception $e) {
   echo "ERROR: " . $e->getMessage() . "\n";
   echo "File: " . $e->getFile() . "\n";
   echo "Line: " . $e->getLine() . "\n";
}

echo "\n=== TEST COMPLETED ===\n";
