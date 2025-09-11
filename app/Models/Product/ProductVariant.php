<?php

namespace App\Models\Product;

use App\Enums\Platform;
use App\Models\StockIn;
use App\Models\StockInItem;
use App\Models\StockOut;
use App\Models\StockOutItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class ProductVariant extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'product_id',
        'sku', // full unique SKU
        'quantity_in_stock',
        'reorder_level',
        'status',
        'size',
        'color',
        'material',
        'variant_initial',
        'additional_attributes',
        'is_discontinued',
        'last_restocked_at',
    ];

    protected $casts = [
        'last_restocked_at' => 'datetime',
        'additional_attributes' => 'array',
        'is_discontinued' => 'boolean',
    ];

    // Relationships
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function stockOuts(): HasMany
    {
        return $this->hasMany(StockOut::class, 'product_variant_id');
    }

    public function stockOutItems(): HasMany
    {
        return $this->hasMany(StockOutItem::class, 'stock_out_id')
            ->join('stock_outs', 'stock_out_items.stock_out_id', '=', 'stock_outs.id')
            ->where('stock_outs.product_variant_id', $this->getKey())
            ->select('stock_out_items.*');
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

    /**
     * Update the status based on current stock levels
     */
    public function updateStockStatus(): void
    {
        $oldStatus = $this->status;

        if ($this->quantity_in_stock <= 0) {
            $newStatus = 'out_of_stock';
        } elseif ($this->quantity_in_stock <= $this->reorder_level) {
            $newStatus = 'low_stock';
        } else {
            $newStatus = 'in_stock';
        }

        // Only update if status actually changed
        if ($oldStatus !== $newStatus) {
            $this->update(['status' => $newStatus]);
        }
    }

    /**
     * Handle stock out movement.
     *
     * @param  array  $items  Each item contains platform, quantity_out, and notes
     * @param  string  $reason  The reason for stock out
     * @param  string  $stockOutDate  The date for stock out
     */
    public function stockOut(array $items, string $reason = 'sale', string $stockOutDate = null): array
    {
        $results = [];
        $totalQuantity = 0;

        // Calculate total quantity needed first
        foreach ($items as $item) {
            $totalQuantity += $item['quantity_out'];
        }

        // Validate total stock availability upfront
        if ($totalQuantity > $this->quantity_in_stock) {
            throw new \Exception("Insufficient stock. Available: {$this->quantity_in_stock}, Requested: {$totalQuantity}");
        }

        DB::transaction(function () use ($items, &$results, $totalQuantity, $reason, $stockOutDate) {
            // Get the custom date from the first item (or use current timestamp)
            $stockOutDate = $stockOutDate ?? $items[0]['stock_out_date'] ?? now();

            // Create the main StockOut record
            $stockOut = StockOut::create([
                'product_id' => $this->product_id,
                'product_variant_id' => $this->id,
                'user_id' => auth()->id(),
                'reason' => $reason,
                'total_quantity' => $totalQuantity,
                'created_at' => $stockOutDate,
                'updated_at' => $stockOutDate,
            ]);

            foreach ($items as $item) {
                $quantityOut = $item['quantity_out'];

                // Validate individual item quantity
                if ($quantityOut <= 0) {
                    throw new \Exception('Quantity out must be greater than 0');
                }

                // Create StockOutItem record
                StockOutItem::create([
                    'stock_out_id' => $stockOut->id,
                    'platform' => $item['platform'],
                    'quantity' => $quantityOut,
                    'note' => $item['notes'] ?? null,
                    'created_at' => $stockOutDate,
                    'updated_at' => $stockOutDate,
                ]);

                $results[] = [
                    'platform' => $item['platform'],
                    'quantity_out' => $quantityOut,
                    'notes' => $item['notes'] ?? '',
                ];
            }

            // Deduct total stock once at the end
            $this->decrement('quantity_in_stock', $totalQuantity);

            // Refresh the model to get updated values
            $this->refresh();
        });

        // Update status after transaction completes
        $this->updateStockStatus();

        return $results;
    }

    /**
     * Handle stock in movement.
     *
     * @param  array  $items  Each item contains quantity_in, reason, and notes
     */
    public function stockIn(array $items): array
    {
        $results = [];
        $totalQuantity = 0;

        // Calculate total quantity to be added first
        foreach ($items as $item) {
            $totalQuantity += $item['quantity_in'];
        }

        // Validate quantities are positive
        foreach ($items as $item) {
            if ($item['quantity_in'] <= 0) {
                throw new \Exception('Quantity in must be greater than 0');
            }
        }

        DB::transaction(function () use ($items, &$results, $totalQuantity) {
            // Get the reason from the first item (or default to 'restock')
            $mainReason = $items[0]['reason'] ?? 'restock';

            // Get the custom date from the first item (or use current timestamp)
            $stockInDate = $items[0]['stock_in_date'] ?? now();

            // Create the main StockIn record
            $stockIn = StockIn::create([
                'product_id' => $this->product_id,
                'product_variant_id' => $this->id,
                'user_id' => auth()->id(),
                'reason' => $mainReason, // Use reason from form items
                'total_quantity' => $totalQuantity,
                'created_at' => $stockInDate,
                'updated_at' => $stockInDate,
            ]);

            foreach ($items as $item) {
                $quantityIn = $item['quantity_in'];
                $reason = $item['reason'] ?? 'restock';

                // Create StockInItem record (without reason since it's now in main record)
                StockInItem::create([
                    'stock_in_id' => $stockIn->id,
                    'quantity' => $quantityIn,
                    'note' => $item['notes'] ?? null,
                    'created_at' => $stockInDate,
                    'updated_at' => $stockInDate,
                ]);

                $results[] = [
                    'reason' => $reason,
                    'quantity_in' => $quantityIn,
                    'notes' => $item['notes'] ?? '',
                ];
            }

            // Add total stock (increment for stock IN)
            $this->increment('quantity_in_stock', $totalQuantity);

            // Refresh the model to get updated values
            $this->refresh();
        });

        // Update status after transaction completes
        $this->updateStockStatus();

        return $results;
    }
}
