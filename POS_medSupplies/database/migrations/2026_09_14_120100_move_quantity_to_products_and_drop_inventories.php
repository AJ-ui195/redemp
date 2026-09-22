<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('products') && ! Schema::hasColumn('products', 'quantity')) {
            Schema::table('products', function (Blueprint $table) {
                $table->unsignedInteger('quantity')->default(0)->after('cost_price');
            });
        }

        if (Schema::hasTable('inventories') && Schema::hasColumn('products', 'quantity')) {
            $rows = DB::table('inventories')
                ->select('product_id', 'quantity')
                ->orderBy('id')
                ->get();

            foreach ($rows as $row) {
                DB::table('products')
                    ->where('id', $row->product_id)
                    ->update(['quantity' => $row->quantity]);
            }

            Schema::drop('inventories');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('inventories')) {
            Schema::create('inventories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->unique()->constrained()->cascadeOnDelete();
                $table->unsignedInteger('quantity')->default(0);
                $table->timestamps();
            });
        }

        if (Schema::hasTable('products') && Schema::hasColumn('products', 'quantity')) {
            $products = DB::table('products')
                ->select('id', 'quantity', 'created_at', 'updated_at')
                ->get();

            foreach ($products as $product) {
                DB::table('inventories')->insert([
                    'product_id' => $product->id,
                    'quantity' => $product->quantity ?? 0,
                    'created_at' => $product->created_at,
                    'updated_at' => $product->updated_at,
                ]);
            }

            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('quantity');
            });
        }
    }
};
