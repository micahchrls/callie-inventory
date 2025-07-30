<?php

namespace App\Models\Product;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ProductVariant extends Model
{

    use SoftDeletes;
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

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
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
    public function adjustStock(int $quantity, string $action, ?string $reason = null): void
    {
        $quantityBefore = $this->quantity_in_stock;
        $quantityChange = 0;

        switch ($action) {
            case 'add':
                $this->quantity_in_stock += $quantity;
                $quantityChange = $quantity;
                break;
            case 'subtract':
                $actualSubtract = min($quantity, $this->quantity_in_stock);
                $this->quantity_in_stock = max(0, $this->quantity_in_stock - $quantity);
                $quantityChange = -$actualSubtract;
                break;
            case 'set':
                $this->quantity_in_stock = max(0, $quantity);
                $quantityChange = $this->quantity_in_stock - $quantityBefore;
                break;
        }

        $quantityAfter = $this->quantity_in_stock;

        // Auto-update status based on new quantity
        $this->updateStatus();

        // Save the variant changes
        $this->save();

        // Create proper stock movement record
        $this->stockMovements()->create([
            'movement_type' => $this->mapActionToMovementType($action),
            'quantity_before' => $quantityBefore,
            'quantity_change' => $quantityChange,
            'quantity_after' => $quantityAfter,
            'user_id' => auth()->id(),
            'reason' => $reason ?: ucfirst($action) . ' stock adjustment',
            'reference_type' => 'manual_adjustment',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Map action string to movement type enum
     */
    private function mapActionToMovementType(string $action): string
    {
        return match($action) {
            'add' => 'restock',
            'subtract' => 'adjustment',
            'set' => 'adjustment',
            default => 'manual_edit'
        };
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

    /**
     * Generate unique SKU for the product variant
     */
    public function generateSku(): string
    {
        // Get product prefix (first 3 characters of product name, uppercase)
        $productPrefix = Str::upper(Str::substr(preg_replace('/[^A-Za-z0-9]/', '', $this->product->name), 0, 3));

        // Get variant attributes for SKU
        $attributes = array_filter([
            $this->size ? Str::upper(Str::substr(preg_replace('/[^A-Za-z0-9]/', '', $this->size), 0, 2)) : null,
            $this->color ? Str::upper(Str::substr(preg_replace('/[^A-Za-z0-9]/', '', $this->color), 0, 2)) : null,
            $this->material ? Str::upper(Str::substr(preg_replace('/[^A-Za-z0-9]/', '', $this->material), 0, 2)) : null,
        ]);

        $attributeString = implode('', $attributes);

        // Generate base SKU
        $baseSku = $productPrefix . '-' . $attributeString;

        // Ensure uniqueness by adding a number suffix if needed
        $counter = 1;
        $sku = $baseSku;

        while (static::where('sku', $sku)->where('id', '!=', $this->id)->exists()) {
            $sku = $baseSku . '-' . str_pad($counter, 2, '0', STR_PAD_LEFT);
            $counter++;
        }

        return $sku;
    }

    /**
     * Boot method to handle SKU auto-generation and stock movement
     */
    protected static function booted()
    {
        static::creating(function ($variant) {
            if (empty($variant->sku)) {
                $variant->sku = $variant->generateSku();
            }
        });

        static::created(function ($variant) {
            // Create initial stock movement if variant has initial stock
            if ($variant->quantity_in_stock > 0) {
                $variant->stockMovements()->create([
                    'movement_type' => 'initial_stock',
                    'quantity_before' => 0,
                    'quantity_change' => $variant->quantity_in_stock,
                    'quantity_after' => $variant->quantity_in_stock,
                    'user_id' => auth()->id(),
                    'reason' => 'Initial stock for new variant',
                    'reference_type' => 'variant_creation',
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]);
            }
        });

        static::updating(function ($variant) {
            if ($variant->isDirty(['size', 'color', 'material', 'product_id'])) {
                $variant->sku = $variant->generateSku();
            }
        });
    }
}
