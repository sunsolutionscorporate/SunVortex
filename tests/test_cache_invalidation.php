<?php

/**
 * Test Endpoint untuk Cache Invalidation System
 * 
 * Cara menggunakan:
 * GET  /public/test_cache_invalidation.php?action=test1  -> Test basic cache hit/miss
 * GET  /public/test_cache_invalidation.php?action=test2  -> Test cache invalidation pada UPDATE
 * GET  /public/test_cache_invalidation.php?action=test3  -> Test cache invalidation pada INSERT
 * GET  /public/test_cache_invalidation.php?action=test4  -> Test cache invalidation pada DELETE
 * GET  /public/test_cache_invalidation.php?action=cleanup -> Cleanup test data
 */

// Include bootstrap
require_once __DIR__ . '/../system/Bootstrap.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? 'test1';
$response = [
   'action' => $action,
   'success' => false,
   'messages' => [],
   'data' => null
];

try {
   // Initialize database
   $db = Database::init();
   $cache = $db->getCache();

   if (!$cache) {
      throw new Exception('Cache system tidak terinialisasi');
   }

   // Ensure test table exists
   $pdo = $db->getConnection();

   // Create test table jika belum ada
   $createTableSQL = "
        CREATE TABLE IF NOT EXISTS test_cache_invalidation (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ";
   $pdo->exec($createTableSQL);
   $response['messages'][] = 'Test table ready';

   switch ($action) {
      case 'test1':
         // Test 1: Basic cache hit and miss
         $response['messages'][] = '=== Test 1: Basic Cache Hit/Miss ===';

         // Insert test data
         $db->table('test_cache_invalidation')->insert([
            'name' => 'Test Item 1',
            'description' => 'This is a test item'
         ]);
         $response['messages'][] = 'Test data inserted';

         // First query - should hit database
         $start = microtime(true);
         $result1 = $db->table('test_cache_invalidation')
            ->where('name', 'Test Item 1')
            ->get();
         $time1 = (microtime(true) - $start) * 1000;
         $response['messages'][] = "First query (cache miss): {$time1}ms, rows: " . count($result1->fetchAll());

         // Second query - should hit cache
         $start = microtime(true);
         $result2 = $db->table('test_cache_invalidation')
            ->where('name', 'Test Item 1')
            ->get();
         $time2 = (microtime(true) - $start) * 1000;
         $response['messages'][] = "Second query (cache hit): {$time2}ms, rows: " . count($result2->fetchAll());

         // Cache hit should be much faster
         if ($time2 < $time1) {
            $response['success'] = true;
            $response['messages'][] = "✓ Cache hit is faster ({$time2}ms < {$time1}ms)";
         } else {
            $response['messages'][] = "⚠ Cache hit tidak lebih cepat (mungkin query terlalu cepat)";
         }

         break;

      case 'test2':
         // Test 2: Cache invalidation pada UPDATE
         $response['messages'][] = '=== Test 2: Cache Invalidation pada UPDATE ===';

         // Insert test data
         $insertId = $db->table('test_cache_invalidation')->insert([
            'name' => 'Test Item Update',
            'description' => 'Original description'
         ]);
         $response['messages'][] = "Test data inserted dengan ID: {$insertId}";

         // First query
         $result1 = $db->table('test_cache_invalidation')
            ->where('id', $insertId)
            ->get()
            ->fetchAll();
         $response['messages'][] = "Query 1 (before update): description = '" . $result1[0]['description'] . "'";

         // Update data
         $db->table('test_cache_invalidation')
            ->where('id', $insertId)
            ->update(['description' => 'Updated description']);
         $response['messages'][] = 'Data di-update';

         // Second query - should show updated data karena cache sudah di-invalidate
         $result2 = $db->table('test_cache_invalidation')
            ->where('id', $insertId)
            ->get()
            ->fetchAll();
         $response['messages'][] = "Query 2 (after update): description = '" . $result2[0]['description'] . "'";

         if ($result2[0]['description'] === 'Updated description') {
            $response['success'] = true;
            $response['messages'][] = '✓ Cache berhasil di-invalidate, data terbaru ditampilkan';
         } else {
            $response['messages'][] = '✗ Cache tidak di-invalidate, data lama masih ditampilkan';
         }

         break;

      case 'test3':
         // Test 3: Cache invalidation pada INSERT
         $response['messages'][] = '=== Test 3: Cache Invalidation pada INSERT ===';

         // Query untuk count semua row
         $countResult1 = $db->table('test_cache_invalidation')
            ->get()
            ->fetchAll();
         $count1 = count($countResult1);
         $response['messages'][] = "Query 1 (before insert): Total rows = {$count1}";

         // Insert new data
         $db->table('test_cache_invalidation')->insert([
            'name' => 'New Item From Cache Test',
            'description' => 'Testing INSERT cache invalidation'
         ]);
         $response['messages'][] = 'New data inserted';

         // Query lagi - cache harus di-invalidate sehingga mendapat data terbaru
         $countResult2 = $db->table('test_cache_invalidation')
            ->get()
            ->fetchAll();
         $count2 = count($countResult2);
         $response['messages'][] = "Query 2 (after insert): Total rows = {$count2}";

         if ($count2 > $count1) {
            $response['success'] = true;
            $response['messages'][] = "✓ Cache berhasil di-invalidate, data baru terdeteksi (rows: {$count1} -> {$count2})";
         } else {
            $response['messages'][] = "✗ Cache tidak di-invalidate, data baru tidak terdeteksi";
         }

         break;

      case 'test4':
         // Test 4: Cache invalidation pada DELETE
         $response['messages'][] = '=== Test 4: Cache Invalidation pada DELETE ===';

         // Insert test data
         $insertId = $db->table('test_cache_invalidation')->insert([
            'name' => 'Item To Delete',
            'description' => 'This will be deleted'
         ]);
         $response['messages'][] = "Test data inserted dengan ID: {$insertId}";

         // Query untuk verifikasi data ada
         $result1 = $db->table('test_cache_invalidation')
            ->where('id', $insertId)
            ->get()
            ->fetchAll();
         $response['messages'][] = "Query 1 (before delete): Found " . count($result1) . " row(s)";

         // Delete data
         $db->table('test_cache_invalidation')
            ->where('id', $insertId)
            ->delete();
         $response['messages'][] = 'Data di-delete';

         // Query lagi - cache harus di-invalidate
         $result2 = $db->table('test_cache_invalidation')
            ->where('id', $insertId)
            ->get()
            ->fetchAll();
         $response['messages'][] = "Query 2 (after delete): Found " . count($result2) . " row(s)";

         if (count($result2) === 0) {
            $response['success'] = true;
            $response['messages'][] = '✓ Cache berhasil di-invalidate, data terhapus terdeteksi';
         } else {
            $response['messages'][] = '✗ Cache tidak di-invalidate, data lama masih ditampilkan';
         }

         break;

      case 'cleanup':
         // Cleanup test data
         $response['messages'][] = '=== Cleanup Test Data ===';
         $pdo->exec('DROP TABLE IF EXISTS test_cache_invalidation');
         $response['success'] = true;
         $response['messages'][] = 'Test table di-drop';
         break;

      default:
         $response['messages'][] = 'Aksi tidak dikenal. Gunakan: test1, test2, test3, test4, cleanup';
   }
} catch (Exception $e) {
   $response['messages'][] = 'ERROR: ' . $e->getMessage();
   $response['messages'][] = 'File: ' . $e->getFile();
   $response['messages'][] = 'Line: ' . $e->getLine();
}

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
