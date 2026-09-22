<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('senior_pwd_discounts')) {
            Schema::create('senior_pwd_discounts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sale_id')->nullable()->constrained('sales')->nullOnDelete();
                $table->string('discount_type')->comment('senior_citizen or pwd');
                $table->string('customer_name')->nullable();
                $table->string('id_number')->nullable();
                $table->string('id_type')->nullable()->comment('OSCA or PWD');
                $table->string('issuing_lgu')->nullable();
                $table->longText('id_image')->nullable();
                $table->string('captured_by')->nullable();
                $table->timestamp('captured_at')->nullable();
                $table->timestamps();

                $table->index('sale_id');
                $table->index('discount_type');
                $table->index('id_number');
                $table->index('captured_at');
            });
        }

        if (! Schema::hasTable('sale_payments')) {
            return;
        }

        Schema::table('sale_payments', function (Blueprint $table) {
            if (! Schema::hasColumn('sale_payments', 'discount_type')) {
                $table->string('discount_type')->nullable()->after('amount');
            }
            if (! Schema::hasColumn('sale_payments', 'is_senior_citizen')) {
                $table->boolean('is_senior_citizen')->default(false)->after('discount_type');
            }
            if (! Schema::hasColumn('sale_payments', 'is_pwd')) {
                $table->boolean('is_pwd')->default(false)->after('is_senior_citizen');
            }
            if (! Schema::hasColumn('sale_payments', 'discount_amount')) {
                $table->decimal('discount_amount', 12, 2)->default(0)->after('is_pwd');
            }
            if (! Schema::hasColumn('sale_payments', 'customer_name')) {
                $table->string('customer_name')->nullable()->after('discount_amount');
            }
            if (! Schema::hasColumn('sale_payments', 'id_number')) {
                $table->string('id_number')->nullable()->after('customer_name');
            }
            if (! Schema::hasColumn('sale_payments', 'id_type')) {
                $table->string('id_type')->nullable()->after('id_number');
            }
            if (! Schema::hasColumn('sale_payments', 'issuing_lgu')) {
                $table->string('issuing_lgu')->nullable()->after('id_type');
            }
            if (! Schema::hasColumn('sale_payments', 'senior_pwd_discount_id')) {
                $table->unsignedBigInteger('senior_pwd_discount_id')->nullable()->after('issuing_lgu');
            }
        });

        if (
            Schema::hasTable('senior_pwd_discounts')
            && Schema::hasColumn('sale_payments', 'senior_pwd_discount_id')
        ) {
            Schema::table('sale_payments', function (Blueprint $table) {
                try {
                    $table->foreign('senior_pwd_discount_id')
                        ->references('id')
                        ->on('senior_pwd_discounts')
                        ->nullOnDelete();
                } catch (\Throwable $e) {
                    // Foreign key may already exist
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('sale_payments')) {
            Schema::table('sale_payments', function (Blueprint $table) {
                if (Schema::hasColumn('sale_payments', 'senior_pwd_discount_id')) {
                    try {
                        $table->dropForeign(['senior_pwd_discount_id']);
                    } catch (\Throwable $e) {
                        // ignore
                    }
                }

                foreach ([
                    'senior_pwd_discount_id',
                    'issuing_lgu',
                    'id_type',
                    'id_number',
                    'customer_name',
                    'discount_amount',
                    'is_pwd',
                    'is_senior_citizen',
                    'discount_type',
                ] as $column) {
                    if (Schema::hasColumn('sale_payments', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
