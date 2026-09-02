<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    /**
     * @var list<array{name: string, abbreviation: string}>
     */
    private const UNITS = [
        ['name' => 'كيلوغرام', 'abbreviation' => 'kg'],
        ['name' => 'لتر', 'abbreviation' => 'L'],
        ['name' => 'قطعة', 'abbreviation' => 'pcs'],
        ['name' => 'غرام', 'abbreviation' => 'g'],
        ['name' => 'مل', 'abbreviation' => 'ml'],
    ];

    public function run(): void
    {
        foreach (self::UNITS as $unit) {
            Unit::query()->firstOrCreate(
                ['abbreviation' => $unit['abbreviation']],
                ['name' => $unit['name']],
            );
        }
    }
}
