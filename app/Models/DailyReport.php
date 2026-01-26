<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyReport extends Model
{
    protected $fillable = [
        'date',
        'total_orders',
        'total_revenue',
        'total_customers',
        'best_seller_id'
        ];

    protected $casts = [
        'date' => 'date',
        'total_revenue' => 'decimal:2',
    ];

    public function bestSeller(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class, 'best_seller_id');
    }
}
