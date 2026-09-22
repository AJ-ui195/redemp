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
        if (!Schema::hasTable('void_requests')) {
            Schema::create('void_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sale_id')->constrained()->onDelete('cascade');
                $table->foreignId('requested_by')->constrained('users')->onDelete('cascade'); // Cashier who requested
                $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null'); // Admin who approved/rejected
                $table->string('status')->default('pending'); // pending, approved, rejected
                $table->text('reason')->nullable(); // Reason for void request
                $table->text('rejection_reason')->nullable(); // Reason for rejection (if rejected)
                $table->timestamp('approved_at')->nullable();
                $table->timestamp('rejected_at')->nullable();
                $table->timestamps();
                
                $table->index('status');
                $table->index('sale_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('void_requests');
    }
};
