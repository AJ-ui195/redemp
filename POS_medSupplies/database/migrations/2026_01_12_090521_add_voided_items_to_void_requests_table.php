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
        if (Schema::hasColumn('void_requests', 'voided_items')) {
            return;
        }

        Schema::table('void_requests', function (Blueprint $table) {
            $table->json('voided_items')->nullable()->after('reason'); // Array of item indices to void
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('void_requests', function (Blueprint $table) {
            $table->dropColumn('voided_items');
        });
    }
};
