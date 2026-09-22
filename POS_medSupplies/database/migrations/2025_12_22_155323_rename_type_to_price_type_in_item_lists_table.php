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
        // Rename type column to price_type and change to enum
        Schema::table('item_lists', function (Blueprint $table) {
            // First, drop the old type column
            $table->dropColumn('type');
        });
        
        // Add price_type as enum
        Schema::table('item_lists', function (Blueprint $table) {
            $table->enum('price_type', ['retail', 'wholesale'])->nullable()->after('active_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('item_lists', function (Blueprint $table) {
            // Drop price_type
            $table->dropColumn('price_type');
        });
        
        // Re-add type as string
        Schema::table('item_lists', function (Blueprint $table) {
            $table->string('type', 50)->nullable()->after('active_status');
        });
    }
};
