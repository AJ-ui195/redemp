<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Drop redundant scanned_barcode — product identity is product_id only.
     * Barcode values are read from products via the product relationship.
     */
    public function up(): void
    {
        if (! Schema::hasTable('scanned_products')) {
            return;
        }

        Schema::table('scanned_products', function (Blueprint $table) {
            if (Schema::hasColumn('scanned_products', 'scanned_barcode')) {
                try {
                    $table->dropIndex(['scanned_barcode']);
                } catch (\Throwable $e) {
                    // Index name may differ across MySQL versions; column drop still proceeds.
                }
                $table->dropColumn('scanned_barcode');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('scanned_products')) {
            return;
        }

        Schema::table('scanned_products', function (Blueprint $table) {
            if (! Schema::hasColumn('scanned_products', 'scanned_barcode')) {
                $table->string('scanned_barcode')->nullable()->after('product_id');
                $table->index('scanned_barcode');
            }
        });
    }
};
