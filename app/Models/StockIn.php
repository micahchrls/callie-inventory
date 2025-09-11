<?php

namespace App\Models;

use App\Models\Product\Product;
use App\Models\Product\ProductVariant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockIn extends Model
{
    protected $table = 'stock_ins';

    protected $fillable = [
        'product_id',
        'product_variant_id',
        'user_id',
        'reason',
        'total_quantity',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'total_quantity' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function stockInItems(): HasMany
    {
        return $this->hasMany(StockInItem::class);
    }
}
