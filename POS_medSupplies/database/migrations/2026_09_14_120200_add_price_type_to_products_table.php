<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('products') || Schema::hasColumn('products', 'price_type')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            $table->string('price_type')->default('retail')->after('selling_price');
            $table->index(['price_type', 'is_active']);
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('products') || ! Schema::hasColumn('products', 'price_type')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['price_type', 'is_active']);
            $table->dropColumn('price_type');
        });
    }
};
