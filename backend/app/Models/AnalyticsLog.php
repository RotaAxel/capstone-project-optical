<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnalyticsLog extends Model
{
    protected $fillable = [
        'product_id', 'method', 'computed_at', 'result_data',
        'predicted_demand', 'eoq_value', 'rop_value', 'fsn_classification', 'turnover_ratio',
    ];

    protected $casts = [
        'computed_at' => 'date',
        'result_data' => 'array',
        'predicted_demand' => 'decimal:2',
        'eoq_value' => 'decimal:2',
        'rop_value' => 'decimal:2',
        'turnover_ratio' => 'decimal:2',
    ];

    public function product() { return $this->belongsTo(Product::class); }
}
