<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('table_scan_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('restaurant_table_id')->constrained()->cascadeOnDelete();
            $table->string('session_token', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('last_seen_at')->useCurrent();
            $table->timestamps();

            $table->index(['restaurant_table_id', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('table_scan_sessions');
    }
};
