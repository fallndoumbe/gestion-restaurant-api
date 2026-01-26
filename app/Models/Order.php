<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'table_id',
        'server_id',
        'status',
        'subtotal',
        'tax',
        'total',
        'payment_method',
        'payment_status',
        'notes'
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function server()
{
    // On lie "server" au modèle User via la colonne server_id
    return $this->belongsTo(User::class, 'server_id');
}

    public function table(): BelongsTo
    {
        return $this->belongsTo(Table::class, 'table_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function calculateTotals(): self
    {
        $subtotal = $this->items->sum(fn($item) => $item->unit_price * $item->quantity);
        $taxRate = 0.18;
        $tax = $subtotal * $taxRate;
        $total = $subtotal + $tax;

        $this->update([
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => $total,
        ]);

        return $this;
    }
}
