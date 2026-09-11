<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CmsPageSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(BenMufeedCmsSeeder::class);
    }
}
