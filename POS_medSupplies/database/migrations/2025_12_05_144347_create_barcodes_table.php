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
        Schema::create('barcodes', function (Blueprint $table) {
            $table->id();
            $table->string('barcode_value')->unique();
            $table->string('item_name');
            $table->decimal('price', 18, 4);
            $table->enum('price_type', ['retail', 'wholesale'])->default('retail');
            $table->enum('unit', ['pcs', 'box', 'case'])->default('pcs');
            $table->string('barcode_type')->default('CODE128');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('barcodes');
    }
};
