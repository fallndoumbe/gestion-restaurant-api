<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Ingredient extends Model
{
    protected $fillable = [
        'name',
        'unit',
        'stock_quantity',
        'min_stock',
        'cost_per_unit'
        ];

    protected $casts = [
        'stock_quantity' => 'decimal:2',
        'min_stock' => 'decimal:2',
        'cost_per_unit' => 'decimal:2',
    ];

    public function menuItems(): BelongsToMany
    {
        return $this->belongsToMany(MenuItem::class, 'menu_ingredients')
            ->withPivot(['quantity_needed'])
            ->withTimestamps();
    }
}
