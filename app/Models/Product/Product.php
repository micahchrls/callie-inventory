<?php

namespace App\Models\Product;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Product extends Model
{
    protected $fillable = [
        'name',
        'description',
        'product_category_id',
        'product_sub_category_id',
    ];

    // Relationships
    public function productCategory(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class);
    }

    public function productSubCategory(): BelongsTo
    {
        return $this->belongsTo(ProductSubCategory::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function activeVariants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->where('is_active', true);
    }

    // Aggregated inventory methods (calculated from variants)
    public function getTotalStock(): int
    {
        return $this->variants()->sum('quantity_in_stock');
    }

    // Check if product has any in-stock variants
    public function hasStock(): bool
    {
        return $this->variants()->where('quantity_in_stock', '>', 0)->exists();
    }

    // Check if product has any low-stock variants
    public function hasLowStock(): bool
    {
        return $this->variants()->whereRaw('quantity_in_stock <= reorder_level AND quantity_in_stock > 0')->exists();
    }

    // Get variant count
    public function getVariantsCount(): int
    {
        return $this->variants()->count();
    }

    public function getActiveVariantsCount(): int
    {
        return $this->activeVariants()->count();
    }

    // Get product status based on variants
    public function getProductStatus(): string
    {
        $totalStock = $this->getTotalStock();

        if ($totalStock <= 0) {
            return 'out_of_stock';
        } elseif ($this->hasLowStock()) {
            return 'low_stock';
        } else {
            return 'in_stock';
        }
    }

    public function getProductStatusText(): string
    {
        switch ($this->getProductStatus()) {
            case 'out_of_stock':
                return 'Out of Stock';
            case 'low_stock':
                return 'Low Stock';
            default:
                return 'In Stock';
        }
    }

    public function getProductStatusColor(): string
    {
        switch ($this->getProductStatus()) {
            case 'out_of_stock':
                return 'danger';
            case 'low_stock':
                return 'warning';
            default:
                return 'success';
        }
    }

    // Scope for products with stock
    public function scopeWithStock($query)
    {
        return $query->whereHas('variants', function ($q) {
            $q->where('quantity_in_stock', '>', 0);
        });
    }

    // Scope for products with low stock
    public function scopeWithLowStock($query)
    {
        return $query->whereHas('variants', function ($q) {
            $q->whereRaw('quantity_in_stock <= reorder_level AND quantity_in_stock > 0');
        });
    }

    // Scope for products out of stock
    public function scopeOutOfStock($query)
    {
        return $query->whereDoesntHave('variants', function ($q) {
            $q->where('quantity_in_stock', '>', 0);
        });
    }

    // Get main variant (first active variant or any variant if no active ones)
    public function getMainVariant(): ?ProductVariant
    {
        return $this->activeVariants()->first() ?? $this->variants()->first();
    }
}
