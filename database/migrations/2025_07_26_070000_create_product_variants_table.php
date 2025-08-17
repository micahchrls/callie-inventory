/* <<<<<<<<<<<<<<  ✨ Windsurf Command 🌟 >>>>>>>>>>>>>>>> */
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

            // SKU and inventory tracking
            $table->string('sku')->unique(); // full unique SKU
            $table->integer('quantity_in_stock')->default(0);
            $table->integer('reorder_level')->default(0);
            $table->enum('status', ['low_stock', 'in_stock', 'out_of_stock', 'discontinued'])->default('in_stock');
            // Variant attributes
            $table->string('size')->nullable();
            $table->string('color')->nullable();
            $table->string('material')->nullable();
            $table->string('variant_initial')->nullable();
            $table->json('additional_attributes')->nullable(); // For flexible attributes

            $table->timestamp('last_restocked_at')->nullable();

            $table->timestamps();
            $table->softDeletes(); // Add soft deletes since model uses SoftDeletes trait
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};

/* <<<<<<<<<<  3a428593-2280-4940-90e8-f19abba7005e  >>>>>>>>>>> */
