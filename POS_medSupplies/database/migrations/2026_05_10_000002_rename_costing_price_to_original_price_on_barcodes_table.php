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
        if (Schema::hasColumn('barcodes', 'costing_price')) {
            Schema::table('barcodes', function (Blueprint $table) {
                $table->renameColumn('costing_price', 'original_price');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('barcodes', 'original_price')) {
            Schema::table('barcodes', function (Blueprint $table) {
                $table->renameColumn('original_price', 'costing_price');
            });
        }
    }
};
