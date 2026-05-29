<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\PrescriptionController;
use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\ProductCategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\StockMovementController;
use App\Http\Controllers\Api\SaleController;
use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\AlertController;

// ── Public ───────────────────────────────────────────────────────────────────
Route::post('/login', [AuthController::class, 'login']);

// ── Authenticated ─────────────────────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me',      [AuthController::class, 'me']);
    Route::put('/me',      [AuthController::class, 'updateProfile']);

    // Dashboard — all roles
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Optometrists list — all roles (needed for appointment booking)
    Route::get('/optometrists', [UserController::class, 'optometrists']);

    // Alerts — all roles (each sees relevant alerts)
    Route::get('/alerts', [AlertController::class, 'index']);

    // ── Patients — clinic staff only; inventory_staff has no access ──────────
    Route::middleware('role:admin,receptionist,optometrist')->group(function () {
        Route::get('patients',           [PatientController::class, 'index']);
        Route::get('patients/stats',     [PatientController::class, 'stats']);
        Route::get('patients/{patient}', [PatientController::class, 'show']);
    });
    Route::middleware('role:admin,receptionist')->group(function () {
        Route::post('patients',          [PatientController::class, 'store']);
        Route::put('patients/{patient}', [PatientController::class, 'update']);
    });
    Route::middleware('role:admin')->group(function () {
        Route::delete('patients/{patient}', [PatientController::class, 'destroy']);
    });

    // ── Prescriptions — clinic staff only; inventory_staff has no access ──────
    Route::middleware('role:admin,receptionist,optometrist')->group(function () {
        Route::get('prescriptions',                [PrescriptionController::class, 'index']);
        Route::get('prescriptions/stats',          [PrescriptionController::class, 'stats']);
        Route::get('prescriptions/{prescription}', [PrescriptionController::class, 'show']);
    });
    Route::middleware('role:admin,optometrist')->group(function () {
        Route::post('prescriptions',                  [PrescriptionController::class, 'store']);
        Route::put('prescriptions/{prescription}',    [PrescriptionController::class, 'update']);
        Route::delete('prescriptions/{prescription}', [PrescriptionController::class, 'destroy']);
    });

    // ── Appointments — clinic staff only; inventory_staff has no access ───────
    Route::middleware('role:admin,receptionist,optometrist')->group(function () {
        Route::get('appointments',               [AppointmentController::class, 'index']);
        Route::get('appointments/stats',         [AppointmentController::class, 'stats']);
        Route::get('appointments/{appointment}', [AppointmentController::class, 'show']);
    });
    Route::middleware('role:admin,receptionist')->group(function () {
        Route::post('appointments',                  [AppointmentController::class, 'store']);
        Route::delete('appointments/{appointment}',  [AppointmentController::class, 'destroy']);
    });
    Route::middleware('role:admin,receptionist,optometrist')->group(function () {
        Route::put('appointments/{appointment}',     [AppointmentController::class, 'update']);
    });

    // Products read — admin, inventory_staff & receptionist (needed for sales form)
    Route::middleware('role:admin,inventory_staff,receptionist')->group(function () {
        Route::get('products',           [ProductController::class, 'index']);
        Route::get('products/stats',     [ProductController::class, 'stats']);
        Route::get('products/{product}', [ProductController::class, 'show']);
    });

    // ── Inventory write — admin & inventory_staff only ────────────────────────
    Route::middleware('role:admin,inventory_staff')->group(function () {
        Route::apiResource('suppliers',          SupplierController::class);
        Route::apiResource('product-categories', ProductCategoryController::class);
        Route::post('products',                  [ProductController::class, 'store']);
        Route::put('products/{product}',         [ProductController::class, 'update']);
        Route::delete('products/{product}',      [ProductController::class, 'destroy']);
        Route::post('products/{product}/stock-in', [ProductController::class, 'stockIn']);
        Route::post('products/{product}/adjust',   [ProductController::class, 'adjust']);
        Route::get('stock-movements/summary', [StockMovementController::class, 'summary']);
        Route::apiResource('stock-movements', StockMovementController::class)->only(['index', 'show']);
    });

    // ── Sales — admin & receptionist ─────────────────────────────────────────
    Route::middleware('role:admin,receptionist')->group(function () {
        Route::get('sales',            [SaleController::class, 'index']);
        Route::get('sales/stats',      [SaleController::class, 'stats']);
        Route::get('sales/{sale}',     [SaleController::class, 'show']);
        Route::post('sales',           [SaleController::class, 'store']);
        Route::delete('sales/{sale}',  [SaleController::class, 'destroy']);
    });

    // ── Reports — sales reports admin only; inventory reports admin & inventory_staff
    Route::middleware('role:admin')->prefix('reports')->group(function () {
        Route::get('/sales/daily',   [ReportController::class, 'salesDaily']);
        Route::get('/sales/monthly', [ReportController::class, 'salesMonthly']);
    });
    Route::middleware('role:admin,inventory_staff')->prefix('reports')->group(function () {
        Route::get('/inventory',     [ReportController::class, 'inventoryReport']);
        Route::get('/top-products',  [ReportController::class, 'topProducts']);
    });

    // ── Predictive Analytics — admin & inventory_staff ───────────────────────
    Route::middleware('role:admin,inventory_staff')->group(function () {
        Route::post('/analytics/run',    [AnalyticsController::class, 'run']);
        Route::get('/analytics/summary', [AnalyticsController::class, 'summary']);
    });

    // ── User / Account management — admin only ────────────────────────────────
    Route::middleware('role:admin')->group(function () {
        Route::apiResource('users', UserController::class);
    });
});
