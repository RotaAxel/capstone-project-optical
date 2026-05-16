<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Appointment;
use App\Models\Prescription;
use App\Models\AnalyticsLog;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AlertController extends Controller
{
    public function index(Request $request)
    {
        $user   = $request->user();
        $role   = $user->role;

        $alerts = match ($role) {
            'receptionist'    => $this->receptionistAlerts(),
            'optometrist'     => $this->optometristAlerts($user),
            'inventory_staff' => $this->inventoryAlerts(),
            default           => array_merge($this->inventoryAlerts(), $this->adminAppointmentAlerts()),
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

        $outOfStock = Product::where('stock_quantity', 0)->where('is_active', true)->get();
        foreach ($outOfStock as $p) {
            $alerts[] = [
                'id'         => 'oos-' . $p->id,
                'type'       => 'out_of_stock',
                'severity'   => 'critical',
                'title'      => 'Out of Stock',
                'message'    => "{$p->name} ({$p->sku}) has 0 units remaining.",
                'product'    => ['id' => $p->id, 'name' => $p->name, 'sku' => $p->sku, 'stock' => $p->stock_quantity],
                'created_at' => now(),
            ];
        }

        $lowStock = Product::where('stock_quantity', '>', 0)
            ->whereColumn('stock_quantity', '<=', 'reorder_point')
            ->where('is_active', true)
            ->get();
        foreach ($lowStock as $p) {
            $alerts[] = [
                'id'         => 'low-' . $p->id,
                'type'       => 'low_stock',
                'severity'   => 'warning',
                'title'      => 'Low Stock',
                'message'    => "{$p->name} ({$p->sku}) has only {$p->stock_quantity} units left (ROP: {$p->reorder_point}).",
                'product'    => ['id' => $p->id, 'name' => $p->name, 'sku' => $p->sku, 'stock' => $p->stock_quantity, 'rop' => $p->reorder_point],
                'created_at' => now(),
            ];
        }

        $predictive = AnalyticsLog::with('product')
            ->whereIn('id', function ($q) {
                $q->selectRaw('MAX(id)')->from('analytics_logs')->groupBy('product_id');
            })
            ->whereHas('product', fn($q) => $q->where('is_active', true))
            ->get()
            ->filter(fn($log) =>
                $log->product &&
                $log->product->stock_quantity > $log->product->reorder_point &&
                $log->rop_value > 0 &&
                $log->product->stock_quantity <= ($log->rop_value * 1.5)
            );

        foreach ($predictive as $log) {
            $p    = $log->product;
            $days = max(1, round(($p->stock_quantity - $log->rop_value) / max(0.01, $log->result_data['avg_daily'] ?? 0.01)));
            $alerts[] = [
                'id'         => 'pred-' . $p->id,
                'type'       => 'predictive_restock',
                'severity'   => 'info',
                'title'      => 'Restock Soon',
                'message'    => "{$p->name} will reach reorder point in ~{$days} days based on demand forecast.",
                'product'    => ['id' => $p->id, 'name' => $p->name, 'sku' => $p->sku, 'stock' => $p->stock_quantity, 'predicted_demand' => $log->predicted_demand, 'rop' => $log->rop_value],
                'created_at' => now(),
            ];
        }

        return $alerts;
    }

    // ── Admin: today's total appointments across all doctors ─────────────────
    private function adminAppointmentAlerts(): array
    {
        $alerts = [];

        $count = Appointment::whereDate('appointment_date', Carbon::today())
            ->where('status', 'scheduled')
            ->count();

        if ($count > 0) {
            $alerts[] = [
                'id'         => 'appt-today',
                'type'       => 'appointments',
                'severity'   => 'info',
                'title'      => 'Today\'s Appointments',
                'message'    => "{$count} appointment(s) scheduled for today.",
                'created_at' => now(),
            ];
        }

        // Unconfirmed appointments for tomorrow
        $tomorrow = Carbon::tomorrow()->toDateString();
        $tmrCount = Appointment::whereDate('appointment_date', $tomorrow)
            ->where('status', 'scheduled')
            ->count();

        if ($tmrCount > 0) {
            $alerts[] = [
                'id'         => 'appt-tomorrow',
                'type'       => 'appointments',
                'severity'   => 'info',
                'title'      => 'Tomorrow\'s Appointments',
                'message'    => "{$tmrCount} appointment(s) scheduled for tomorrow.",
                'created_at' => now(),
            ];
        }

        return $alerts;
    }

    // ── Receptionist: appointment-focused alerts ──────────────────────────────
    private function receptionistAlerts(): array
    {
        $alerts = [];
        $today  = Carbon::today();

        $scheduled = Appointment::whereDate('appointment_date', $today)
            ->where('status', 'scheduled')
            ->count();

        if ($scheduled > 0) {
            $alerts[] = [
                'id'         => 'appt-today',
                'type'       => 'appointments',
                'severity'   => 'info',
                'title'      => 'Today\'s Appointments',
                'message'    => "{$scheduled} appointment(s) still scheduled today.",
                'created_at' => now(),
            ];
        }

        // Appointments in the next hour (urgent)
        $nextHour = Appointment::with('patient')
            ->whereBetween('appointment_date', [now(), now()->addHour()])
            ->where('status', 'scheduled')
            ->get();

        foreach ($nextHour as $appt) {
            $name = $appt->patient ? $appt->patient->first_name . ' ' . $appt->patient->last_name : 'Unknown';
            $time = Carbon::parse($appt->appointment_date)->format('g:i A');
            $alerts[] = [
                'id'         => 'appt-soon-' . $appt->id,
                'type'       => 'appointments',
                'severity'   => 'warning',
                'title'      => 'Appointment Starting Soon',
                'message'    => "{$name} has an appointment at {$time}.",
                'created_at' => now(),
            ];
        }

        // No-show check: scheduled appointments past their time (over 30 min ago)
        $noShows = Appointment::with('patient')
            ->where('appointment_date', '<', now()->subMinutes(30))
            ->whereDate('appointment_date', $today)
            ->where('status', 'scheduled')
            ->get();

        foreach ($noShows as $appt) {
            $name = $appt->patient ? $appt->patient->first_name . ' ' . $appt->patient->last_name : 'Unknown';
            $time = Carbon::parse($appt->appointment_date)->format('g:i A');
            $alerts[] = [
                'id'         => 'noshow-' . $appt->id,
                'type'       => 'no_show',
                'severity'   => 'warning',
                'title'      => 'Possible No-Show',
                'message'    => "{$name} was scheduled at {$time} but has not been marked as completed.",
                'created_at' => now(),
            ];
        }

        // Tomorrow's appointments count
        $tomorrow = Appointment::whereDate('appointment_date', Carbon::tomorrow())
            ->where('status', 'scheduled')
            ->count();

        if ($tomorrow > 0) {
            $alerts[] = [
                'id'         => 'appt-tomorrow',
                'type'       => 'appointments',
                'severity'   => 'info',
                'title'      => 'Tomorrow\'s Schedule',
                'message'    => "{$tomorrow} appointment(s) scheduled for tomorrow.",
                'created_at' => now(),
            ];
        }

        return $alerts;
    }

    // ── Optometrist: their own appointments + expiring prescriptions ──────────
    private function optometristAlerts($user): array
    {
        $alerts = [];
        $today  = Carbon::today();

        // Their appointments today
        $myToday = Appointment::whereDate('appointment_date', $today)
            ->where('optometrist_id', $user->id)
            ->where('status', 'scheduled')
            ->count();

        if ($myToday > 0) {
            $alerts[] = [
                'id'         => 'my-appt-today',
                'type'       => 'appointments',
                'severity'   => 'info',
                'title'      => 'Your Appointments Today',
                'message'    => "You have {$myToday} appointment(s) scheduled for today.",
                'created_at' => now(),
            ];
        }

        // Their appointment starting within the next hour
        $nextHour = Appointment::with('patient')
            ->whereBetween('appointment_date', [now(), now()->addHour()])
            ->where('optometrist_id', $user->id)
            ->where('status', 'scheduled')
            ->get();

        foreach ($nextHour as $appt) {
            $name = $appt->patient ? $appt->patient->first_name . ' ' . $appt->patient->last_name : 'Unknown';
            $time = Carbon::parse($appt->appointment_date)->format('g:i A');
            $alerts[] = [
                'id'         => 'my-appt-soon-' . $appt->id,
                'type'       => 'appointments',
                'severity'   => 'warning',
                'title'      => 'Patient Arriving Soon',
                'message'    => "{$name} is scheduled at {$time}.",
                'created_at' => now(),
            ];
        }

        // Prescriptions expiring in the next 7 days (written by this optometrist)
        $expiringSoon = Prescription::with('patient')
            ->where('optometrist_id', $user->id)
            ->whereBetween('valid_until', [$today, $today->copy()->addDays(7)])
            ->get();

        foreach ($expiringSoon as $rx) {
            $name    = $rx->patient ? $rx->patient->first_name . ' ' . $rx->patient->last_name : 'Unknown';
            $daysLeft = $today->diffInDays(Carbon::parse($rx->valid_until));
            $severity = $daysLeft <= 2 ? 'warning' : 'info';
            $alerts[] = [
                'id'         => 'rx-expiring-' . $rx->id,
                'type'       => 'prescription_expiring',
                'severity'   => $severity,
                'title'      => 'Prescription Expiring Soon',
                'message'    => "{$name}'s prescription expires in {$daysLeft} day(s).",
                'created_at' => now(),
            ];
        }

        // Overdue appointments (past time, still scheduled, their patients)
        $overdue = Appointment::with('patient')
            ->where('appointment_date', '<', now()->subMinutes(30))
            ->whereDate('appointment_date', $today)
            ->where('optometrist_id', $user->id)
            ->where('status', 'scheduled')
            ->get();

        foreach ($overdue as $appt) {
            $name = $appt->patient ? $appt->patient->first_name . ' ' . $appt->patient->last_name : 'Unknown';
            $time = Carbon::parse($appt->appointment_date)->format('g:i A');
            $alerts[] = [
                'id'         => 'my-overdue-' . $appt->id,
                'type'       => 'overdue_appointment',
                'severity'   => 'warning',
                'title'      => 'Appointment Not Completed',
                'message'    => "{$name}'s {$time} appointment has not been marked completed.",
                'created_at' => now(),
            ];
        }

        return $alerts;
    }
}
