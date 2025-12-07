<?php

/**
 * Test QueryBuilder Cache Invalidation - Standalone Test
 */

ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('ROOT_PATH', __DIR__ . '/../');
define('APP_PATH', ROOT_PATH . 'app/');
define('CORE_PATH', ROOT_PATH . 'system/');
define('DISK_PATH', ROOT_PATH . 'storage/');

require_once CORE_PATH . 'Cache/CacheConfig.php';
require_once CORE_PATH . 'database/QueryManager.php';

echo "=== QueryCache Tagging System Test ===\n\n";

CacheConfig::load();

// Test 1: QueryCache tagging
echo "Test 1: QueryCache tagging with put() method\n";
echo "--------------------------------------------\n";

$queryCache = new QueryCache('file', 3600);

// Test put with tags
$queryCache->tags(['table:users'])->put('query_1', ['id' => 1, 'name' => 'Alice'], 3600);
echo "✓ Stored cache query_1 dengan tag: table:users\n";

// Retrieve
$cached = $queryCache->get('query_1');
if ($cached && isset($cached['name']) && $cached['name'] === 'Alice') {
   echo "✓ Retrieved cache query_1: " . json_encode($cached) . "\n";
} else {
   echo "✗ Failed to retrieve cache or data mismatch\n";
}

// Test 2: Multiple queries with tags
echo "\nTest 2: Multiple queries dengan different tags\n";
echo "----------------------------------------------\n";

$queryCache->tags(['table:users'])->put('users_all', ['user1', 'user2', 'user3'], 3600);
echo "✓ Stored cache users_all dengan tag: table:users\n";

$queryCache->tags(['table:products'])->put('products_all', ['prod1', 'prod2'], 3600);
echo "✓ Stored cache products_all dengan tag: table:products\n";

// Verify data exists
$users = $queryCache->get('users_all');
$products = $queryCache->get('products_all');
echo "  - Before flush: users_all=" . ($users ? "EXISTS" : "DELETED") . ", products_all=" . ($products ? "EXISTS" : "DELETED") . "\n";

// Flush only users table
$queryCache->flushTable('users');
echo "✓ Called flushTable('users')\n";

// Verify results
$users_after = $queryCache->get('users_all');
$products_after = $queryCache->get('products_all');
echo "  - After flush: users_all=" . ($users_after ? "EXISTS" : "DELETED") . ", products_all=" . ($products_after ? "EXISTS" : "DELETED") . "\n";

if (!$users_after && $products_after) {
   echo "✓ Table-specific invalidation works correctly!\n";
} else {
   echo "✗ Invalidation has issues\n";
}

// Test 3: Verify old method still works (backward compatibility)
echo "\nTest 3: Backward compatibility - put() without tags\n";
echo "----------------------------------------------------\n";

$queryCache->put('legacy_query', ['data' => 'old_style'], 3600);
echo "✓ Stored cache without tags using put()\n";

$legacy = $queryCache->get('legacy_query');
echo "  - Retrieved: " . ($legacy ? json_encode($legacy) : "NULL") . "\n";

if ($legacy && isset($legacy['data'])) {
   echo "✓ Backward compatibility maintained\n";
} else {
   echo "✗ Backward compatibility broken\n";
}

// Test 4: FlushTag method
echo "\nTest 4: Direct flushTag() method\n";
echo "--------------------------------\n";

$queryCache->tags(['type:admin'])->put('admin_query_1', ['admin' => 'data1'], 3600);
$queryCache->tags(['type:admin'])->put('admin_query_2', ['admin' => 'data2'], 3600);
$queryCache->tags(['type:user'])->put('user_query_1', ['user' => 'data1'], 3600);
echo "✓ Stored 3 caches with different type tags\n";

echo "  - Before flush: admin_query_1=" . ($queryCache->get('admin_query_1') ? "EXISTS" : "DELETED");
echo ", user_query_1=" . ($queryCache->get('user_query_1') ? "EXISTS" : "DELETED") . "\n";

$queryCache->flushTag('type:admin');
echo "✓ Flushed with tag: type:admin\n";

echo "  - After flush: admin_query_1=" . ($queryCache->get('admin_query_1') ? "EXISTS" : "DELETED");
echo ", user_query_1=" . ($queryCache->get('user_query_1') ? "EXISTS" : "DELETED") . "\n";

if (!$queryCache->get('admin_query_1') && !$queryCache->get('admin_query_2') && $queryCache->get('user_query_1')) {
   echo "✓ flushTag() works correctly!\n";
} else {
   echo "✗ flushTag() has issues\n";
}

echo "\n=== All QueryCache Tests Completed ===\n";
