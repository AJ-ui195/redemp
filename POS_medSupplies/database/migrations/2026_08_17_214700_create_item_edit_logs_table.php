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
        if (Schema::hasTable('item_edit_logs')) {
            return;
        }

        Schema::create('item_edit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('user_email');
            $table->unsignedBigInteger('item_id');
            $table->string('item_name');
            $table->string('source', 50);
            $table->json('changes');
            $table->timestamps();

            $table->index('created_at');
            $table->index('user_id');
            $table->index(['source', 'item_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_edit_logs');
    }
};
