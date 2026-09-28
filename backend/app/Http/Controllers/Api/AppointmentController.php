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
            ->when($request->year,  fn($q) => $q->where('appointment_year', $request->year))
            ->when($request->month, fn($q) => $q->where('appointment_month', $request->month));

        if ($request->highlight_id) {
            $target = Appointment::find($request->highlight_id);
            if ($target) {
                // Stable sort: year ASC, month ASC, id ASC — count rows that come before target
                $before = (clone $query)->where(function ($q) use ($target) {
                    $q->where('appointment_year', '<', $target->appointment_year)
                      ->orWhere(fn($q2) => $q2->where('appointment_year', $target->appointment_year)
                          ->where('appointment_month', '<', $target->appointment_month))
                      ->orWhere(fn($q2) => $q2->where('appointment_year', $target->appointment_year)
                          ->where('appointment_month', $target->appointment_month)
                          ->where('id', '<', $target->id));
                })->count();
                $page = (int) floor($before / 15) + 1;
                return response()->json($query->orderBy('appointment_year')->orderBy('appointment_month')->orderBy('id')->paginate(15, ['*'], 'page', $page));
            }
        }

        return response()->json($query->orderBy('appointment_year')->orderBy('appointment_month')->orderBy('id')->paginate(15));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id'         => 'required|exists:patients,id',
            'optometrist_id'     => 'required|exists:users,id',
            'appointment_month'  => 'required|integer|min:1|max:12',
            'appointment_year'   => 'required|integer|min:' . now()->year,
            'type'               => 'required|in:eye_exam,follow_up,fitting,other',
            'reason'             => 'nullable|string',
            'notes'              => 'nullable|string',
        ]);

        $this->assertNotInThePast($validated['appointment_year'], $validated['appointment_month']);

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
        $validated = $request->validate([
            'optometrist_id'     => 'sometimes|exists:users,id',
            'appointment_month'  => 'sometimes|integer|min:1|max:12',
            'appointment_year'   => 'sometimes|integer|min:2000',
            'type'               => 'sometimes|in:eye_exam,follow_up,fitting,other',
            'status'             => 'sometimes|in:scheduled,completed,cancelled,no_show',
            'reason'             => 'nullable|string',
            'notes'              => 'nullable|string',
        ]);

        // Only enforce the not-in-the-past rule when the month/year is actually
        // changing — marking a past appointment completed/no_show must not be blocked.
        $newMonth = $validated['appointment_month'] ?? $appointment->appointment_month;
        $newYear  = $validated['appointment_year']  ?? $appointment->appointment_year;
        $isChanging = $newMonth !== $appointment->appointment_month || $newYear !== $appointment->appointment_year;

        if ($isChanging) {
            $this->assertNotInThePast($newYear, $newMonth);
        }

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

    public function stats(Request $request)
    {
        $stats = DB::table('appointments')
            ->whereNull('deleted_at')
            ->when($request->year,   fn($q) => $q->where('appointment_year', $request->year))
            ->when($request->month,  fn($q) => $q->where('appointment_month', $request->month))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'scheduled'  THEN 1 ELSE 0 END) as scheduled,
                SUM(CASE WHEN status = 'completed'  THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status IN ('cancelled', 'no_show') THEN 1 ELSE 0 END) as cancelled_no_show
            ")
            ->first();
        return response()->json($stats);
    }

    /** A booked month/year must not be earlier than the current month. */
    private function assertNotInThePast(int $year, int $month): void
    {
        $requested = $year * 12 + $month;
        $current   = now()->year * 12 + now()->month;

        if ($requested < $current) {
            throw ValidationException::withMessages([
                'appointment_month' => ['The selected month must be this month or later.'],
            ]);
        }
    }
}
