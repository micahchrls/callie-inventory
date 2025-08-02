<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Product\ProductVariant;

class Platform extends Model
{
    protected $fillable = ['name', 'description'];

    public function productVariants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }
}
