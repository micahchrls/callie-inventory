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
        Schema::table('products', function (Blueprint $table) {
            $table->string('sku', 50)->unique()->nullable()->after('description');
//            $table->decimal('price', 10, 2)->nullable()->after('sku');
//            $table->decimal('cost_price', 10, 2)->nullable()->after('price');
            $table->integer('quantity_in_stock')->default(0)->after('sku');
            $table->integer('reorder_level')->default(5)->after('quantity_in_stock');
            $table->string('location', 100)->nullable()->after('reorder_level');
            $table->enum('status', ['in_stock', 'low_stock', 'out_of_stock', 'discontinued'])->default('in_stock')->after('location');
            $table->text('notes')->nullable()->after('status');
            $table->boolean('is_active')->default(true)->after('notes');
            $table->timestamp('last_restocked_at')->nullable()->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'sku',
//                'price',
//                'cost_price',
                'quantity_in_stock',
                'reorder_level',
                'location',
                'status',
                'notes',
                'is_active',
                'last_restocked_at'
            ]);
        });
    }
};
