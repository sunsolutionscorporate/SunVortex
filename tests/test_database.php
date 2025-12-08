<?php

/**
 * tests/test_database.php
 *
 * Simple database tests for CLI runner `php sun test database`.
 * - Initializes Database singleton if available
 * - Attempts a connection check using Database::testConnection()
 * - Runs a lightweight query `SELECT 1` when possible
 *
 * This script is defensive: it catches exceptions and prints readable
 * messages so it can be safely run in CI or via the project's CLI.
 */

CLI::print("Running Database tests...");

try {
   if (!class_exists('Database')) {
      CLI::print("Database class not found. Make sure the framework is bootstrapped.");
      exit(2);
   }

   // Initialize Database manager (singleton)
   $db = Database::init();
   CLI::print("Database manager initialized.");

   // Do a simple connection test if method available
   if (method_exists($db, 'testConnection')) {
      $ok = $db->testConnection();
      cli::print("Connection test: " . ($ok ? "OK" : "FAILED"));
      if (!$ok) {
         cli::print("Warning: connection test failed. Check DB config (.env / DB_CONFIG)");
      }
   } else {
      cli::print("No testConnection() helper available; attempting a lightweight query instead.");
      $ok = false;
   }

   // If connection seems available, try a tiny query
   try {
      $row = $db->selectOne('SELECT 1 AS one');
      if ($row === null) {
         cli::print("Query SELECT 1: no result", CLI::YELLOW);
      } else {
         cli::print("Query SELECT 1: ", $row, CLI::GREEN);
         // cli::print(json_encode($row));
      }
   } catch (Throwable $e) {
      cli::print("Query failed: " . $e->getMessage());
   }
} catch (Throwable $e) {
   cli::print("ERROR during database tests: " . $e->getMessage(), CLI::RED);
}
