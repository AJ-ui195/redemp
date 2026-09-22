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
        if (Schema::hasColumn('barcodes', 'original_price') || Schema::hasColumn('barcodes', 'costing_price')) {
            return;
        }

        Schema::table('barcodes', function (Blueprint $table) {
            $table->decimal('costing_price', 18, 4)->nullable()->after('price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('barcodes', function (Blueprint $table) {
            $table->dropColumn('costing_price');
        });
    }
};
