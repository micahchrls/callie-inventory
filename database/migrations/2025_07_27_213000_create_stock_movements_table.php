<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create stock movements for complete audit trail.
     * Track every inventory change with reason and user.
     */
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained('product_variants')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            
            // Movement details
            $table->enum('movement_type', [
                'restock', 'sale', 'adjustment', 'damage', 'loss', 'return', 'transfer', 'initial_stock', 'manual_edit'
            ]);
            $table->integer('quantity_before')->default(0);
            $table->integer('quantity_change'); // Can be negative
            $table->integer('quantity_after')->default(0);
            
            // Context and references
            $table->string('reference_type', 100)->nullable(); // 'order', 'purchase', 'adjustment', 'bulk_action'
            $table->string('reference_id', 100)->nullable(); // Order ID, Purchase ID, etc.
            $table->string('platform', 50)->nullable(); // Which platform if sale
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();
            
            // Cost tracking for COGS
            $table->decimal('unit_cost', 10, 2)->nullable();
            $table->decimal('total_cost', 10, 2)->nullable();
            
            // IP and browser info for security
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            
            $table->timestamps();
            
            // Indexes for reporting and performance
            $table->index(['product_variant_id', 'created_at']);
            $table->index(['movement_type', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['platform', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
