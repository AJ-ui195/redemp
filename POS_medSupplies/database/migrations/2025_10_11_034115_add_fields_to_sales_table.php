<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (!Schema::hasColumn('sales', 'receipt_number')) {
                $table->string('receipt_number')->unique()->nullable()->after('id');
            }
            if (!Schema::hasColumn('sales', 'user_id')) {
                $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade')->after('amount');
    }
            if (!Schema::hasColumn('sales', 'shift_id')) {
                $table->foreignId('shift_id')->nullable()->constrained()->onDelete('set null')->after('user_id');
            }
            if (!Schema::hasColumn('sales', 'items')) {
                $table->json('items')->nullable()->after('shift_id');
            }
            if (!Schema::hasColumn('sales', 'subtotal')) {
                $table->decimal('subtotal', 12, 2)->default(0)->after('items');
            }
            if (!Schema::hasColumn('sales', 'tax')) {
                $table->decimal('tax', 12, 2)->default(0)->after('subtotal');
            }
            if (!Schema::hasColumn('sales', 'discount')) {
                $table->decimal('discount', 12, 2)->default(0)->after('tax');
            }
            if (!Schema::hasColumn('sales', 'payment_method')) {
                $table->string('payment_method')->default('cash')->after('discount');
            }
            if (!Schema::hasColumn('sales', 'amount_tendered')) {
                $table->decimal('amount_tendered', 12, 2)->nullable()->after('payment_method');
            }
            if (!Schema::hasColumn('sales', 'change_amount')) {
                $table->decimal('change_amount', 12, 2)->default(0)->after('amount_tendered');
            }
            if (!Schema::hasColumn('sales', 'customer_email')) {
                $table->string('customer_email')->nullable()->after('change_amount');
            }
            if (!Schema::hasColumn('sales', 'status')) {
                $table->string('status')->default('completed')->after('customer_email');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $columns = [
                'receipt_number',
                'user_id',
                'shift_id',
                'items',
                'subtotal',
                'tax',
                'discount',
                'payment_method',
                'amount_tendered',
                'change_amount',
                'customer_email',
                'status'
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('sales', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
