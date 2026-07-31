<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('storefront_customers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('phone', 32)->nullable()->unique();
            $table->timestamp('phone_verified_at')->nullable();
            $table->string('account_type', 32);
            $table->foreignId('default_restaurant_table_id')
                ->nullable()
                ->constrained('restaurant_tables')
                ->nullOnDelete();
            $table->string('created_via', 32);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('account_type');
        });

        Schema::create('storefront_customer_otps', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 32);
            $table->string('code_hash');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['phone', 'expires_at']);
        });

        Schema::create('storefront_customer_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('storefront_customer_id')
                ->constrained('storefront_customers')
                ->cascadeOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->index(['storefront_customer_id', 'expires_at'], 'sf_customer_sessions_customer_exp_idx');
        });

        Schema::create('storefront_customer_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('storefront_customer_id')
                ->constrained('storefront_customers')
                ->cascadeOnDelete();
            $table->string('label', 80);
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->string('location_note', 500);
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['storefront_customer_id', 'is_default'], 'sf_customer_addresses_customer_def_idx');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('storefront_customer_id')
                ->nullable()
                ->after('customer_session_token')
                ->constrained('storefront_customers')
                ->nullOnDelete();
            $table->foreignId('saved_address_id')
                ->nullable()
                ->after('storefront_customer_id')
                ->constrained('storefront_customer_addresses')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('saved_address_id');
            $table->dropConstrainedForeignId('storefront_customer_id');
        });

        Schema::dropIfExists('storefront_customer_addresses');
        Schema::dropIfExists('storefront_customer_sessions');
        Schema::dropIfExists('storefront_customer_otps');
        Schema::dropIfExists('storefront_customers');
    }
};
