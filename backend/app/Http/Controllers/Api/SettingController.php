<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function show()
    {
        return response()->json(AppSetting::current());
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            // 100 = today's original behavior (alert exactly at the reorder
            // point). Capped at 500 — beyond that the alert would fire so
            // far ahead of the reorder point it stops being a "low stock"
            // warning and starts flagging almost-full products.
            'low_stock_sensitivity_percent'   => 'required|integer|min:50|max:500',

            // Activity-ratio percentages that bound the "slow" band.
            // fast must stay strictly above nonmoving or every product
            // would collapse into a single bucket.
            'fsn_fast_threshold_percent'      => 'required|integer|min:1|max:100',
            'fsn_nonmoving_threshold_percent' => 'required|integer|min:0|max:99|lt:fsn_fast_threshold_percent',

            'fsn_dead_stock_weeks'            => 'required|integer|min:1|max:208',
            'fsn_fast_min_daily_avg'          => 'required|numeric|min:0|max:1000',
        ]);

        $settings = AppSetting::current();
        $settings->update($validated);

        return response()->json($settings->fresh());
    }
}
