<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('seo_title')->nullable()->after('description');
            $table->string('seo_description', 500)->nullable()->after('seo_title');
            $table->string('seo_focus_keywords', 500)->nullable()->after('seo_description');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('seo_focus_keywords', 500)->nullable()->after('seo_description');
        });

        Schema::table('site_settings', function (Blueprint $table) {
            $table->text('seo_keywords')->nullable()->after('seo_description');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['seo_title', 'seo_description', 'seo_focus_keywords']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('seo_focus_keywords');
        });

        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn('seo_keywords');
        });
    }
};
