<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Customer list for admin / cashier (registered name, TIN, business address).
     */
    public function up(): void
    {
        if (Schema::hasTable('customers')) {
            return;
        }

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('registered_name');
            $table->string('tin', 50)->nullable();
            $table->text('business_address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('registered_name');
            $table->index('tin');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
