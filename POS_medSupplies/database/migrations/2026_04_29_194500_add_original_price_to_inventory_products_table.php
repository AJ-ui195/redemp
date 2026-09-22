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
        if (!Schema::hasColumn('inventory_products', 'original_price')) {
            Schema::table('inventory_products', function (Blueprint $table) {
                $table->decimal('original_price', 18, 4)->nullable()->after('mfg_date');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('inventory_products', 'original_price')) {
            Schema::table('inventory_products', function (Blueprint $table) {
                $table->dropColumn('original_price');
            });
        }
    }
};

