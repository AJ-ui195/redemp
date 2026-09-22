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
        Schema::create('inventory_products', function (Blueprint $table) {
            $table->id();
            $table->enum('active_status', ['Active', 'Inactive'])->default('Active');
            $table->string('barcode_value')->unique()->index();
            $table->string('item_name');
            $table->text('description')->nullable();
            $table->decimal('price', 18, 4)->nullable();
            $table->enum('price_type', ['retail', 'wholesale'])->nullable();
            $table->string('unit', 50)->nullable();
            $table->decimal('quantity_on_hand', 18, 4)->default(0);
            $table->date('expiration_date')->nullable();
            $table->timestamps();
            
            // Indexes for better query performance
            $table->index('item_name');
            $table->index('active_status');
            $table->index('expiration_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_products');
    }
};
