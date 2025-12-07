<?php

/**
 * Test Caching System - Standalone Test
 */

// Suppress headers warning
ob_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define constants
define('ROOT_PATH', __DIR__ . '/../');
define('APP_PATH', ROOT_PATH . 'app/');
define('CORE_PATH', ROOT_PATH . 'system/');
define('DISK_PATH', ROOT_PATH . 'storage/');

// Load required files
require_once CORE_PATH . 'Cache/CacheConfig.php';
require_once CORE_PATH . 'Cache/Cache.php';
require_once CORE_PATH . 'database/QueryManager.php';

echo "=== Cache Invalidation System Test ===\n\n";

// Test 1: Cache tagging dengan file driver
echo "Test 1: Testing Cache tagging system\n";
echo "-------------------------------------\n";

CacheConfig::load();

$cache = new Cache('query');
echo "Cache driver: " . $cache->getDriver() . "\n";

// Set some test data dengan tags
$cache->tags(['table:users', 'table:profiles'])->set('user_1', ['id' => 1, 'name' => 'John'], 3600);
echo "✓ Cached user_1 dengan tags: table:users, table:profiles\n";

// Retrieve data
$cached = $cache->get('user_1');
if ($cached && $cached['id'] === 1) {
   echo "✓ Cache hit: " . json_encode($cached) . "\n";
} else {
   echo "✗ Cache miss atau data tidak sesuai\n";
}

// Test 2: Invalidate by tag
echo "\nTest 2: Testing tag-based invalidation\n";
echo "--------------------------------------\n";

$cache->tags(['table:users'])->set('user_2', ['id' => 2, 'name' => 'Jane'], 3600);
echo "✓ Cached user_2 dengan tag: table:users\n";

$cache->tags(['table:products'])->set('product_1', ['id' => 1, 'name' => 'Laptop'], 3600);
echo "✓ Cached product_1 dengan tag: table:products\n";

// Invalidate table:users
$cache->flushTable('users');
echo "✓ Flushed all cache dengan tag: table:users\n";

// Check results
$user1 = $cache->get('user_1');
$user2 = $cache->get('user_2');
$product1 = $cache->get('product_1');

echo "  - user_1 cache: " . ($user1 ? "EXISTS" : "DELETED") . " (expected: DELETED)\n";
echo "  - user_2 cache: " . ($user2 ? "EXISTS" : "DELETED") . " (expected: DELETED)\n";
echo "  - product_1 cache: " . ($product1 ? "EXISTS" : "DELETED") . " (expected: EXISTS)\n";

if (!$user1 && !$user2 && $product1) {
   echo "✓ Tag-based invalidation works correctly!\n";
} else {
   echo "✗ Tag-based invalidation has issues\n";
}

// Test 3: FlushTable method
echo "\nTest 3: Testing flushTable method\n";
echo "----------------------------------\n";

$cache->tags(['table:orders'])->set('order_1', ['id' => 1, 'total' => 100], 3600);
$cache->tags(['table:orders'])->set('order_2', ['id' => 2, 'total' => 200], 3600);
echo "✓ Cached order_1 dan order_2 dengan tag: table:orders\n";

$order1_before = $cache->get('order_1');
$order2_before = $cache->get('order_2');
echo "  - Before flush: order_1=" . ($order1_before ? "EXISTS" : "DELETED") . ", order_2=" . ($order2_before ? "EXISTS" : "DELETED") . "\n";

$cache->flushTable('orders');
echo "✓ Called flushTable('orders')\n";

$order1_after = $cache->get('order_1');
$order2_after = $cache->get('order_2');
echo "  - After flush: order_1=" . ($order1_after ? "EXISTS" : "DELETED") . ", order_2=" . ($order2_after ? "EXISTS" : "DELETED") . "\n";

if (!$order1_after && !$order2_after) {
   echo "✓ flushTable works correctly!\n";
} else {
   echo "✗ flushTable has issues\n";
}

// Test 4: Multiple tags on single cache entry
echo "\nTest 4: Testing multiple tags on single entry\n";
echo "----------------------------------------------\n";

$cache->tags(['table:users', 'table:accounts', 'type:admin'])->set('admin_user_1', ['id' => 1, 'role' => 'admin'], 3600);
echo "✓ Cached admin_user_1 dengan 3 tags: table:users, table:accounts, type:admin\n";

$admin_before = $cache->get('admin_user_1');
echo "  - Before flush: " . ($admin_before ? "EXISTS" : "DELETED") . "\n";

// Flush with one of the tags
$cache->flushTag('table:accounts');
echo "✓ Flushed dengan tag: table:accounts\n";

$admin_after = $cache->get('admin_user_1');
echo "  - After flush: " . ($admin_after ? "EXISTS" : "DELETED") . " (expected: DELETED)\n";

if (!$admin_after) {
   echo "✓ Multiple tags on single entry works correctly!\n";
} else {
   echo "✗ Multiple tags on single entry has issues\n";
}

echo "\n=== All Tests Completed ===\n";
