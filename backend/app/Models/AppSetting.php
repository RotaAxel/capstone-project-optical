<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Singleton settings row — there is always exactly one. Use AppSetting::current()
 * rather than querying the table directly; it self-heals (creates the default
 * row) if one doesn't exist yet, so no separate seeder step is required.
 */
class AppSetting extends Model
{
    protected $fillable = [
        'low_stock_sensitivity_percent',
        'fsn_fast_threshold_percent',
        'fsn_nonmoving_threshold_percent',
        'fsn_dead_stock_weeks',
        'fsn_fast_min_daily_avg',
    ];

    protected function casts(): array
    {
        return [
            'low_stock_sensitivity_percent'   => 'integer',
            'fsn_fast_threshold_percent'      => 'integer',
            'fsn_nonmoving_threshold_percent' => 'integer',
            'fsn_dead_stock_weeks'            => 'integer',
            'fsn_fast_min_daily_avg'          => 'float',
        ];
    }

    public static function current(): self
    {
        // Pass defaults explicitly rather than relying on the DB column
        // defaults alone — firstOrCreate() would otherwise hand back an
        // in-memory instance with nulls for every unspecified attribute on
        // the exact call that inserts the row (it doesn't re-fetch after
        // insert), even though the DB row itself is populated correctly.
        return static::firstOrCreate([], [
            'low_stock_sensitivity_percent'   => 100,
            'fsn_fast_threshold_percent'      => 50,
            'fsn_nonmoving_threshold_percent' => 10,
            'fsn_dead_stock_weeks'            => 26,
            'fsn_fast_min_daily_avg'          => 1.00,
        ]);
    }
}
