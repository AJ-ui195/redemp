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
        Schema::table('item_lists', function (Blueprint $table) {
            $table->string('brand')->nullable()->after('item');
            $table->string('lot_number')->nullable()->after('mpn');
            $table->date('expiry_date')->nullable()->after('lot_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('item_lists', function (Blueprint $table) {
            $table->dropColumn(['brand', 'lot_number', 'expiry_date']);
        });
    }
};
