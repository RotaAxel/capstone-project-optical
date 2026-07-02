<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AppointmentController extends Controller
{
    public function index(Request $request)
    {
        $query = Appointment::with(['patient', 'optometrist'])
            ->when($request->patient_id, fn($q) => $q->where('patient_id', $request->patient_id))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->date, fn($q) => $q->whereDate('appointment_date', $request->date));

        if ($request->highlight_id) {
            $target = Appointment::find($request->highlight_id);
            if ($target) {
                // Stable sort: appointment_date ASC, id ASC — count rows that come before target
                $before = (clone $query)->where(function ($q) use ($target) {
                    $q->where('appointment_date', '<', $target->appointment_date)
                      ->orWhere(fn($q2) => $q2->where('appointment_date', $target->appointment_date)->where('id', '<', $target->id));
                })->count();
                $page = (int) floor($before / 15) + 1;
                return response()->json($query->orderBy('appointment_date')->orderBy('id')->paginate(15, ['*'], 'page', $page));
            }
        }

        return response()->json($query->orderBy('appointment_date')->orderBy('id')->paginate(15));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id'       => 'required|exists:patients,id',
            'optometrist_id'   => 'required|exists:users,id',
            'appointment_date' => 'required|date|after_or_equal:today',
            'type'             => 'required|in:eye_exam,follow_up,fitting,other',
            'reason'           => 'nullable|string',
            'notes'            => 'nullable|string',
        ]);

        $opto = User::find($validated['optometrist_id']);
        if (!$opto || $opto->role !== 'optometrist' || !$opto->is_active) {
            throw ValidationException::withMessages([
                'optometrist_id' => ['The selected user must be an active optometrist.'],
            ]);
        }

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
        // Only enforce future-date rule when the date is actually being changed.
        // Marking a past appointment as completed/no_show must not be blocked.
        $dateIsChanging = $request->has('appointment_date')
            && $request->appointment_date !== $appointment->appointment_date->toDateString();

        $validated = $request->validate([
            'optometrist_id'   => 'sometimes|exists:users,id',
            'appointment_date' => array_filter([
                'sometimes',
                'date',
                $dateIsChanging ? 'after_or_equal:today' : null,
            ]),
            'type'             => 'sometimes|in:eye_exam,follow_up,fitting,other',
            'status'           => 'sometimes|in:scheduled,completed,cancelled,no_show',
            'reason'           => 'nullable|string',
            'notes'            => 'nullable|string',
        ]);

        if (isset($validated['optometrist_id'])) {
            $opto = User::find($validated['optometrist_id']);
            if (!$opto || $opto->role !== 'optometrist' || !$opto->is_active) {
                throw ValidationException::withMessages([
                    'optometrist_id' => ['The selected user must be an active optometrist.'],
                ]);
            }
        }

        $appointment->update($validated);

        return response()->json($appointment->fresh(['patient', 'optometrist']));
    }

    public function destroy(Appointment $appointment)
    {
        $appointment->delete();
        return response()->json(['message' => 'Appointment deleted.']);
    }

    public function stats()
    {
        $stats = DB::table('appointments')
            ->whereNull('deleted_at')
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'scheduled'  THEN 1 ELSE 0 END) as scheduled,
                SUM(CASE WHEN status = 'completed'  THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status IN ('cancelled', 'no_show') THEN 1 ELSE 0 END) as cancelled_no_show
            ")
            ->first();
        return response()->json($stats);
    }
}
