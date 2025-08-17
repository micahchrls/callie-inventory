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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_category_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('product_sub_category_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('base_sku', 50)->unique();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'inactive', 'discontinued'])->default('active');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
