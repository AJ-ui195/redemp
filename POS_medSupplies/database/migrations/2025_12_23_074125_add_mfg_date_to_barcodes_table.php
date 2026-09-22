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
        if (!Schema::hasColumn('barcodes', 'mfg_date')) {
            Schema::table('barcodes', function (Blueprint $table) {
                $table->date('mfg_date')->nullable()->after('expiration_date');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('barcodes', 'mfg_date')) {
            Schema::table('barcodes', function (Blueprint $table) {
                $table->dropColumn('mfg_date');
            });
        }
    }
};
