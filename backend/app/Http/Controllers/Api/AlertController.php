<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Appointment;
use App\Models\Prescription;
use App\Models\AnalyticsLog;
use App\Models\AppSetting;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AlertController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $alerts = match (true) {
            $user->isReceptionist()   => $this->receptionistAlerts(),
            $user->isOptometrist()    => $this->optometristAlerts($user),
            $user->isInventoryStaff() => $this->inventoryAlerts(),
            default                   => array_merge($this->inventoryAlerts(), $this->adminAppointmentAlerts()),
        };

        // Sort: critical → warning → info
        $order = ['critical' => 0, 'warning' => 1, 'info' => 2];
        usort($alerts, fn($a, $b) => $order[$a['severity']] <=> $order[$b['severity']]);

        return response()->json([
            'alerts'         => $alerts,
            'total'          => count($alerts),
            'critical_count' => collect($alerts)->where('severity', 'critical')->count(),
            'warning_count'  => collect($alerts)->where('severity', 'warning')->count(),
            'info_count'     => collect($alerts)->where('severity', 'info')->count(),
            'checked_at'     => now(),
        ]);
    }

    // ── Inventory: out-of-stock, low-stock, predictive restock ───────────────
    private function inventoryAlerts(): array
    {
        $alerts = [];

        // Admin-configurable early-warning multiplier over each product's own
        // reorder_point — 100% reproduces the original exact-at-ROP behavior.
        // Out-of-stock alerts below are never affected by this setting.
        $sensitivity = AppSetting::current()->low_stock_sensitivity_percent / 100.0;

        $outOfStock = Product::where('stock_quantity', 0)->where('is_active', true)->get();
        foreach ($outOfStock as $p) {
            $alerts[] = [
                'id'           => 'oos-' . $p->id,
                'type'         => 'out_of_stock',
                'severity'     => 'critical',
                'title'        => 'Out of Stock',
                'message'      => "{$p->name} ({$p->sku}) has 0 units remaining.",
                'product'      => ['id' => $p->id, 'name' => $p->name, 'sku' => $p->sku, 'stock' => $p->stock_quantity],
                'route'        => '/inventory',
                'highlight_id' => $p->id,
                'search_hint'  => $p->sku,
                'created_at'   => now(),
            ];
        }

        $lowStock = Product::where('stock_quantity', '>', 0)
            ->whereRaw('stock_quantity <= reorder_point * ?', [$sensitivity])
            ->where('is_active', true)
            ->get();
        foreach ($lowStock as $p) {
            $alerts[] = [
                'id'           => 'low-' . $p->id,
                'type'         => 'low_stock',
                'severity'     => 'warning',
                'title'        => 'Low Stock',
                'message'      => "{$p->name} ({$p->sku}) has only {$p->stock_quantity} units left (ROP: {$p->reorder_point}).",
                'product'      => ['id' => $p->id, 'name' => $p->name, 'sku' => $p->sku, 'stock' => $p->stock_quantity, 'rop' => $p->reorder_point],
                'route'        => '/inventory',
                'highlight_id' => $p->id,
                'search_hint'  => $p->sku,
                'created_at'   => now(),
            ];
        }

        $predictive = AnalyticsLog::with('product')
            ->whereIn('id', function ($q) {
                $q->selectRaw('MAX(id)')->from('analytics_logs')->groupBy('product_id');
            })
            ->whereHas('product', fn($q) => $q->where('is_active', true))
            ->get()
            ->filter(function ($log) use ($sensitivity) {
                $avgDaily = $log->result_data['avg_daily'] ?? 0;
                return $log->product
                    && $avgDaily > 0  // only products with real measured demand
                    && $log->product->stock_quantity > ($log->product->reorder_point * $sensitivity)  // above the same effective threshold as "low stock" (not already alerting there)
                    && $log->rop_value > 0
                    && (($log->product->stock_quantity - $log->rop_value) / $avgDaily) <= 14;  // hits ROP within 14 days
            });

        foreach ($predictive as $log) {
            $p        = $log->product;
            $avgDaily = $log->result_data['avg_daily'];
            $days     = max(1, (int) round(($p->stock_quantity - $log->rop_value) / $avgDaily));
            $alerts[] = [
                'id'           => 'pred-' . $p->id,
                'type'         => 'predictive_restock',
                'severity'     => 'info',
                'title'        => 'Restock Soon',
                'message'      => "{$p->name} will reach reorder point in ~{$days} days based on demand forecast.",
                'product'      => ['id' => $p->id, 'name' => $p->name, 'sku' => $p->sku, 'stock' => $p->stock_quantity, 'predicted_demand' => $log->predicted_demand, 'rop' => $log->rop_value],
                'route'        => '/inventory',
                'highlight_id' => $p->id,
                'search_hint'  => $p->sku,
                'created_at'   => now(),
            ];
        }

        return $alerts;
    }

    // ── Admin: this month's total appointments across all doctors ────────────
    private function adminAppointmentAlerts(): array
    {
        $alerts = [];
        [$year, $month]         = [now()->year, now()->month];
        [$nextYear, $nextMonth] = $this->nextMonth();

        $count = Appointment::where('appointment_year', $year)->where('appointment_month', $month)
            ->where('status', 'scheduled')->count();

        if ($count > 0) {
            $alerts[] = $this->apptAlert('appt-this-month', 'info',
                "This Month's Appointments", "{$count} appointment(s) scheduled this month.");
        }

        $nextCount = Appointment::where('appointment_year', $nextYear)->where('appointment_month', $nextMonth)
            ->where('status', 'scheduled')->count();

        if ($nextCount > 0) {
            $alerts[] = $this->apptAlert('appt-next-month', 'info',
                "Next Month's Appointments", "{$nextCount} appointment(s) scheduled for next month.");
        }

        return $alerts;
    }

    // ── Receptionist: appointment-focused alerts ──────────────────────────────
    private function receptionistAlerts(): array
    {
        $alerts = [];
        [$year, $month]         = [now()->year, now()->month];
        [$nextYear, $nextMonth] = $this->nextMonth();

        $scheduled = Appointment::where('appointment_year', $year)->where('appointment_month', $month)
            ->where('status', 'scheduled')->count();

        if ($scheduled > 0) {
            $alerts[] = $this->apptAlert('appt-this-month', 'info',
                "This Month's Appointments", "{$scheduled} appointment(s) still scheduled this month.");
        }

        // Appointments still marked "scheduled" for a month that has already passed —
        // the month-only equivalent of a no-show check (there's no time-of-day to compare against).
        $overdueCount = Appointment::where('status', 'scheduled')
            ->where(fn($q) => $q->where('appointment_year', '<', $year)
                ->orWhere(fn($q2) => $q2->where('appointment_year', $year)->where('appointment_month', '<', $month)))
            ->count();

        if ($overdueCount > 0) {
            $alerts[] = $this->apptAlert('appt-overdue', 'warning',
                'Unresolved Past Appointments',
                "{$overdueCount} appointment(s) from an earlier month are still marked \"scheduled\" — mark them completed, cancelled, or no-show.");
        }

        $nextCount = Appointment::where('appointment_year', $nextYear)->where('appointment_month', $nextMonth)
            ->where('status', 'scheduled')->count();

        if ($nextCount > 0) {
            $alerts[] = $this->apptAlert('appt-next-month', 'info',
                "Next Month's Schedule", "{$nextCount} appointment(s) scheduled for next month.");
        }

        return $alerts;
    }

    // ── Optometrist: their own appointments + expiring prescriptions ──────────
    private function optometristAlerts($user): array
    {
        $alerts = [];
        $today  = Carbon::today();
        [$year, $month] = [now()->year, now()->month];

        // Their appointments this month
        $myThisMonth = Appointment::where('appointment_year', $year)->where('appointment_month', $month)
            ->where('optometrist_id', $user->id)->where('status', 'scheduled')->count();

        if ($myThisMonth > 0) {
            $alerts[] = $this->apptAlert('my-appt-this-month', 'info',
                'Your Appointments This Month', "You have {$myThisMonth} appointment(s) scheduled this month.");
        }

        // Their own appointments left "scheduled" for a month that's already passed
        $myOverdue = Appointment::where('status', 'scheduled')
            ->where('optometrist_id', $user->id)
            ->where(fn($q) => $q->where('appointment_year', '<', $year)
                ->orWhere(fn($q2) => $q2->where('appointment_year', $year)->where('appointment_month', '<', $month)))
            ->count();

        if ($myOverdue > 0) {
            $alerts[] = $this->apptAlert('my-appt-overdue', 'warning',
                'Unresolved Past Appointments',
                "{$myOverdue} of your appointment(s) from an earlier month are still marked \"scheduled\".");
        }

        // Prescriptions expiring in the next 7 days (written by this optometrist)
        $expiringSoon = Prescription::with('patient')
            ->where('optometrist_id', $user->id)
            ->whereBetween('valid_until', [$today, $today->copy()->addDays(7)])
            ->get();

        foreach ($expiringSoon as $rx) {
            $name     = $rx->patient ? $rx->patient->first_name . ' ' . $rx->patient->last_name : 'Unknown';
            $daysLeft = $today->diffInDays(Carbon::parse($rx->valid_until));
            $severity = $daysLeft <= 2 ? 'warning' : 'info';
            $alerts[] = [
                'id'           => 'rx-expiring-' . $rx->id,
                'type'         => 'prescription_expiring',
                'severity'     => $severity,
                'title'        => 'Prescription Expiring Soon',
                'message'      => "{$name}'s prescription expires in {$daysLeft} day(s).",
                'route'        => '/prescriptions',
                'highlight_id' => $rx->id,
                'search_hint'  => null,
                'created_at'   => now(),
            ];
        }

        return $alerts;
    }

    /** [year, month] of the calendar month right after the current one. */
    private function nextMonth(): array
    {
        $next = now()->copy()->addMonthNoOverflow();
        return [$next->year, $next->month];
    }

    private function apptAlert(string $id, string $severity, string $title, string $message): array
    {
        return [
            'id'           => $id,
            'type'         => 'appointments',
            'severity'     => $severity,
            'title'        => $title,
            'message'      => $message,
            'route'        => '/appointments',
            'highlight_id' => null,
            'search_hint'  => null,
            'created_at'   => now(),
        ];
    }
}
