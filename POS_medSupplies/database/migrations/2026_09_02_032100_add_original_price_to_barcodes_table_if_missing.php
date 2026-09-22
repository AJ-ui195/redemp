<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('barcodes')) {
            return;
        }

        if (Schema::hasColumn('barcodes', 'original_price')) {
            return;
        }

        if (Schema::hasColumn('barcodes', 'costing_price')) {
            Schema::table('barcodes', function (Blueprint $table) {
                $table->renameColumn('costing_price', 'original_price');
            });

            return;
        }

        Schema::table('barcodes', function (Blueprint $table) {
            $table->decimal('original_price', 18, 4)->nullable();
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('barcodes') && Schema::hasColumn('barcodes', 'original_price')) {
            Schema::table('barcodes', function (Blueprint $table) {
                $table->dropColumn('original_price');
            });
        }
    }
};
