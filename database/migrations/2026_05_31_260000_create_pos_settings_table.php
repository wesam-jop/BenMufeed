<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('kitchen_printer_name')->nullable();
            $table->string('cashier_printer_name')->nullable();
            $table->boolean('auto_print_kitchen')->default(true);
            $table->boolean('auto_print_cashier')->default(true);
            $table->boolean('kitchen_alert_sound')->default(true);
            $table->boolean('receipt_show_logo')->default(true);
            $table->unsignedSmallInteger('receipt_paper_width_mm')->default(80);
            $table->string('receipt_business_name')->nullable();
            $table->text('receipt_header')->nullable();
            $table->text('receipt_footer')->nullable();
            $table->text('kitchen_ticket_footer')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_settings');
    }
};
