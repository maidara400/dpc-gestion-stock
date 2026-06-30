<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'user_id',
        'supplier_id',
        'type',
        'quantity',
        'location_from',
        'location_to',
        'notes',
        'stock_depot_after',
        'stock_boutique_after',
        'amount_due',
        'payment_due_date',
        'payment_status',
        'paid_at',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'stock_depot_after' => 'integer',
        'stock_boutique_after' => 'integer',
        'amount_due' => 'decimal:2',
        'payment_due_date' => 'date',
        'paid_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function isOverdue(): bool
    {
        return $this->payment_status === 'pending'
            && $this->payment_due_date
            && $this->payment_due_date->isPast();
    }
}
