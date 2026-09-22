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
        Schema::create('senior_pwd_discounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->nullable()->constrained('sales')->onDelete('cascade');
            $table->string('discount_type')->comment('senior or pwd');
            $table->string('customer_name')->nullable();
            $table->string('id_number')->nullable();
            $table->string('id_type')->nullable()->comment('OSCA or PWD');
            $table->string('issuing_lgu')->nullable();
            $table->longText('id_image')->nullable()->comment('Base64 encoded image or file path');
            $table->string('captured_by')->nullable()->comment('User who captured the ID');
            $table->timestamp('captured_at')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index('sale_id');
            $table->index('discount_type');
            $table->index('id_number');
            $table->index('captured_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('senior_pwd_discounts');
    }
};
