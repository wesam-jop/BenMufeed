<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->string('venue_scan_token', 64)->nullable()->unique()->after('tiktok_url');
        });

        Schema::table('table_scan_sessions', function (Blueprint $table): void {
            $table->foreignId('restaurant_table_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->dropColumn('venue_scan_token');
        });

        Schema::table('table_scan_sessions', function (Blueprint $table): void {
            $table->foreignId('restaurant_table_id')->nullable(false)->change();
        });
    }
};
