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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->onDelete('cascade');
            $table->string('payment_method'); // cash | e_wallet | check
            $table->string('reference_number')->nullable(); // transaction ID or check number
            $table->string('provider')->nullable(); // GCash, Maya, Bank name
            $table->decimal('amount', 12, 2);
            $table->string('status')->default('pending'); // paid | pending | failed
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('cleared_at')->nullable();
            $table->timestamps();
            
            // Indexes for better query performance
            $table->index('sale_id');
            $table->index('payment_method');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
