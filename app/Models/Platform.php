<?php

namespace App\Models;

use App\Models\Product\ProductVariant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Platform extends Model
{
    protected $fillable = ['name', 'description'];

    public function productVariants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }
}
