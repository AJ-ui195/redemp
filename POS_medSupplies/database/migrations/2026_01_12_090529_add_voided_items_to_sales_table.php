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
        if (Schema::hasColumn('sales', 'voided_items')) {
            return;
        }

        Schema::table('sales', function (Blueprint $table) {
            $table->json('voided_items')->nullable()->after('items'); // Array of item indices that have been voided
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('voided_items');
        });
    }
};
