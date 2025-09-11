<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockInItem extends Model
{
    protected $table = 'stock_in_items';

    protected $fillable = [
        'stock_in_id',
        'reason',
        'quantity',
        'note',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function stockIn(): BelongsTo
    {
        return $this->belongsTo(StockIn::class);
    }
}
