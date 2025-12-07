<?php

/**
 * Migration CLI Handler
 * 
 * Usage dari command line:
 *   php migrate make:create create_users_table
 *   php migrate make:alter alter_users_table
 *   php migrate run
 *   php migrate rollback
 *   php migrate refresh
 *   php migrate reset
 */

class MigrationCLI
{
   private $manager;
   private $seederManager;

   public function __construct()
   {
      $this->manager = new MigrationManager(Database::init());
      // initialize seeder manager for new seeder commands
      $this->seederManager = new SeederManager(Database::init());
   }

   /**
    * Run command
    */
   public static function run(...$arg): void
   {
      $cli = new self();
      // echo print_r($arg, true);
      // exit;

      $command = $arg[0] ?? 'help';
      $subcommand = $arg[1] ?? null;
      $argument = $arg[2] ?? null;

      switch ($command) {
         case 'make:create':
         case 'make:migration':
            if (!$subcommand) {
               CLI::print("Error: Migration name required\n");
               CLI::print("Usage: php migrate make:create create_users_table\n");
               exit(1);
            }
            $cli->make($subcommand, 'create');
            break;

         case 'make:alter':
            if (!$subcommand) {
               CLI::print("Error: Migration name required\n");
               CLI::print("Usage: php migrate make:alter alter_users_table\n");
               exit(1);
            }
            $cli->make($subcommand, 'alter');
            break;

         case 'run':
         case 'migrate':
            $cli->runMigrations();
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

         case 'list':
            $cli->listMigrations();
            break;

         case 'fresh':
            $cli->fresh();
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

         case 'status':
            $cli->status();
            break;

         case 'help':
         default:
            $cli->showHelp();
            break;
      }
   }

   /**
    * Create new migration file
    */
   private function make(string $name, string $type): void
   {
      $path = MigrationManager::create($name);
      CLI::print("✓ Migration created: {$path}\n");
      CLI::print("  Run migrations with: php migrate run\n");
   }

   /**
    * Create new seeder file
    */
   private function makeSeeder(string $name): void
   {
      $path = SeederManager::create($name);
      CLI::print("✓ Seeder created: {$path}\n");
      CLI::print("  Edit the file to define your seed data\n");
      CLI::print("  Run with: php sun migrate seed {$name}\n");
   }

   /**
    * Run pending migrations
    */
   private function runMigrations(): void
   {
      $pending = $this->manager->getPendingMigrations();

      if (empty($pending)) {
         CLI::print("No pending migrations.\n");
         return;
      }

      CLI::print("Running " . count($pending) . " migration(s)...\n");

      $results = $this->manager->run();

      foreach ($results as $result) {
         $status = $result['status'] === 'success' ? '✓' : '✗';
         CLI::print("{$status} {$result['migration']}: {$result['message']}\n");
      }
   }

   /**
    * Rollback migrations
    */
   private function rollback(int $steps = 1): void
   {
      CLI::print("Rolling back {$steps} step(s)...\n");

      $results = $this->manager->rollback($steps);

      if (isset($results['message'])) {
         echo $results['message'] . "\n";
         return;
      }

      foreach ($results as $result) {
         $status = $result['status'] === 'success' ? '✓' : '✗';
         CLI::print("{$status} {$result['migration']}: {$result['message']}\n");
      }
   }

   /**
    * Refresh - rollback dan run semua
    */
   private function refresh(): void
   {
      CLI::print("Refreshing database...\n");
      CLI::print("Rolling back all migrations...\n");
      $this->manager->reset();
      CLI::print("Running all migrations...\n");
      $this->manager->run();
      CLI::print("✓ Database refreshed\n");
   }

   private function fresh(): void
   {
      CLI::print("⚠️  Fresh will drop all tables and re-migrate!\n");
      $handle = fopen("php://stdin", "r");
      CLI::print("Type 'yes' to confirm: ");
      $confirm = trim(fgets($handle));
      fclose($handle);

      if ($confirm !== 'yes') {
         CLI::print("Cancelled.\n");
         return;
      }

      CLI::print("Dropping all tables...\n");
      $this->manager->reset();
      CLI::print("Running all migrations...\n");
      $this->manager->run();
      CLI::print("✓ Database freshened\n");
   }

