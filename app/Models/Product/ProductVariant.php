<?php

namespace App\Models\Product;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id',
        'sku',
        'variation_name',
        'size',
        'color',
        'material',
        'weight',
        'additional_attributes',
        'quantity_in_stock',
        'reorder_level',
        'status',
        'notes',
        'is_active',
        'last_restocked_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_restocked_at' => 'datetime',
        'additional_attributes' => 'array',
    ];

    // Relationships
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // Inventory status methods
    public function isLowStock(): bool
    {
        return $this->quantity_in_stock <= $this->reorder_level && $this->quantity_in_stock > 0;
    }

    public function isOutOfStock(): bool
    {
        return $this->quantity_in_stock <= 0;
    }

    public function isInStock(): bool
    {
        return $this->quantity_in_stock > $this->reorder_level;
    }

    // Auto-update status based on quantity
    public function updateStatus(): void
    {
        if ($this->quantity_in_stock <= 0) {
            $this->status = 'out_of_stock';
        } elseif ($this->quantity_in_stock <= $this->reorder_level) {
            $this->status = 'low_stock';
        } else {
            $this->status = 'in_stock';
        }
        $this->save();
    }

    // Scope for low stock variants
    public function scopeLowStock($query)
    {
        return $query->whereRaw('quantity_in_stock <= reorder_level AND quantity_in_stock > 0');
    }

    // Scope for out of stock variants
    public function scopeOutOfStock($query)
    {
        return $query->where('quantity_in_stock', '<=', 0);
    }

    // Scope for in stock variants
    public function scopeInStock($query)
    {
        return $query->whereRaw('quantity_in_stock > reorder_level');
    }

    // Scope for active variants
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Get human-readable stock status text
    public function getStockStatusText(): string
    {
        if ($this->isOutOfStock()) {
            return 'Out of Stock';
        } elseif ($this->isLowStock()) {
            return 'Low Stock';
        } else {
            return 'In Stock';
        }
    }

    // Get stock status color for UI display
    public function getStockStatusColor(): string
    {
        if ($this->isOutOfStock()) {
            return 'danger';
        } elseif ($this->isLowStock()) {
            return 'warning';
        } else {
            return 'success';
        }
    }

    // Adjust stock quantity
    public function adjustStock(int $quantity, string $action): void
    {
        switch ($action) {
            case 'add':
                $this->quantity_in_stock += $quantity;
                break;
            case 'subtract':
                $this->quantity_in_stock = max(0, $this->quantity_in_stock - $quantity);
                break;
            case 'set':
                $this->quantity_in_stock = max(0, $quantity);
                break;
        }

        // Auto-update status based on new quantity
        $this->updateStatus();
    }

    // Get full variant name with attributes
    public function getFullName(): string
    {
        $parts = [$this->product->name];

        if ($this->variation_name) {
            $parts[] = $this->variation_name;
        } else {
            $attributes = array_filter([
                $this->size,
                $this->color,
                $this->material,
                $this->weight,
            ]);
            if (!empty($attributes)) {
                $parts[] = implode(' - ', $attributes);
            }
        }

        return implode(' | ', $parts);
    }

    // Get variant attributes as string
    public function getAttributesString(): string
    {
        $attributes = array_filter([
            $this->size ? "Size: {$this->size}" : null,
            $this->color ? "Color: {$this->color}" : null,
            $this->material ? "Material: {$this->material}" : null,
            $this->weight ? "Weight: {$this->weight}" : null,
        ]);

        return implode(' | ', $attributes);
    }
}
