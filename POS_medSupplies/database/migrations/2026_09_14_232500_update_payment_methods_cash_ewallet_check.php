<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payment_methods')) {
            return;
        }

        $now = now();

        foreach (['cash', 'e_wallet', 'check'] as $name) {
            $exists = DB::table('payment_methods')
                ->whereRaw('LOWER(name) = ?', [strtolower($name)])
                ->exists();

            if (! $exists) {
                DB::table('payment_methods')->insert([
                    'name' => $name,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        $cardId = DB::table('payment_methods')
            ->whereRaw('LOWER(name) = ?', ['card'])
            ->value('id');

        if ($cardId) {
            // Remap any leftover references to cash before removing card
            if (Schema::hasTable('sale_payments')) {
                $cashId = DB::table('payment_methods')
                    ->whereRaw('LOWER(name) = ?', ['cash'])
                    ->value('id');

                if ($cashId) {
                    DB::table('sale_payments')
                        ->where('payment_method_id', $cardId)
                        ->update(['payment_method_id' => $cashId]);
                }
            }

            DB::table('payment_methods')->where('id', $cardId)->delete();
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('payment_methods')) {
            return;
        }

        $now = now();

        if (! DB::table('payment_methods')->whereRaw('LOWER(name) = ?', ['card'])->exists()) {
            DB::table('payment_methods')->insert([
                'name' => 'card',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        DB::table('payment_methods')
            ->whereIn('name', ['e_wallet', 'check'])
            ->delete();
    }
};
