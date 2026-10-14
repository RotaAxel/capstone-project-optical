<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'sku', 'name', 'category_id', 'supplier_id', 'description',
        'image_url', 'brand', 'model', 'color', 'size',
        'cost_price', 'selling_price', 'stock_quantity',
        'reorder_point', 'reorder_quantity', 'is_active',
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * SKU prefix of the synthetic ARIMA validation datasets (database/seeders/ArimaDemoSeeder.php).
     * They stay in the Analytics results table, but are kept out of clinic-wide
     * figures: the monthly forecast accuracy card, FSN counts and the inventory report.
     */
    public const DEMO_SKU_PREFIX = 'ARIMA-DEMO-';

    public function scopeExcludingDemo($query)
    {
        return $query->where('sku', 'not like', self::DEMO_SKU_PREFIX . '%');
    }

    /** Subquery of demo product IDs, for filtering sale_items / analytics_logs by product_id. */
    public static function demoIdsQuery(): \Closure
    {
        return fn ($q) => $q->select('id')->from('products')->where('sku', 'like', self::DEMO_SKU_PREFIX . '%');
    }

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
