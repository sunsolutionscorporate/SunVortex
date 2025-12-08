<?php

class AlterProductsAddDescriptionMigration extends Migration
{
    /**
     * Run migration
     */
    public function up()
    {
        // Alter existing products table: add description column
        $this->table('products', function (Blueprint $table) {
            $table->string('description', 1000)->nullable();
        });
    }

    /**
     * Rollback migration
     */
    public function down()
    {
        // Remove the description column on rollback (use raw SQL to ensure compatibility)
        $this->db->statement("ALTER TABLE `products` DROP COLUMN `description`");
    }
};
