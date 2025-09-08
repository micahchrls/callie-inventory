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
        // Update any existing 'bazar' platform values to 'bazaar' in stock_out_items table
        DB::table('stock_out_items')
            ->where('platform', 'bazar')
            ->update(['platform' => 'bazaar']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverse the change: update 'bazaar' back to 'bazar'
        DB::table('stock_out_items')
            ->where('platform', 'bazaar')
            ->update(['platform' => 'bazar']);
    }
};
