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
        if (Schema::hasTable('wholesale_to_retail_requests')) {
            return;
        }

        Schema::create('wholesale_to_retail_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('retail_item_id')->constrained('item_lists')->onDelete('cascade')->comment('The retail product that is out of stock');
            $table->foreignId('wholesale_item_id')->constrained('item_lists')->onDelete('cascade')->comment('The wholesale product to convert to retail');
            $table->foreignId('requested_by')->constrained('users')->onDelete('cascade')->comment('Cashier who requested the conversion');
            $table->string('status')->default('pending')->comment('pending, approved, rejected, completed');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null')->comment('Admin who approved/rejected');
            $table->text('request_notes')->nullable()->comment('Notes from cashier');
            $table->text('admin_notes')->nullable()->comment('Notes from admin');
            $table->decimal('quantity_to_convert', 18, 2)->nullable()->comment('Quantity to convert (optional, can convert all)');
            $table->timestamp('requested_at')->useCurrent();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index('status');
            $table->index('requested_by');
            $table->index('requested_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wholesale_to_retail_requests');
    }
};
