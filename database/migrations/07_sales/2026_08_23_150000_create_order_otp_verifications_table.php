<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_otp_verifications', function (Blueprint $table) {
            $table->id();
            $table->string('phone');
            $table->string('name');
            $table->string('code_hash');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('verified_at')->nullable();
            $table->string('session_id')->nullable()->index();
            $table->timestamps();

            $table->index(['phone', 'verified_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_otp_verifications');
    }
};
