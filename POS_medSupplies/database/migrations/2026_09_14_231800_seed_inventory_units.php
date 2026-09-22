<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('units')) {
            return;
        }

        $now = now();
        $units = [
            ['name' => 'box', 'abbreviation' => 'Box'],
            ['name' => 'case', 'abbreviation' => 'Case'],
            ['name' => 'roll', 'abbreviation' => 'Roll'],
            ['name' => 'gal', 'abbreviation' => 'Gal'],
            ['name' => 'set', 'abbreviation' => 'Set'],
            ['name' => 'bottle', 'abbreviation' => 'Bottle'],
            ['name' => 'unit', 'abbreviation' => 'Unit'],
            ['name' => 'pair', 'abbreviation' => 'Pair'],
            ['name' => 'pck', 'abbreviation' => 'Pck'],
            ['name' => 'piece', 'abbreviation' => 'Piece'],
        ];

        foreach ($units as $unit) {
            $existing = DB::table('units')
                ->whereRaw('LOWER(name) = ?', [strtolower($unit['name'])])
                ->orWhereRaw('LOWER(abbreviation) = ?', [strtolower($unit['abbreviation'])])
                ->first();

            if ($existing) {
                // Keep existing row; optionally align display abbreviation if still short codes
                continue;
            }

            DB::table('units')->insert([
                'name' => $unit['name'],
                'abbreviation' => $unit['abbreviation'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // Align common display labels for seed rows already present
        DB::table('units')->where('name', 'box')->where('abbreviation', 'box')->update([
            'abbreviation' => 'Box',
            'updated_at' => $now,
        ]);
        DB::table('units')->where('name', 'piece')->whereIn('abbreviation', ['pc', 'pcs'])->update([
            'abbreviation' => 'Piece',
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('units')) {
            return;
        }

        DB::table('units')->whereIn('name', [
            'case', 'roll', 'gal', 'set', 'bottle', 'unit', 'pair', 'pck',
        ])->delete();
    }
};
