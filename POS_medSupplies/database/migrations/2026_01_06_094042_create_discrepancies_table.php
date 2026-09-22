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
        if (Schema::hasTable('discrepancies')) {
            return;
        }

        Schema::create('discrepancies', function (Blueprint $table) {
            $table->id();
            $table->string('item_name'); // Item name (can be from item_lists or inventory_products)
            $table->foreignId('item_id')->nullable()->constrained('item_lists')->onDelete('set null'); // Reference to item_lists if available
            $table->foreignId('inventory_product_id')->nullable()->constrained('inventory_products')->onDelete('set null'); // Reference to inventory_products if available
            $table->decimal('quantity', 18, 2); // Quantity of broken items
            $table->text('description'); // Description of the damage/issue
            $table->string('reason')->nullable(); // Reason for discrepancy
            $table->foreignId('requested_by')->constrained('users')->onDelete('cascade'); // Cashier who requested
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null'); // Admin who approved/rejected
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->text('rejection_reason')->nullable(); // Reason for rejection (if rejected)
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();
            
            $table->index('status');
            $table->index('item_id');
            $table->index('inventory_product_id');
            $table->index('requested_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discrepancies');
    }
};
