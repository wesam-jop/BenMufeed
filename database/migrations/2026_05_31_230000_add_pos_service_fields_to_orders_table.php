<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('service_type', 32)->nullable()->after('source');
            $table->unsignedTinyInteger('table_number')->nullable()->after('service_type');
            $table->decimal('paid_amount', 12, 2)->nullable()->after('total');
            $table->timestamp('closed_at')->nullable()->after('paid_amount');

            $table->index(['source', 'status', 'table_number']);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['source', 'status', 'table_number']);
            $table->dropColumn(['service_type', 'table_number', 'paid_amount', 'closed_at']);
        });
    }
};
