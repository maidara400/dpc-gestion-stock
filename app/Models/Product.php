<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'sku',
        'category_id',
        'supplier_id',
        'stock_depot',
        'stock_boutique',
        'alert_threshold_depot',
        'alert_threshold_boutique',
        'purchase_price',
        'sale_price',
        'active',
        'image_path',
    ];

    protected $casts = [
        'stock_depot' => 'integer',
        'stock_boutique' => 'integer',
        'alert_threshold_depot' => 'integer',
        'alert_threshold_boutique' => 'integer',
        'purchase_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'active' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function isDepotAlerted(): bool
    {
        return $this->stock_depot <= $this->alert_threshold_depot;
    }

    public function isBoutiqueAlerted(): bool
    {
        return $this->stock_boutique <= $this->alert_threshold_boutique;
    }
}
