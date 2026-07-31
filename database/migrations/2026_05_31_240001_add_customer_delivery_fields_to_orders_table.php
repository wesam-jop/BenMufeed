<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->foreignId('restaurant_table_id')
                ->nullable()
                ->after('table_number')
                ->constrained('restaurant_tables')
                ->nullOnDelete();
            $table->decimal('delivery_latitude', 10, 7)->nullable()->after('customer_note');
            $table->decimal('delivery_longitude', 10, 7)->nullable()->after('delivery_latitude');
            $table->text('delivery_location_note')->nullable()->after('delivery_longitude');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('restaurant_table_id');
            $table->dropColumn(['delivery_latitude', 'delivery_longitude', 'delivery_location_note']);
        });
    }
};
