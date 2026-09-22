<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('products')) {
            Schema::create('products', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('category'); // Medical Supplies, Medicines, Medical Equipment
                $table->text('description')->nullable();
                $table->decimal('price', 10, 2);
                $table->integer('stock_quantity')->default(0);
                $table->integer('min_stock_level')->default(10);
                $table->string('unit')->default('piece'); // piece, box, bottle, etc.
                $table->string('sku')->unique()->nullable(); // Stock Keeping Unit
                $table->string('brand')->nullable();
                $table->string('supplier')->nullable();
                $table->date('expiry_date')->nullable();
                $table->boolean('requires_prescription')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Don't drop the table in down() to avoid data loss
        // If you need to drop it, do it manually
    }
};
