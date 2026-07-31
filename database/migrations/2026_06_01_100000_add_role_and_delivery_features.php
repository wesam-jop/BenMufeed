<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->string('role', 20)->default('cashier')->after('password');
            });

            DB::table('users')->where('is_admin', true)->update(['role' => 'admin']);
        }

        if (! Schema::hasColumn('pos_settings', 'delivery_fee')) {
            Schema::table('pos_settings', function (Blueprint $table): void {
                $table->decimal('delivery_fee', 10, 2)->default(0)->after('kitchen_ticket_footer');
            });
        }

        if (! Schema::hasColumn('orders', 'delivery_fee')) {
            Schema::table('orders', function (Blueprint $table): void {
                $table->decimal('delivery_fee', 10, 2)->nullable()->after('total');
                $table->timestamp('reviewed_at')->nullable()->after('closed_at');
                $table->timestamp('cancelled_at')->nullable()->after('reviewed_at');
            });
        }

        if (Schema::hasTable('product_variant_recipe_items')) {
            return;
        }

        Schema::create('product_variant_recipe_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('material_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 12, 3);
            $table->timestamps();

            $table->unique(['product_variant_id', 'material_id'], 'variant_recipe_material_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variant_recipe_items');

        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn(['delivery_fee', 'reviewed_at', 'cancelled_at']);
        });

        Schema::table('pos_settings', function (Blueprint $table): void {
            $table->dropColumn('delivery_fee');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('role');
        });
    }
};
