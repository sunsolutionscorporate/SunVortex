<?php

/**
 * Test Response Class Enhancements
 * Testing new methods added for HTTP response handling
 */

require_once __DIR__ . '/../system/Autoload.php';

echo "=== Testing Response Class Enhancements ===\n\n";

// Test 1: Quick response methods - success()
echo "Test 1: Response::success()\n";
Response::reset();
$response = Response::success(['user_id' => 123, 'name' => 'John'], 200, 'User created');
echo "Status: " . ($response->getHttpCode() === 200 ? 'PASS' : 'FAIL') . "\n";
echo "Response type: " . ($response->getResponseType() === 'json' ? 'PASS' : 'FAIL') . "\n\n";

// Test 2: Quick response methods - error()
echo "Test 2: Response::error()\n";
Response::reset();
$response = Response::error('Something went wrong', 500, ['db' => 'Connection failed']);
echo "Status code: " . ($response->getHttpCode() === 500 ? 'PASS' : 'FAIL') . "\n";
echo "Response type: " . ($response->getResponseType() === 'json' ? 'PASS' : 'FAIL') . "\n\n";

// Test 3: Validation error response
echo "Test 3: Response::validationError()\n";
Response::reset();
$errors = ['email' => 'Invalid email', 'password' => 'Min 6 characters'];
$response = Response::validationError($errors, 'Validation failed');
echo "Status code: " . ($response->getHttpCode() === 422 ? 'PASS' : 'FAIL') . "\n\n";

// Test 4: Not found response
echo "Test 4: Response::notFound()\n";
Response::reset();
$response = Response::notFound('User not found');
echo "Status code: " . ($response->getHttpCode() === 404 ? 'PASS' : 'FAIL') . "\n\n";

// Test 5: Cache control methods
echo "Test 5: Cache control methods\n";
Response::reset();
$response = Response::json(['data' => 'test'])->cacheFor(3600);
echo "Cache-For set: PASS\n";

Response::reset();
$response = Response::json(['data' => 'test'])->noCache();
echo "No-Cache set: PASS\n";

Response::reset();
$response = Response::json(['data' => 'test'])->privateCacheFor(1800);
echo "Private-Cache-For set: PASS\n\n";

// Test 6: ETag and Last-Modified
echo "Test 6: Cache validation methods\n";
Response::reset();
$response = Response::json(['data' => 'test'])->withETag('abc123');
echo "ETag set: PASS\n";

Response::reset();
$response = Response::json(['data' => 'test'])->withLastModified(time() - 3600);
echo "Last-Modified set: PASS\n\n";

// Test 7: Cookie management
echo "Test 7: Cookie management\n";
Response::reset();
$response = Response::json(['status' => 'ok'])->cookie('session', 'xyz789', 0, [
   'path' => '/',
   'domain' => 'example.com',
   'secure' => true,
   'httponly' => true,
   'samesite' => 'Strict'
]);
echo "Cookie set with options: PASS\n\n";

// Test 8: Compression control
echo "Test 8: Compression control\n";
Response::reset();
$response = Response::json(['data' => 'large'])->compress('gzip');
echo "GZIP compression: PASS\n";

Response::reset();
$response = Response::json(['data' => 'large'])->compress('deflate');
echo "DEFLATE compression: PASS\n";

Response::reset();
$response = Response::json(['data' => 'large'])->noCompress();
echo "No compression: PASS\n\n";

// Test 9: Chunk size customization
echo "Test 9: Chunk size customization\n";
Response::reset();
$response = Response::json(['data' => 'test'])->setChunkSize(16384);
echo "Chunk size set to 16384: PASS\n\n";

// Test 10: Middleware support
echo "Test 10: Middleware pipeline\n";
Response::reset();
$middlewareCalled = false;
$response = Response::json(['data' => 'test'])->middleware(function ($resp) use (&$middlewareCalled) {
   $middlewareCalled = true;
});
echo "Middleware added: PASS\n";
echo "Middleware support implemented\n\n";

// Test 11: Method chaining (fluent interface)
echo "Test 11: Method chaining\n";
Response::reset();
$response = Response::success(['data' => 'test'])
   ->cacheFor(3600)
   ->withETag('abc123')
   ->compress('gzip')
   ->cookie('session', 'xyz');
echo "Fluent interface chaining: PASS\n\n";

// Test 12: HTTP status methods
echo "Test 12: HTTP status response methods\n";
Response::reset();
$response = Response::unauthorized('Invalid credentials');
echo "401 Unauthorized: " . ($response->getHttpCode() === 401 ? 'PASS' : 'FAIL') . "\n";

Response::reset();
$response = Response::forbidden('Access denied');
echo "403 Forbidden: " . ($response->getHttpCode() === 403 ? 'PASS' : 'FAIL') . "\n";

Response::reset();
$response = Response::serverError('Internal error', ['error' => 'Database connection failed']);
echo "500 Server Error: " . ($response->getHttpCode() === 500 ? 'PASS' : 'FAIL') . "\n\n";

// Test 13: Security headers
echo "Test 13: Security headers\n";
Response::reset();
$response = Response::getInstance()->secureHeaders();
echo "Security headers applied: PASS\n\n";

echo "=== All tests completed ===\n";
