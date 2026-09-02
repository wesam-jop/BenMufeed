<?php

namespace App\Http\Requests\Admin\Cms\Site;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSiteSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'business_name' => 'nullable|string|max:255',
            'tagline' => 'nullable|string|max:500',
            'logo' => 'nullable|image|max:2048',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:1000',
            'whatsapp' => 'nullable|string|max:50',
            'instagram' => 'nullable|string|max:255',
            'facebook' => 'nullable|string|max:255',
            'tiktok' => 'nullable|string|max:255',
            'tax_number' => 'nullable|string|max:100',
            'opening_hours' => 'nullable|string|max:255',
            'store_opens_at' => 'nullable|date_format:H:i',
            'store_closes_at' => 'nullable|date_format:H:i',
            'hero_title' => 'nullable|string|max:255',
            'hero_subtitle' => 'nullable|string|max:1000',
            'hero_image' => 'nullable|image|max:4096',
            'homepage_sections' => 'nullable|array',
            'homepage_sections.*' => 'string|in:hero,categories,featured,about_preview,contact_preview',
            'show_featured_products' => 'boolean',
            'featured_products_limit' => 'nullable|integer|min:1|max:24',
            'trust_items' => 'nullable|array|max:8',
            'trust_items.*.title' => 'nullable|string|max:255',
            'categories_section_title' => 'nullable|string|max:255',
            'categories_section_subtitle' => 'nullable|string|max:500',
            'featured_section_title' => 'nullable|string|max:255',
            'featured_section_subtitle' => 'nullable|string|max:500',
            'contact_preview_title' => 'nullable|string|max:255',
            'contact_preview_subtitle' => 'nullable|string|max:500',
            'categories_page_title' => 'nullable|string|max:255',
            'categories_page_subtitle' => 'nullable|string|max:500',
            'categories_page_image' => 'nullable|image|max:4096',
            'products_page_title' => 'nullable|string|max:255',
            'products_page_subtitle' => 'nullable|string|max:500',
            'products_page_image' => 'nullable|image|max:4096',
            'about_title' => 'nullable|string|max:255',
            'about_content' => 'nullable|string|max:10000',
            'about_image' => 'nullable|image|max:4096',
            'about_features' => 'nullable|array',
            'about_features.*.title' => 'nullable|string|max:255',
            'about_features.*.description' => 'nullable|string|max:500',
            'about_eyebrow' => 'nullable|string|max:255',
            'about_why_title' => 'nullable|string|max:255',
            'about_why_subtitle' => 'nullable|string|max:500',
            'about_cta_title' => 'nullable|string|max:255',
            'about_cta_subtitle' => 'nullable|string|max:1000',
            'contact_title' => 'nullable|string|max:255',
            'contact_content' => 'nullable|string|max:10000',
            'contact_map_embed' => 'nullable|string|max:5000',
            'contact_form_enabled' => 'boolean',
            'contact_eyebrow' => 'nullable|string|max:255',
            'map_lat' => 'nullable|numeric|between:-90,90',
            'map_lng' => 'nullable|numeric|between:-180,180',
            'map_title' => 'nullable|string|max:255',
            'map_subtitle' => 'nullable|string|max:500',
            'map_placements' => 'nullable|array',
            'map_placements.*' => 'string|in:home,about,contact',
            'clear_map_location' => 'sometimes|boolean',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'remove_categories_page_image' => 'sometimes|boolean',
            'remove_products_page_image' => 'sometimes|boolean',
            'remove_logo' => 'sometimes|boolean',
            'remove_hero_image' => 'sometimes|boolean',
            'remove_about_image' => 'sometimes|boolean',
        ];
    }
}