   /**
    * Reset - rollback semua
    */
   private function reset(): void
   {
      CLI::print("Resetting database (rolling back all migrations)...\n");
      $results = $this->manager->reset();

      if (isset($results['message'])) {
         echo $results['message'] . "\n";
         return;
      }

      foreach ($results as $result) {
         $status = $result['status'] === 'success' ? '✓' : '✗';
         CLI::print("{$status} {$result['migration']}: {$result['message']}\n");
      }
   }

   /**
    * Show migration status
    */
   private function status(): void
   {
      CLI::print("Migration Status\n", CLI::MAGENTA);
      echo str_repeat("=", 60) . "\n";

      $executed = $this->manager->getExecutedMigrations();
      $pending = $this->manager->getPendingMigrations();

      if (!empty($executed)) {
         CLI::print("Executed Migrations:");
         foreach ($executed as $migration) {
            CLI::print("  ✓ {$migration['migration']} (batch {$migration['batch']}, {$migration['executed_at']})");
         }
      } else {
         CLI::print("No migrations executed yet.", CLI::CYAN);
      }

      if (!empty($pending)) {
         CLI::print("Pending Migrations:", CLI::CYAN);
         foreach ($pending as $migration) {
            CLI::print("  ◯ {$migration['name']}\n");
         }
      } else {
         CLI::print("No pending migrations.", CLI::CYAN);
      }

      CLI::print("\n");
   }

   /**
    * List migrations
    */
   private function listMigrations(): void
   {
      CLI::print("Available Migrations\n");
      echo str_repeat("=", 60) . "\n";

      $all = $this->manager->getAllMigrations();
      $executed = $this->manager->getExecutedMigrations();
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

   /**
    * Run a specific seeder
    */
   private function seed(string $name): void
   {
      CLI::print("Seeding: {$name}\n");
      $result = $this->seederManager->runSeeder($name);
      $status = $result['status'] === 'success' ? '✓' : '✗';
      CLI::print("{$status} {$result['seeder']}: {$result['message']}\n");
   }

   /**
    * Run all seeders
    */
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

   /**
    * Reset migrations and run seeders
    */
   private function seedRefresh(): void
   {
      CLI::print("Seeder Refresh: Reset migrations and run seeders\n");
      CLI::print("Resetting migrations...\n");
      $this->manager->reset();
      CLI::print("Running migrations...\n");
      $this->manager->run();
      CLI::print("Running seeders...\n");
      $this->seedAll();
      CLI::print("✓ Seeder refresh complete\n");
   }

   /**
    * Show help
    */
   private function showHelp(): void
   {
      echo <<<HELP
Database Migration CLI

Usage:
   php sun migrate <command> [arguments]

Migration Commands:
   make:create <name>      Create new migration file
   make:migration <name>   Alias for make:create
   run                     Run pending migrations
   migrate                 Alias for run
   rollback [steps]        Rollback migrations (default: 1 step)
   refresh                 Rollback all and run again
   reset                   Rollback all migrations
   fresh                   Drop all and re-migrate (requires confirmation)
   status                  Show migration status
   list                    List all migrations

Seeder Commands:
   make:seed <name>        Create new seeder file
   seed [name]             Run seeder (run all if no name supplied)
   seed:all                Run all seeders
   seed:refresh            Reset migrations and run seeders

Examples:
   php sun migrate make:create create_users_table
   php sun migrate run
   php sun migrate rollback
   php sun migrate rollback 3
   php sun migrate refresh
   php sun migrate status

   php sun migrate make:seed testing_seeder
   php sun migrate seed testing_seeder
   php sun migrate seed:all

HELP;
   }
}

// Run CLI if executed directly
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['argv'][0] ?? '')) {
   MigrationCLI::run();
}
