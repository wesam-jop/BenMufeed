<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SiteLogoTest extends TestCase
{
    public function test_storefront_receives_logo_url_from_settings_media(): void
    {
        Storage::fake('public');

        $settings = SiteSetting::current();
        $settings
            ->addMedia(UploadedFile::fake()->image('brand-logo.png', 320, 120))
            ->toMediaCollection('logo');

        $response = $this->get('/');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('site.site_name', $settings->site_name)
            ->where('site.logo_url', fn ($url) => is_string($url) && $url !== '')
        );
    }
}
