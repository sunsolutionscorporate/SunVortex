<?php

/**
 * Enhanced Migration & Seeder CLI Handler
 * 
 * Commands:
 *   Migration:
 *     php sun migrate make:create <name>      - Create migration
 *     php sun migrate make:alter <name>       - Create alter migration
 *     php sun migrate run                     - Run pending migrations
 *     php sun migrate rollback [steps]        - Rollback migrations
 *     php sun migrate refresh                 - Reset and run all
 *     php sun migrate reset                   - Rollback all
 *     php sun migrate status                  - Show status
 *   
 *   Seeder:
 *     php sun migrate make:seed <name>        - Create seeder
 *     php sun migrate seed [seeder]           - Run seeder(s)
 *     php sun migrate seed:all                - Run all seeders
 *     php sun migrate seed:refresh            - Reset migrations & run seeders
 *   
 *   Other:
 *     php sun migrate fresh                   - Drop all & re-migrate
 *     php sun migrate list                    - List all migrations
 *     php sun migrate help                    - Show help
 */

class EnhancedMigrationCLI
{
   private $migrationManager;
   private $seederManager;

   public function __construct()
   {
      $this->migrationManager = new MigrationManager(Database::init());
      $this->seederManager = new SeederManager(Database::init());
   }

   /**
    * Run command
    */
   public static function run(...$args): void
   {
      $cli = new self();

      $command = $args[0] ?? 'help';
      $subcommand = $args[1] ?? null;
      $argument = $args[2] ?? null;

      switch ($command) {
         // ===== MIGRATION COMMANDS =====
         case 'make:create':
         case 'make:migration':
            if (!$subcommand) {
               CLI::print("Error: Migration name required\n");
               CLI::print("Usage: php sun migrate make:create create_users_table\n");
               exit(1);
            }
            $cli->makeMigration($subcommand, 'create');
            break;

         case 'make:alter':
            if (!$subcommand) {
               CLI::print("Error: Migration name required\n");
               CLI::print("Usage: php sun migrate make:alter alter_users_table\n");
               exit(1);
            }
            $cli->makeMigration($subcommand, 'alter');
            break;

         case 'run':
         case 'migrate':
            $cli->runMigrations();
            break;

         case 'rollback':
            $steps = (int)($subcommand ?? 1);
            $cli->rollback($steps);
            break;

         case 'refresh':
            $cli->refresh();
            break;

         case 'reset':
            $cli->reset();
            break;

         case 'fresh':
            $cli->fresh();
            break;

         case 'status':
            $cli->status();
            break;

         case 'list':
            $cli->listMigrations();
            break;

         // ===== SEEDER COMMANDS =====
         case 'make:seed':
            if (!$subcommand) {
               CLI::print("Error: Seeder name required\n");
               CLI::print("Usage: php sun migrate make:seed user_seeder\n");
               exit(1);
            }
            $cli->makeSeeder($subcommand);
            break;

         case 'seed':
            if ($subcommand === 'all') {
               $cli->seedAll();
            } elseif ($subcommand === 'refresh') {
               $cli->seedRefresh();
            } elseif ($subcommand) {
               $cli->seed($subcommand);
            } else {
               $cli->seedAll();
            }
            break;

         case 'help':
         default:
            $cli->showHelp();
            break;
      }
   }

   // ===== MIGRATION METHODS =====

   private function makeMigration(string $name, string $type): void
   {
      $path = MigrationManager::create($name);
      CLI::print("✓ Migration created: {$path}\n");
      CLI::print("  Edit the file to define your schema changes\n");
      CLI::print("  Run with: php sun migrate run\n");
   }

   private function runMigrations(): void
   {
      $pending = $this->migrationManager->getPendingMigrations();

      if (empty($pending)) {
         CLI::print("No pending migrations.\n");
         return;
      }

      CLI::print("Running " . count($pending) . " migration(s)...\n");

      $results = $this->migrationManager->run();

      foreach ($results as $result) {
         $status = $result['status'] === 'success' ? '✓' : '✗';
         CLI::print("{$status} {$result['migration']}: {$result['message']}\n");
      }
   }

   private function rollback(int $steps = 1): void
   {
      CLI::print("Rolling back {$steps} step(s)...\n");

      $results = $this->migrationManager->rollback($steps);

      if (isset($results['message'])) {
         echo $results['message'] . "\n";
         return;
      }

      foreach ($results as $result) {
         $status = $result['status'] === 'success' ? '✓' : '✗';
         CLI::print("{$status} {$result['migration']}: {$result['message']}\n");
      }
   }

   private function refresh(): void
   {
      CLI::print("Refreshing database...\n");
      CLI::print("Rolling back all migrations...\n");
      $this->migrationManager->reset();
      CLI::print("Running all migrations...\n");
      $this->migrationManager->run();
      CLI::print("✓ Database refreshed\n");
   }

   private function reset(): void
   {
      CLI::print("Resetting database (rolling back all migrations)...\n");
      $results = $this->migrationManager->reset();

      if (isset($results['message'])) {
         echo $results['message'] . "\n";
         return;
      }

      foreach ($results as $result) {
         $status = $result['status'] === 'success' ? '✓' : '✗';
         CLI::print("{$status} {$result['migration']}: {$result['message']}\n");
      }
   }

