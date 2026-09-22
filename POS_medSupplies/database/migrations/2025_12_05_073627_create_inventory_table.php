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
        Schema::create('inventory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('item_lists')->onDelete('cascade');
            $table->string('transaction_type'); // stock_in, stock_out, adjustment, transfer, return, damage, expiry
            $table->decimal('quantity', 18, 2); // Positive for stock_in, negative for stock_out
            $table->decimal('quantity_before', 18, 2)->nullable();
            $table->decimal('quantity_after', 18, 2)->nullable();
            $table->decimal('unit_cost', 18, 4)->nullable();
            $table->decimal('total_cost', 18, 2)->nullable();
            $table->string('reference_type')->nullable(); // purchase_order, sale, adjustment, transfer, etc.
            $table->unsignedBigInteger('reference_id')->nullable(); // ID of the related record
            $table->string('reference_number')->nullable(); // PO number, receipt number, etc.
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('location')->nullable(); // Warehouse, shelf, bin location
            $table->string('batch_number')->nullable();
            $table->date('expiry_date')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('completed'); // pending, completed, cancelled
            $table->date('transaction_date')->nullable();
            $table->json('metadata')->nullable(); // Additional data
            $table->timestamps();

            // Indexes for better query performance
            $table->index('item_id');
            $table->index('transaction_type');
            $table->index('transaction_date');
            $table->index('reference_type');
            $table->index('reference_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory');
    }
};
