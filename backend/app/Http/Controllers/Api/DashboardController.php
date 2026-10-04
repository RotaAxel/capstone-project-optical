<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Appointment;
use App\Models\Prescription;
use App\Models\StockMovement;
use App\Models\AnalyticsLog;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user  = $request->user();
        $today = now()->toDateString();

        return match (true) {
            $user->isReceptionist()   => response()->json($this->receptionistData($today)),
            $user->isOptometrist()    => response()->json($this->optometristData($user, $today)),
            $user->isInventoryStaff() => response()->json($this->inventoryData($today)),
            default                   => response()->json($this->adminData($today)),
        };
    }

    // ── Admin ─────────────────────────────────────────────────────────────────
    private function adminData(string $today): array
    {
        $monthStart = now()->startOfMonth()->toDateString();

        $stats = [
            'total_patients'     => Patient::count(),
            'new_patients_today' => Patient::whereDate('created_at', $today)->count(),
            'appointments_today' => Appointment::whereDate('appointment_date', $today)->count(),
            'low_stock_count'    => Product::whereColumn('stock_quantity', '<=', 'reorder_point')->count(),
            'sales_today'        => Sale::whereDate('created_at', $today)->where('status', 'completed')->sum('total_amount'),
            'sales_this_month'   => Sale::whereDate('created_at', '>=', $monthStart)->where('status', 'completed')->sum('total_amount'),
            'transactions_today' => Sale::whereDate('created_at', $today)->where('status', 'completed')->count(),
        ];

        $topSelling = SaleItem::selectRaw('product_id, SUM(quantity) as total_sold')
            ->whereHas('sale', fn ($q) => $q->where('status', 'completed')
                ->whereYear('created_at', now()->year)
                ->whereMonth('created_at', now()->month))
            ->groupBy('product_id')
            ->orderByDesc('total_sold')
            ->limit(5)
            ->with('product:id,name')
            ->get()
            ->map(fn ($item) => [
                'name'           => $item->product?->name ?? 'Unknown',
                'total_sold'     => (int) $item->total_sold,
                'change_percent' => 0,
            ]);

        return [
            'role'                 => 'admin',
            'stats'                => $stats,
            'top_selling_products' => $topSelling,
            'low_stock_products'   => Product::with('category')->whereColumn('stock_quantity', '<=', 'reorder_point')->orderBy('stock_quantity')->limit(5)->get(),
            'recent_sales'         => Sale::with(['patient', 'cashier', 'items'])->latest()->limit(5)->get(),
            'upcoming_appointments'=> Appointment::with(['patient', 'optometrist'])->where('appointment_date', '>=', now())->where('status', 'scheduled')->orderBy('appointment_date')->limit(5)->get(),
            'monthly_sales'        => Sale::selectRaw('MONTH(created_at) as month, SUM(total_amount) as total')->whereYear('created_at', now()->year)->where('status', 'completed')->groupBy('month')->orderBy('month')->get(),
        ];
    }

    // ── Receptionist ─────────────────────────────────────────────────────────
    private function receptionistData(string $today): array
    {
        $todayAppts = Appointment::with(['patient', 'optometrist'])
            ->whereDate('appointment_date', $today)
            ->orderBy('appointment_date')
            ->get();

        $stats = [
            'appointments_today' => $todayAppts->count(),
            'scheduled_today'    => $todayAppts->where('status', 'scheduled')->count(),
            'completed_today'    => $todayAppts->where('status', 'completed')->count(),
            'cancelled_today'    => $todayAppts->where('status', 'cancelled')->count(),
            'new_patients_today' => Patient::whereDate('created_at', $today)->count(),
            'total_patients'     => Patient::count(),
        ];

        return [
            'role'               => 'receptionist',
            'stats'              => $stats,
            'today_appointments' => $todayAppts,
            'recent_patients'    => Patient::with('createdBy')->latest()->limit(8)->get(),
            'upcoming_appointments' => Appointment::with(['patient', 'optometrist'])
                ->where('appointment_date', '>', now())
                ->where('status', 'scheduled')
                ->orderBy('appointment_date')
                ->limit(5)
                ->get(),
        ];
    }

    // ── Optometrist ───────────────────────────────────────────────────────────
    private function optometristData(\App\Models\User $user, string $today): array
    {
        $myTodayAppts = Appointment::with('patient')
            ->whereDate('appointment_date', $today)
            ->where('optometrist_id', $user->id)
            ->orderBy('appointment_date')
            ->get();

        $stats = [
            'my_appointments_today' => $myTodayAppts->count(),
            'completed_today'       => $myTodayAppts->where('status', 'completed')->count(),
            'scheduled_today'       => $myTodayAppts->where('status', 'scheduled')->count(),
            'prescriptions_this_week' => Prescription::where('optometrist_id', $user->id)
                ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
                ->count(),
        ];

        return [
            'role'               => 'optometrist',
            'stats'              => $stats,
            'today_appointments' => $myTodayAppts,
            'recent_prescriptions' => Prescription::with('patient')
                ->where('optometrist_id', $user->id)
                ->latest()
                ->limit(8)
                ->get(),
            'upcoming_appointments' => Appointment::with('patient')
                ->where('optometrist_id', $user->id)
                ->where('appointment_date', '>', now())
                ->where('status', 'scheduled')
                ->orderBy('appointment_date')
                ->limit(5)
                ->get(),
        ];
    }

    // ── Inventory Staff ───────────────────────────────────────────────────────
    private function inventoryData(string $today): array
    {
        $outOfStock = Product::where('stock_quantity', 0)->where('is_active', true)->count();
        $lowStock   = Product::where('stock_quantity', '>', 0)->whereColumn('stock_quantity', '<=', 'reorder_point')->where('is_active', true)->count();

        $stats = [
            'total_products'   => Product::where('is_active', true)->count(),
            'out_of_stock'     => $outOfStock,
            'low_stock_count'  => $lowStock,
            'total_stock_value'=> Product::where('is_active', true)->selectRaw('SUM(stock_quantity * cost_price) as val')->value('val') ?? 0,
            'stock_ins_today'  => StockMovement::where('type', 'stock_in')->whereDate('created_at', $today)->count(),
        ];

        $latest = AnalyticsLog::whereIn('id', function ($q) {
            $q->selectRaw('MAX(id)')->from('analytics_logs')->groupBy('product_id');
        })->get();

        return [
            'role'  => 'inventory_staff',
            'stats' => $stats,
            'fsn_summary' => [
                'fast'       => $latest->where('fsn_classification', 'fast')->count(),
                'slow'       => $latest->where('fsn_classification', 'slow')->count(),
                'non_moving' => $latest->where('fsn_classification', 'non_moving')->count(),
            ],
            'low_stock_products'   => Product::with('category')->where('is_active', true)->whereColumn('stock_quantity', '<=', 'reorder_point')->orderBy('stock_quantity')->limit(20)->get(),
            'recent_stock_movements' => StockMovement::with(['product', 'user'])->latest()->limit(10)->get(),
        ];
    }
}
