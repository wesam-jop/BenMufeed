<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->decimal('paid_amount', 12, 2)->default(0)->after('total');
        });

        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->string('payment_type', 32)->default('cash')->after('customer_name');
            $table->foreignId('customer_id')->nullable()->after('payment_type')->constrained()->nullOnDelete();
            $table->decimal('paid_amount', 12, 2)->default(0)->after('total');
        });

        Schema::create('invoice_payments', function (Blueprint $table) {
            $table->id();
            $table->morphs('invoice');
            $table->decimal('amount', 12, 2);
            $table->date('paid_at');
            $table->string('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_payments');

        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('customer_id');
            $table->dropColumn(['payment_type', 'paid_amount']);
        });

        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->dropColumn('paid_amount');
        });
    }
};
