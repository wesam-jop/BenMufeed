<?php

namespace Database\Seeders;

use App\Services\Cms\CmsPageBuilderService;
use Illuminate\Database\Seeder;

class CmsPageSeeder extends Seeder
{
    public function run(): void
    {
        app(CmsPageBuilderService::class)->ensureSystemPages(refreshContent: true);
    }
}
