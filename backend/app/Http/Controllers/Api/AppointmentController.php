<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    public function index(Request $request)
    {
        $query = Appointment::with(['patient', 'optometrist'])
            ->when($request->patient_id, fn($q) => $q->where('patient_id', $request->patient_id))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->date, fn($q) => $q->whereDate('appointment_date', $request->date));

        return response()->json($query->orderBy('appointment_date')->paginate(15));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id'       => 'required|exists:patients,id',
            'optometrist_id'   => 'required|exists:users,id',
            'appointment_date' => 'required|date',
            'type'             => 'required|in:eye_exam,follow_up,fitting,other',
            'reason'           => 'nullable|string',
            'notes'            => 'nullable|string',
        ]);

        $validated['created_by'] = $request->user()->id;
        $validated['status']     = 'scheduled';

        $appointment = Appointment::create($validated);
        return response()->json($appointment->load(['patient', 'optometrist']), 201);
    }

    public function show(Appointment $appointment)
    {
        return response()->json($appointment->load(['patient', 'optometrist']));
    }

    public function update(Request $request, Appointment $appointment)
    {
        $appointment->update($request->validate([
            'appointment_date' => 'sometimes|date',
            'type'             => 'sometimes|in:eye_exam,follow_up,fitting,other',
            'status'           => 'sometimes|in:scheduled,completed,cancelled,no_show',
            'reason'           => 'nullable|string',
            'notes'            => 'nullable|string',
        ]));

        return response()->json($appointment->fresh(['patient', 'optometrist']));
    }

    public function destroy(Appointment $appointment)
    {
        $appointment->delete();
        return response()->json(['message' => 'Appointment deleted.']);
    }
}
