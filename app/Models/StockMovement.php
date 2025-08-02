<?php

namespace App\Models;

use App\Models\Product\ProductVariant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    protected $fillable = [
        'product_variant_id',
        'user_id',
        'movement_type',
        'quantity_before',
        'quantity_change',
        'quantity_after',
        'reference_type',
        'reference_id',
        'platform',
        'reason',
        'notes',
        'unit_cost',
        'total_cost',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'quantity_before' => 'integer',
        'quantity_change' => 'integer',
        'quantity_after' => 'integer',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Static method to log stock movements
    public static function logMovement(
        ProductVariant $variant,
        string $movementType,
        int $quantityChange,
        ?User $user = null,
        array $options = []
    ): self {
        $quantityBefore = $variant->getOriginal('quantity_in_stock') ?? $variant->quantity_in_stock;
        $quantityAfter = $quantityBefore + $quantityChange;

        return self::create([
            'product_variant_id' => $variant->id,
            'user_id' => $user?->id ?? auth()->id(),
            'movement_type' => $movementType,
            'quantity_before' => $quantityBefore,
            'quantity_change' => $quantityChange,
            'quantity_after' => $quantityAfter,
            'reference_type' => $options['reference_type'] ?? null,
            'reference_id' => $options['reference_id'] ?? null,
            'platform' => $options['platform'] ?? null,
            'reason' => $options['reason'] ?? null,
            'notes' => $options['notes'] ?? null,
            'unit_cost' => $options['unit_cost'] ?? null,
            'total_cost' => $options['total_cost'] ?? null,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }

    // Scope methods for filtering
    public function scopeByVariant($query, $variantId)
    {
        return $query->where('product_variant_id', $variantId);
    }

    public function scopeByMovementType($query, $type)
    {
        return $query->where('movement_type', $type);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    public function scopeStockIncreases($query)
    {
        return $query->where('quantity_change', '>', 0);
    }

    public function scopeStockDecreases($query)
    {
        return $query->where('quantity_change', '<', 0);
    }

    // Attribute accessors
    public function getMovementTypeDisplayAttribute(): string
    {
        return match ($this->movement_type) {
            'restock' => 'Restock',
            'sale' => 'Sale',
            'adjustment' => 'Adjustment',
            'damage' => 'Damage',
            'loss' => 'Loss',
            'return' => 'Return',
            'transfer' => 'Transfer',
            'initial_stock' => 'Initial Stock',
            'manual_edit' => 'Manual Edit',
            default => ucfirst($this->movement_type),
        };
    }

    public function getMovementDirectionAttribute(): string
    {
        return $this->quantity_change > 0 ? 'increase' : 'decrease';
    }

    public function getAbsoluteQuantityChangeAttribute(): int
    {
        return abs($this->quantity_change);
    }
}
