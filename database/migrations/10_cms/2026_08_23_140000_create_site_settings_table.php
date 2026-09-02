<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();

            // Brand & contact
            $table->string('business_name')->nullable();
            $table->string('tagline')->nullable();
            $table->string('logo')->nullable();
            $table->string('table_qr_logo')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('instagram')->nullable();
            $table->string('facebook')->nullable();
            $table->string('tiktok')->nullable();
            $table->string('tax_number')->nullable();

            // Store hours
            $table->string('opening_hours')->nullable();
            $table->time('store_opens_at')->nullable();
            $table->time('store_closes_at')->nullable();

            // Homepage hero & sections
            $table->string('hero_title')->nullable();
            $table->text('hero_subtitle')->nullable();
            $table->string('hero_image')->nullable();
            $table->json('homepage_sections')->nullable();
            $table->boolean('show_featured_products')->default(true);
            $table->unsignedTinyInteger('featured_products_limit')->default(8);
            $table->json('trust_items')->nullable();
            $table->string('categories_section_title')->nullable();
            $table->string('categories_section_subtitle')->nullable();
            $table->string('featured_section_title')->nullable();
            $table->string('featured_section_subtitle')->nullable();
            $table->string('contact_preview_title')->nullable();
            $table->string('contact_preview_subtitle')->nullable();

            // Storefront page headers
            $table->string('categories_page_title')->nullable();
            $table->string('categories_page_subtitle')->nullable();
            $table->string('categories_page_image')->nullable();
            $table->string('products_page_title')->nullable();
            $table->string('products_page_subtitle')->nullable();
            $table->string('products_page_image')->nullable();

            // About page
            $table->string('about_title')->nullable();
            $table->longText('about_content')->nullable();
            $table->string('about_image')->nullable();
            $table->json('about_features')->nullable();
            $table->string('about_eyebrow')->nullable();
            $table->string('about_why_title')->nullable();
            $table->string('about_why_subtitle')->nullable();
            $table->string('about_cta_title')->nullable();
            $table->text('about_cta_subtitle')->nullable();

            // Contact page
            $table->string('contact_title')->nullable();
            $table->longText('contact_content')->nullable();
            $table->text('contact_map_embed')->nullable();
            $table->decimal('map_lat', 10, 7)->nullable();
            $table->decimal('map_lng', 10, 7)->nullable();
            $table->string('map_title')->nullable();
            $table->string('map_subtitle')->nullable();
            $table->json('map_placements')->nullable();
            $table->boolean('contact_form_enabled')->default(true);
            $table->string('contact_eyebrow')->nullable();

            // Table ordering
            $table->string('table_access_code', 20)->nullable();

            // SEO
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();

            $table->timestamps();
        });

        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('message');
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_messages');
        Schema::dropIfExists('site_settings');
    }
};
