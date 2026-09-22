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
        Schema::table('sales', function (Blueprint $table) {
            $table->string('discount_type')->nullable()->after('discount')->comment('senior or pwd');
            $table->string('id_number')->nullable()->after('discount_type');
            $table->string('id_type')->nullable()->after('id_number')->comment('OSCA or PWD');
            $table->string('issuing_lgu')->nullable()->after('id_type');
            $table->string('customer_name')->nullable()->after('issuing_lgu');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn([
                'discount_type',
                'id_number',
                'id_type',
                'issuing_lgu',
                'customer_name'
            ]);
        });
    }
};
