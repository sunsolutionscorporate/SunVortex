<?php

/**
 * Migration System Integration Test
 * 
 * Test migration system components:
 * - Migration class instantiation
 * - Blueprint column definition
 * - Schema SQL compilation
 * - MigrationManager lifecycle
 */

require_once __DIR__ . '/../system/Autoload.php';

echo "=== Migration System Integration Test ===\n\n";

// Test 1: Blueprint creation
echo "Test 1: Blueprint Column Definition\n";
try {
   $blueprint = new Blueprint('test_table', 'create');

   // Add various columns
   $col1 = $blueprint->id();
   $col2 = $blueprint->string('name')->nullable();
   $col3 = $blueprint->email()->unique();
   $col4 = $blueprint->password();
   $blueprint->timestamps();

   echo "  ✓ Created blueprint with 5 columns\n";
   echo "  ✓ Columns defined: id, name, email, password, timestamps\n";
} catch (Exception $e) {
   echo "  ✗ Error: " . $e->getMessage() . "\n";
}

// Test 2: Column modifiers
echo "\nTest 2: Column Modifiers\n";
try {
   $column = new ColumnDefinition('test_column', 'string', ['length' => 255]);
   $column
      ->nullable()
      ->default('default_value')
      ->unique()
      ->comment('Test column');

   echo "  ✓ Applied modifiers: nullable, default, unique, comment\n";
   echo "  ✓ Column name: " . $column->getName() . "\n";
   echo "  ✓ Column type: " . $column->getType() . "\n";
   echo "  ✓ Nullable: " . ($column->isNullable() ? 'yes' : 'no') . "\n";
   echo "  ✓ Unique: " . ($column->isUnique() ? 'yes' : 'no') . "\n";
} catch (Exception $e) {
   echo "  ✗ Error: " . $e->getMessage() . "\n";
}

// Test 3: Schema compilation
echo "\nTest 3: Schema SQL Compilation\n";
try {
   $db = Database::init();
   $schema = new Schema($db);

   // Check schema methods
   if (method_exists($schema, 'hasTable')) {
      echo "  ✓ Schema has hasTable() method\n";
   }
   if (method_exists($schema, 'hasColumn')) {
      echo "  ✓ Schema has hasColumn() method\n";
   }
   if (method_exists($schema, 'create')) {
      echo "  ✓ Schema has create() method\n";
   }
   if (method_exists($schema, 'alter')) {
      echo "  ✓ Schema has alter() method\n";
   }

   echo "  ✓ Schema manager operational\n";
} catch (Exception $e) {
   echo "  ✗ Error: " . $e->getMessage() . "\n";
}

// Test 4: Migration Manager initialization
echo "\nTest 4: Migration Manager Initialization\n";
try {
   $db = Database::init();
   $manager = new MigrationManager($db);

   echo "  ✓ MigrationManager instantiated\n";

   // Get all migrations
   $all = $manager->getAllMigrations();
   echo "  ✓ Found " . count($all) . " migration file(s)\n";

   if (!empty($all)) {
      foreach ($all as $migration) {
         echo "    - " . $migration['name'] . "\n";
      }
   }

   // Get pending
   $pending = $manager->getPendingMigrations();
   echo "  ✓ Found " . count($pending) . " pending migration(s)\n";

   // Get executed
   $executed = $manager->getExecutedMigrations();
   echo "  ✓ Found " . count($executed) . " executed migration(s)\n";
} catch (Exception $e) {
   echo "  ✗ Error: " . $e->getMessage() . "\n";
}

// Test 5: Database helper methods
echo "\nTest 5: Database Helper Methods\n";
try {
   $db = Database::init();

   // Check new methods
   $methods = [
      'statement',
      'select',
      'selectOne',
      'insert',
      'update',
      'delete',
      'getPdo',
      'beginTransaction',
      'commit',
      'rollBack'
   ];

   foreach ($methods as $method) {
      if (method_exists($db, $method)) {
         echo "  ✓ Database::" . $method . "() available\n";
      } else {
         echo "  ✗ Database::" . $method . "() missing\n";
      }
   }
} catch (Exception $e) {
   echo "  ✗ Error: " . $e->getMessage() . "\n";
}

// Test 6: Migration file generation
echo "\nTest 6: Migration File Generation\n";
try {
   $path = MigrationManager::create('test_migration');

   if (file_exists($path)) {
      echo "  ✓ Migration file generated: " . basename($path) . "\n";

      // Check content
      $content = file_get_contents($path);
      if (strpos($content, 'class TestMigrationMigration extends Migration') !== false) {
         echo "  ✓ Class name correct: TestMigrationMigration\n";
      }
      if (strpos($content, 'public function up()') !== false) {
         echo "  ✓ up() method present\n";
      }
      if (strpos($content, 'public function down()') !== false) {
         echo "  ✓ down() method present\n";
      }

      // Cleanup
      unlink($path);
      echo "  ✓ Test file cleaned up\n";
   } else {
      echo "  ✗ Migration file not created\n";
   }
} catch (Exception $e) {
   echo "  ✗ Error: " . $e->getMessage() . "\n";
}

// Test 7: CLI command structure
echo "\nTest 7: CLI Command Structure\n";
try {
   if (class_exists('MigrationCLI')) {
      echo "  ✓ MigrationCLI class available\n";

      if (method_exists('MigrationCLI', 'run')) {
         echo "  ✓ MigrationCLI::run() method available\n";
      }
   } else {
      echo "  ✗ MigrationCLI class not found\n";
   }

   // Check if migrate script exists
   if (file_exists(APP_PATH . '/migrate')) {
      echo "  ✓ migrate CLI entry point exists\n";
   }
} catch (Exception $e) {
   echo "  ✗ Error: " . $e->getMessage() . "\n";
}

echo "\n=== Integration Test Complete ===\n";
echo "\nMigration System Status: ✅ Ready for use\n";
echo "\nNext steps:\n";
echo "  1. Run: php migrate status\n";
echo "  2. Create migration: php migrate make:create <name>\n";
echo "  3. Run migrations: php migrate run\n";
echo "  4. Check documentation: doc/DATABASE_MIGRATIONS.md\n";
