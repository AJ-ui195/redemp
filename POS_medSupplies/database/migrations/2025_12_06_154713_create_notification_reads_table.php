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
        Schema::create('notification_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('notification_type'); // low_stock, pending_po, pending_sample, completed_shift, expiring_item
            $table->string('notification_key'); // Unique identifier (item_id, po_id, sample_id, shift_id, inventory_id)
            $table->timestamp('read_at');
            $table->timestamps();
            
            // Ensure a user can only mark a notification as read once
            $table->unique(['user_id', 'notification_type', 'notification_key'], 'notif_reads_unique');
            
            // Index for faster lookups
            $table->index(['user_id', 'notification_type'], 'notif_reads_user_type_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_reads');
    }
};
