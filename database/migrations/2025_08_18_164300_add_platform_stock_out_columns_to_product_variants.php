<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            // Add platform-specific stock out columns
            $table->integer('tiktok_stock_out')->default(0)->after('quantity_in_stock');
            $table->integer('shopee_stock_out')->default(0)->after('tiktok_stock_out');
            $table->integer('bazar_stock_out')->default(0)->after('shopee_stock_out');
            $table->integer('others_stock_out')->default(0)->after('bazar_stock_out');

            // Add total stock out column (computed field but can be indexed for performance)
            $table->integer('total_stock_out')->default(0)->after('others_stock_out');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn([
                'tiktok_stock_out',
                'shopee_stock_out',
                'bazar_stock_out',
                'others_stock_out',
                'total_stock_out'
            ]);
        });
    }
};
