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
        if (!Schema::hasColumn('item_lists', 'mfg_date')) {
            Schema::table('item_lists', function (Blueprint $table) {
                $table->date('mfg_date')->nullable()->after('expiry_date');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('item_lists', 'mfg_date')) {
            Schema::table('item_lists', function (Blueprint $table) {
                $table->dropColumn('mfg_date');
            });
        }
    }
};
