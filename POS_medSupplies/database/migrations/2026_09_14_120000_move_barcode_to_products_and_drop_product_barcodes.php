<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('products') && ! Schema::hasColumn('products', 'barcode')) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('barcode')->nullable()->unique()->after('sku');
            });
        }

        if (Schema::hasTable('product_barcodes') && Schema::hasColumn('products', 'barcode')) {
            $rows = DB::table('product_barcodes')
                ->select('product_id', 'barcode')
                ->orderBy('id')
                ->get();

            foreach ($rows as $row) {
                DB::table('products')
                    ->where('id', $row->product_id)
                    ->whereNull('barcode')
                    ->update(['barcode' => $row->barcode]);
            }

            Schema::drop('product_barcodes');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('product_barcodes')) {
            Schema::create('product_barcodes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->string('barcode')->unique();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('products') && Schema::hasColumn('products', 'barcode')) {
            $products = DB::table('products')
                ->whereNotNull('barcode')
                ->select('id', 'barcode', 'created_at', 'updated_at')
                ->get();

            foreach ($products as $product) {
                DB::table('product_barcodes')->insert([
                    'product_id' => $product->id,
                    'barcode' => $product->barcode,
                    'created_at' => $product->created_at,
                    'updated_at' => $product->updated_at,
                ]);
            }

            Schema::table('products', function (Blueprint $table) {
                $table->dropUnique(['barcode']);
                $table->dropColumn('barcode');
            });
        }
    }
};
