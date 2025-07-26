<?php

namespace App\Models\Product;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductCategory extends Model
{
    protected $fillable = [
        'name',
        'description',
    ];

    public function subCategories(): HasMany
    {
        return $this->hasMany(ProductSubCategory::class);
    }
}
