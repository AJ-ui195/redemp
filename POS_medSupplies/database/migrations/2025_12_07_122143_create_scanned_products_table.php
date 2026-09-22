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
        Schema::create('scanned_products', function (Blueprint $table) {
            $table->id();
            $table->string('barcode_value');
            $table->string('barcode_type')->nullable();
            $table->string('item_name')->nullable();
            $table->decimal('price', 18, 4)->nullable();
            $table->enum('price_type', ['retail', 'wholesale'])->nullable();
            $table->enum('unit', ['pcs', 'box', 'case'])->nullable();
            $table->timestamp('scanned_at')->useCurrent();
            $table->timestamps();
            
            $table->index('barcode_value');
            $table->index('scanned_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scanned_products');
    }
};
