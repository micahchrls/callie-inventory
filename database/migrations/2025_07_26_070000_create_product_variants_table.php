<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('platform_id')->nullable()->constrained('platforms')->onDelete('set null');
            // Variant identification
            $table->string('sku')->unique();
            $table->string('variation_name')->nullable(); // e.g., "Small Gold", "Large Silver"

            // Variant attributes
            $table->string('size')->nullable();
            $table->string('color')->nullable();
            $table->string('material')->nullable();
            $table->string('weight')->nullable();
            $table->json('additional_attributes')->nullable(); // For flexible attributes

            // Inventory management only (no pricing)
            $table->integer('quantity_in_stock')->default(0);
            $table->integer('reorder_level')->default(10);
            $table->enum('status', ['in_stock', 'low_stock', 'out_of_stock'])->default('in_stock');
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->datetime('last_restocked_at')->nullable();

            $table->softDeletes();
            $table->timestamps();

            // Indexes
            $table->index(['product_id', 'is_active']);
            $table->index(['status']);
            $table->index(['quantity_in_stock']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