   private function fresh(): void
   {
      CLI::print("⚠️  Fresh will drop all tables and re-migrate!\n");

      // Confirm
      $handle = fopen("php://stdin", "r");
      CLI::print("Type 'yes' to confirm: ");
      $confirm = trim(fgets($handle));
      fclose($handle);

      if ($confirm !== 'yes') {
         CLI::print("Cancelled.\n");
         return;
      }

      CLI::print("Dropping all tables...\n");
      $this->migrationManager->reset();
      CLI::print("Running all migrations...\n");
      $this->migrationManager->run();
      CLI::print("✓ Database freshened\n");
   }

   private function status(): void
   {
      CLI::print("Migration Status\n", CLI::CYAN);
      echo str_repeat("=", 60) . "\n";

      $executed = $this->migrationManager->getExecutedMigrations();
      $pending = $this->migrationManager->getPendingMigrations();

      if (!empty($executed)) {
         CLI::print("\nExecuted Migrations:\n");
         foreach ($executed as $migration) {
            CLI::print("  ✓ {$migration['migration']} (batch {$migration['batch']}, {$migration['executed_at']})\n");
         }
      } else {
         CLI::print("\nNo migrations executed yet.\n");
      }

      if (!empty($pending)) {
         CLI::print("\nPending Migrations:\n");
         foreach ($pending as $migration) {
            CLI::print("  ◯ {$migration['name']}\n");
         }
      } else {
         CLI::print("\nNo pending migrations.\n");
      }

      CLI::print("\n");
   }

   private function listMigrations(): void
   {
      CLI::print("Available Migrations\n");
      echo str_repeat("=", 60) . "\n";

      $all = $this->migrationManager->getAllMigrations();
      $executed = $this->migrationManager->getExecutedMigrations();
      $executedNames = array_column($executed, 'migration');

      if (empty($all)) {
         CLI::print("No migrations found.\n");
         return;
      }

      foreach ($all as $migration) {
         $status = in_array($migration['name'], $executedNames) ? '✓' : '○';
         CLI::print("[{$status}] {$migration['name']}\n");
      }

      CLI::print("\n");
   }

   // ===== SEEDER METHODS =====

   private function makeSeeder(string $name): void
   {
      $path = SeederManager::create($name);
      CLI::print("✓ Seeder created: {$path}\n");
      CLI::print("  Edit the file to define your seed data\n");
      CLI::print("  Run with: php sun migrate seed {$name}\n");
   }

   private function seed(string $name): void
   {
      CLI::print("Seeding: {$name}\n");
      $result = $this->seederManager->runSeeder($name);

      $status = $result['status'] === 'success' ? '✓' : '✗';
      CLI::print("{$status} {$result['seeder']}: {$result['message']}\n");
   }

   private function seedAll(): void
   {
      $seeders = $this->seederManager->getAllSeeders();

      if (empty($seeders)) {
         CLI::print("No seeders found.\n");
         return;
      }

      CLI::print("Seeding " . count($seeders) . " seeder(s)...\n");

      $results = $this->seederManager->runAll();

      foreach ($results as $result) {
         $status = $result['status'] === 'success' ? '✓' : '✗';
         CLI::print("{$status} {$result['seeder']}: {$result['message']}\n");
      }
   }

   private function seedRefresh(): void
   {
      CLI::print("Seeder Refresh: Reset migrations and run seeders\n");

      CLI::print("Resetting migrations...\n");
      $this->migrationManager->reset();

      CLI::print("Running migrations...\n");
      $this->migrationManager->run();

      CLI::print("Running seeders...\n");
      $this->seedAll();

      CLI::print("✓ Seeder refresh complete\n");
   }

   // ===== HELP =====

   private function showHelp(): void
   {
      echo <<<HELP
Database Migration & Seeder CLI

MIGRATION COMMANDS:
  php sun migrate make:create <name>      Create new migration
  php sun migrate make:alter <name>       Create alter migration
  php sun migrate run                     Run pending migrations
  php sun migrate rollback [steps]        Rollback migrations (default: 1)
  php sun migrate refresh                 Reset and run all migrations
  php sun migrate reset                   Rollback all migrations
  php sun migrate fresh                   Drop all and re-migrate (confirmed)
  php sun migrate status                  Show migration status
  php sun migrate list                    List all migrations

SEEDER COMMANDS:
  php sun migrate make:seed <name>        Create new seeder
  php sun migrate seed [seeder]           Run specific seeder (all if not specified)
  php sun migrate seed:all                Run all seeders
  php sun migrate seed:refresh            Reset migrations and run seeders

EXAMPLES:
  php sun migrate make:create create_users_table
  php sun migrate run
  php sun migrate status
  php sun migrate rollback 2
  
  php sun migrate make:seed user_seeder
  php sun migrate seed user_seeder
  php sun migrate seed:all

HELP;
   }
}

// Define alias only if MigrationCLI not already defined to avoid collision
if (!class_exists('MigrationCLI')) {
   class MigrationCLI extends EnhancedMigrationCLI {}
}
