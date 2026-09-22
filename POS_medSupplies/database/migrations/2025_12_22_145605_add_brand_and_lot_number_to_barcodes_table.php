<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('barcodes', function (Blueprint $table) {
            // Add brand column after item_name
            $table->string('brand')->nullable()->after('item_name');
            
            // Add lot_number column after unit
            $table->string('lot_number')->nullable()->after('unit');
        });

        // Change unit column from enum to string to support all unit types
        // This is done using raw SQL because Laravel doesn't support changing enum columns directly
        DB::statement("ALTER TABLE barcodes MODIFY COLUMN unit VARCHAR(50) DEFAULT 'pcs'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Change unit column back to enum
        DB::statement("ALTER TABLE barcodes MODIFY COLUMN unit ENUM('pcs', 'box', 'case') DEFAULT 'pcs'");

        Schema::table('barcodes', function (Blueprint $table) {
            $table->dropColumn(['brand', 'lot_number']);
        });
    }
};
