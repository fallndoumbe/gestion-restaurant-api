<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DailyReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'total_orders',
        'total_revenue',
        'total_customers',
        'best_seller_id'
    ];

    protected $casts = [
        'date' => 'date',
        'total_revenue' => 'decimal:2'
    ];

    // Relations
    public function bestSeller()
    {
        return $this->belongsTo(MenuItem::class, 'best_seller_id');
    }
}