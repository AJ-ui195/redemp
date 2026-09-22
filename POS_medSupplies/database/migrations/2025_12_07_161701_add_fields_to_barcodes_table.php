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
        Schema::table('barcodes', function (Blueprint $table) {
            $table->enum('active_status', ['Active', 'Inactive'])->default('Active')->after('barcode_type');
            $table->text('description')->nullable()->after('active_status');
            $table->decimal('quantity_on_hand', 18, 4)->default(0)->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('barcodes', function (Blueprint $table) {
            $table->dropColumn(['active_status', 'description', 'quantity_on_hand']);
        });
    }
};
