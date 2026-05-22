<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PatientController extends Controller
{
    public function index(Request $request)
    {
        $query = Patient::with('createdBy')
            ->when($request->search, fn($q) => $q->where(function ($q) use ($request) {
                $q->where('first_name', 'like', "%{$request->search}%")
                  ->orWhere('last_name', 'like', "%{$request->search}%")
                  ->orWhere('patient_code', 'like', "%{$request->search}%")
                  ->orWhere('phone', 'like', "%{$request->search}%");
            }));

        $perPage = min((int) ($request->per_page ?? 15), 500);
        return response()->json($query->latest()->paginate($perPage));
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

        $validated['patient_code'] = 'PAT-' . strtoupper(Str::random(6));
        $validated['created_by']   = $request->user()->id;

        $patient = Patient::create($validated);

        return response()->json($patient->load('createdBy'), 201);
    }

    public function show(Patient $patient)
    {
        return response()->json($patient->load([
            'createdBy',
            'prescriptions.optometrist',
            'appointments.optometrist',
            'sales.cashier',
            'sales.items.product',
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
        $patient->delete();
        return response()->json(['message' => 'Patient deleted.']);
    }
}
