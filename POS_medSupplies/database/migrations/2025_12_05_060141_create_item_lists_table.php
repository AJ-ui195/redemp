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
        Schema::create('item_lists', function (Blueprint $table) {
            $table->id();
            $table->string('active_status', 20)->nullable();
            $table->string('type', 50)->nullable();
            $table->string('item', 255)->nullable();
            $table->string('description', 255)->nullable();
            $table->string('sales_tax_code', 50)->nullable();
            $table->string('account', 100)->nullable();
            $table->string('cogs_account', 100)->nullable();
            $table->string('asset_account', 100)->nullable();
            $table->decimal('accumulated_depr', 18, 2)->nullable();
            $table->string('purchase_description', 255)->nullable();
            $table->decimal('quantity_on_hand', 18, 2)->nullable();
            $table->string('unit_of_measure', 50)->nullable()->comment('u/m');
            $table->decimal('cost', 18, 4)->nullable();
            $table->string('preferred_vendor', 255)->nullable();
            $table->string('tax_agency', 255)->nullable();
            $table->decimal('price', 18, 4)->nullable();
            $table->decimal('reorder_pt_min', 18, 2)->nullable();
            $table->string('mpn', 100)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_lists');
    }
};
