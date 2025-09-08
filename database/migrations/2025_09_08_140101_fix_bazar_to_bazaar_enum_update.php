<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Step 1: First modify the enum to temporarily include 'bazar'
        Schema::table('stock_out_items', function (Blueprint $table) {
            $table->enum('platform', ['tiktok', 'shopee', 'bazar', 'bazaar', 'others'])->nullable()->change();
        });

        // Step 2: Update any existing 'bazar' values to 'bazaar'
        DB::table('stock_out_items')
            ->where('platform', 'bazar')
            ->update(['platform' => 'bazaar']);

        // Step 3: Remove 'bazar' from the enum, keeping only correct values
        Schema::table('stock_out_items', function (Blueprint $table) {
            $table->enum('platform', ['tiktok', 'shopee', 'bazaar', 'others'])->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverse: add 'bazar' back, update 'bazaar' to 'bazar', then remove 'bazaar'
        Schema::table('stock_out_items', function (Blueprint $table) {
            $table->enum('platform', ['tiktok', 'shopee', 'bazar', 'bazaar', 'others'])->nullable()->change();
        });

        DB::table('stock_out_items')
            ->where('platform', 'bazaar')
            ->update(['platform' => 'bazar']);

        Schema::table('stock_out_items', function (Blueprint $table) {
            $table->enum('platform', ['tiktok', 'shopee', 'bazar', 'others'])->nullable()->change();
        });
    }
};
