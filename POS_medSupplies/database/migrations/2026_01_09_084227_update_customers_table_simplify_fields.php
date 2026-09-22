<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('customers') || Schema::hasColumn('customers', 'registered_name')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            // Add new columns first
            $table->string('registered_name')->nullable()->after('id');
            $table->string('tin')->nullable()->after('registered_name');
            $table->text('business_address')->nullable()->after('tin');
        });
        
        // Copy data from old columns to new columns
        DB::statement('UPDATE customers SET registered_name = customer_name, business_address = address');
        
        Schema::table('customers', function (Blueprint $table) {
            // Drop old columns
            $table->dropColumn(['customer_name', 'id_number', 'id_type', 'issuing_lgu', 'email', 'phone', 'address', 'notes']);
            
            // Make registered_name required
            $table->string('registered_name')->nullable(false)->change();
        });
        
        // Update indexes
        Schema::table('customers', function (Blueprint $table) {
            $table->index('registered_name');
            $table->index('tin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // Add back old columns
            $table->string('customer_name')->nullable()->after('id');
            $table->string('id_number')->nullable()->after('customer_name');
            $table->string('id_type')->nullable()->after('id_number');
            $table->string('issuing_lgu')->nullable()->after('id_type');
            $table->string('email')->nullable()->after('issuing_lgu');
            $table->string('phone')->nullable()->after('email');
            $table->text('address')->nullable()->after('phone');
            $table->text('notes')->nullable()->after('address');
        });
        
        // Copy data back
        DB::statement('UPDATE customers SET customer_name = registered_name, address = business_address');
        
        Schema::table('customers', function (Blueprint $table) {
            // Drop new columns
            $table->dropColumn(['registered_name', 'tin', 'business_address']);
            
            // Make customer_name required
            $table->string('customer_name')->nullable(false)->change();
        });
        
        // Restore indexes
        Schema::table('customers', function (Blueprint $table) {
            $table->index('customer_name');
            $table->index('id_number');
        });
    }
};
