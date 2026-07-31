<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_sales_summaries', function (Blueprint $table): void {
            $table->date('sale_date')->primary();
            $table->unsignedInteger('pos_orders_count')->default(0);
            $table->unsignedInteger('takeaway_count')->default(0);
            $table->unsignedInteger('table_count')->default(0);
            $table->decimal('takeaway_total', 14, 2)->default(0);
            $table->decimal('table_total', 14, 2)->default(0);
            $table->decimal('pos_total', 14, 2)->default(0);
            $table->decimal('collected_total', 14, 2)->default(0);
            $table->unsignedInteger('invoice_count')->default(0);
            $table->decimal('invoice_total', 14, 2)->default(0);
            $table->decimal('grand_total', 14, 2)->default(0);
            $table->unsignedInteger('open_tables_count')->default(0);
            $table->decimal('open_tables_total', 14, 2)->default(0);
            $table->timestamp('computed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_sales_summaries');
    }
};
