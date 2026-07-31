<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->index(['status', 'created_at'], 'orders_status_created_idx');
            $table->index(['service_type', 'status', 'table_number'], 'orders_table_open_idx');
            $table->index(['source', 'status'], 'orders_source_status_idx');
        });

        Schema::table('kitchen_tickets', function (Blueprint $table): void {
            $table->index(['status', 'created_at'], 'kitchen_tickets_pending_idx');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropIndex('orders_status_created_idx');
            $table->dropIndex('orders_table_open_idx');
            $table->dropIndex('orders_source_status_idx');
        });

        Schema::table('kitchen_tickets', function (Blueprint $table): void {
            $table->dropIndex('kitchen_tickets_pending_idx');
        });
    }
};
