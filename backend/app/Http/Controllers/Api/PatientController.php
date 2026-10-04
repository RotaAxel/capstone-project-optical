<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PatientController extends Controller
{
    public function index(Request $request)
    {
        $query = Patient::with('createdBy')
            ->withCount(['appointments', 'prescriptions', 'sales'])
            // "last_visit" ("YYYY-MM") is the most recent of three independent signals
            // that the patient was actually at the clinic: a completed appointment, a
            // prescription's exam date, or a purchase. A completed appointment alone
            // isn't enough — a prescription or a sale is just as real evidence of a
            // visit, and appointment status-keeping doesn't always line up with them
            // (e.g. a patient can have a written prescription with no appointment ever
            // marked "completed"). '0000-00' is a sentinel so GREATEST() ignores a
            // signal that doesn't exist for this patient; NULLIF turns it back to null.
            ->addSelect(DB::raw("(
                SELECT NULLIF(GREATEST(
                    COALESCE((SELECT DATE_FORMAT(a.appointment_date, '%Y-%m')
                               FROM appointments a
                               WHERE a.patient_id = patients.id AND a.status = 'completed' AND a.deleted_at IS NULL
                               ORDER BY a.appointment_date DESC LIMIT 1), '0000-00'),
                    COALESCE((SELECT DATE_FORMAT(MAX(r.exam_date), '%Y-%m')
                               FROM prescriptions r
                               WHERE r.patient_id = patients.id AND r.deleted_at IS NULL), '0000-00'),
                    COALESCE((SELECT DATE_FORMAT(MAX(s.created_at), '%Y-%m')
                               FROM sales s
                               WHERE s.patient_id = patients.id AND s.deleted_at IS NULL), '0000-00')
                ), '0000-00')
            ) as last_visit"))
            ->addSelect([
                'latest_rx_date' => \App\Models\Prescription::select('exam_date')
                    ->whereColumn('patient_id', 'patients.id')
                    ->orderByDesc('exam_date')
                    ->limit(1),
            ])
            ->when($request->search, fn($q) => $q->where(function ($q) use ($request) {
                // The extra CONCAT check lets a typed full name (e.g. "Aileen Salazar")
                // match even though first_name/last_name are two separate columns —
                // without it, a full-name search can never match either column alone.
                $q->where('first_name', 'like', "%{$request->search}%")
                  ->orWhere('last_name', 'like', "%{$request->search}%")
                  ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$request->search}%"])
                  ->orWhere('patient_code', 'like', "%{$request->search}%")
                  ->orWhere('phone', 'like', "%{$request->search}%");
            }));

        $perPage = min((int) ($request->per_page ?? 15), 100);
        return response()->json($query->latest()->paginate($perPage));
    }

    public function stats()
    {
        $stats = DB::table('patients')
            ->whereNull('deleted_at')
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN gender = 'male'   THEN 1 ELSE 0 END) as male_count,
                SUM(CASE WHEN gender = 'female' THEN 1 ELSE 0 END) as female_count,
                SUM(CASE WHEN created_at >= ?   THEN 1 ELSE 0 END) as new_this_month
            ", [now()->startOfMonth()])
            ->first();

        return response()->json($stats);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name'              => 'required|string|max:100',
            'last_name'               => 'required|string|max:100',
            'date_of_birth'           => 'required|date',
            'gender'                  => 'required|in:male,female,other',
            'phone'                   => 'nullable|string|max:20',
            'email'                   => 'nullable|email',
            'address'                 => 'nullable|string',
            'emergency_contact_name'  => 'nullable|string',
            'emergency_contact_phone' => 'nullable|string',
            'medical_history'         => 'nullable|string',
        ]);

        $validated['patient_code'] = 'TEMP-' . strtoupper(Str::uuid());
        $validated['created_by']   = $request->user()->id;

        $patient = Patient::create($validated);
        $patient->patient_code = 'PAT-' . str_pad($patient->id, 6, '0', STR_PAD_LEFT);
        $patient->save();

        return response()->json($patient->load('createdBy'), 201);
    }

    public function show(Patient $patient)
    {
        return response()->json($patient->load([
            'createdBy',
            'prescriptions' => fn($q) => $q->with('optometrist')->latest()->limit(100),
            'appointments'  => fn($q) => $q->with('optometrist')->latest()->limit(100),
            'sales'         => fn($q) => $q->with(['cashier', 'items.product'])->latest()->limit(50),
        ]));
    }

    public function update(Request $request, Patient $patient)
    {
        $validated = $request->validate([
            'first_name'              => 'sometimes|string|max:100',
            'last_name'               => 'sometimes|string|max:100',
            'date_of_birth'           => 'sometimes|date',
            'gender'                  => 'sometimes|in:male,female,other',
            'phone'                   => 'nullable|string|max:20',
            'email'                   => 'nullable|email',
            'address'                 => 'nullable|string',
            'emergency_contact_name'  => 'nullable|string',
            'emergency_contact_phone' => 'nullable|string',
            'medical_history'         => 'nullable|string',
        ]);

        $patient->update($validated);

        return response()->json($patient->fresh('createdBy'));
    }

    public function destroy(Patient $patient)
    {
        if ($patient->sales()->exists() || $patient->prescriptions()->exists() || $patient->appointments()->exists()) {
            return response()->json([
                'message' => 'Cannot delete a patient who has linked sales, prescriptions, or appointments.',
            ], 409);
        }

        $patient->delete();
        return response()->json(null, 204);
    }
}
