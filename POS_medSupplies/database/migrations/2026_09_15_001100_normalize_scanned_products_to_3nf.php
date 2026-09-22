<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 3NF scanned_products:
     * - Product attributes (name, price, unit, stock, etc.) live only in products.
     * - This table stores scan-event facts that depend on the scan row only.
     */
    public function up(): void
    {
        Schema::dropIfExists('scanned_products');

        Schema::create('scanned_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('barcode_type')->nullable();
            $table->timestamp('scanned_at')->useCurrent();
            $table->timestamps();

            $table->index('scanned_at');
            $table->index(['product_id', 'scanned_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scanned_products');
    }
};
