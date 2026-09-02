<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cms_pages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('path');
            $table->string('locale', 5)->default('ar'); // ar | en
            $table->boolean('is_system')->default(false);
            $table->boolean('is_published')->default(true);
            $table->json('theme')->nullable();
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->json('sections')->nullable();
            $table->timestamps();

            $table->unique(['path', 'locale']);
            $table->index(['locale', 'is_published']);
            $table->index(['is_system', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_pages');
    }
};
