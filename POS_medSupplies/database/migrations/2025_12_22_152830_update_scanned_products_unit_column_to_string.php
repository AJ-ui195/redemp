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
        // Change unit column from enum to string to support all unit types
        Schema::table('scanned_products', function (Blueprint $table) {
            // Drop the enum column
            $table->dropColumn('unit');
        });
        
        // Re-add as string column
        Schema::table('scanned_products', function (Blueprint $table) {
            $table->string('unit', 50)->nullable()->after('price_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scanned_products', function (Blueprint $table) {
            $table->dropColumn('unit');
        });
        
        Schema::table('scanned_products', function (Blueprint $table) {
            $table->enum('unit', ['pcs', 'box', 'case'])->nullable()->after('price_type');
        });
    }
};
