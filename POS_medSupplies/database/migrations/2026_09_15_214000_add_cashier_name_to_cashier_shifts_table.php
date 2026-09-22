<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cashier_shifts')) {
            return;
        }

        Schema::table('cashier_shifts', function (Blueprint $table) {
            if (! Schema::hasColumn('cashier_shifts', 'cashier_name')) {
                $table->string('cashier_name')->nullable()->after('cashier_user_id');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('cashier_shifts')) {
            return;
        }

        Schema::table('cashier_shifts', function (Blueprint $table) {
            if (Schema::hasColumn('cashier_shifts', 'cashier_name')) {
                $table->dropColumn('cashier_name');
            }
        });
    }
};
