<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('products')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'lot_number')) {
                $table->string('lot_number')->nullable()->after('quantity');
            }

            if (! Schema::hasColumn('products', 'mfg_date')) {
                $table->date('mfg_date')->nullable()->after('lot_number');
            }

            if (! Schema::hasColumn('products', 'expiration_date')) {
                $table->date('expiration_date')->nullable()->after('mfg_date');
                $table->index('expiration_date');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('products')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'expiration_date')) {
                $table->dropIndex(['expiration_date']);
                $table->dropColumn('expiration_date');
            }

            if (Schema::hasColumn('products', 'mfg_date')) {
                $table->dropColumn('mfg_date');
            }

            if (Schema::hasColumn('products', 'lot_number')) {
                $table->dropColumn('lot_number');
            }
        });
    }
};
