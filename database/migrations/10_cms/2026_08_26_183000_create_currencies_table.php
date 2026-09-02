<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('name');
            $table->string('symbol', 20);
            $table->string('symbol_position', 10)->default('after'); // before|after
            $table->unsignedTinyInteger('decimal_places')->default(2);
            $table->decimal('rate_to_primary', 18, 6)->default(1);
            $table->boolean('is_primary')->default(false);
            $table->boolean('show_on_receipt')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};
