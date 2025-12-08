<?php
require_once __DIR__ . '/../system/Bootstrap.php';
try {
   $db = Database::init();
   $rows = $db->select('SELECT migration, batch, executed_at FROM migrations ORDER BY batch, migration');
   echo "Executed migrations:\n";
   foreach ($rows as $r) {
      echo "- {$r['migration']} (batch: {$r['batch']}) at {$r['executed_at']}\n";
   }
} catch (Throwable $e) {
   echo "ERROR: " . $e->getMessage() . "\n";
}
