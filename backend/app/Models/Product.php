<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'sku', 'name', 'category_id', 'supplier_id', 'description',
        'brand', 'model', 'color', 'size',
        'cost_price', 'selling_price', 'stock_quantity',
        'reorder_point', 'reorder_quantity', 'is_active',
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function getIsLowStockAttribute(): bool
    {
        return $this->stock_quantity <= $this->reorder_point;
    }

    public function category() { return $this->belongsTo(ProductCategory::class); }
    public function supplier() { return $this->belongsTo(Supplier::class); }
    public function stockMovements() { return $this->hasMany(StockMovement::class); }
    public function saleItems() { return $this->hasMany(SaleItem::class); }
    public function analyticsLogs() { return $this->hasMany(AnalyticsLog::class); }
}
