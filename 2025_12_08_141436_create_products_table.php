<?php

class CreateProductsTableMigration extends Migration
{
    /**
     * Run migration
     */
    public function up()
    {
        $this->create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->integer('stock')->default(0);
            $table->decimal('price', 10, 2)->default(0.00);
            $table->timestamps();
        });
    }

    /**
     * Rollback migration
     */
    public function down()
    {
        $this->dropIfExists('products');
    }
}
