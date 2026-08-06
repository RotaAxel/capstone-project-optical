<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PrescriptionController extends Controller
{
    public function index(Request $request)
    {
        $query = Prescription::with(['patient', 'optometrist'])
            ->when($request->patient_id, fn($q) => $q->where('patient_id', $request->patient_id))
            ->when($request->search, fn($q) => $q->whereHas('patient', fn($pq) =>
                $pq->where('first_name', 'like', "%{$request->search}%")
                   ->orWhere('last_name', 'like', "%{$request->search}%")
                   ->orWhere('patient_code', 'like', "%{$request->search}%")
            ))
            ->when($request->date, fn($q) => $q->whereDate('exam_date', $request->date));

        if ($request->highlight_id) {
            $target = Prescription::find($request->highlight_id);
            if ($target) {
                // Stable sort: created_at DESC, id DESC — count rows that come before target
                $before = (clone $query)->where(function ($q) use ($target) {
                    $q->where('created_at', '>', $target->created_at)
                      ->orWhere(fn($q2) => $q2->where('created_at', $target->created_at)->where('id', '>', $target->id));
                })->count();
                $page = (int) floor($before / 15) + 1;
                return response()->json($query->orderBy('created_at', 'desc')->orderBy('id', 'desc')->paginate(15, ['*'], 'page', $page));
            }
        }

        return response()->json($query->orderBy('created_at', 'desc')->orderBy('id', 'desc')->paginate(15));
    }

    public function store(Request $request)
    {
        $user      = $request->user();
        $validated = $request->validate([
            'patient_id'       => 'required|exists:patients,id',
            'exam_date'        => 'required|date',
            'valid_until'      => 'nullable|date',
            'od_sphere'        => 'nullable|numeric',
            'od_cylinder'      => 'nullable|numeric',
            'od_axis'          => 'nullable|numeric',
            'od_add'           => 'nullable|numeric',
            'od_pd'            => 'nullable|numeric',
            'os_sphere'        => 'nullable|numeric',
            'os_cylinder'      => 'nullable|numeric',
            'os_axis'          => 'nullable|numeric',
            'os_add'           => 'nullable|numeric',
            'os_pd'            => 'nullable|numeric',
            'visual_acuity_od' => 'nullable|numeric',
            'visual_acuity_os' => 'nullable|numeric',
            'notes'            => 'nullable|string',
            'optometrist_id'   => $user->isAdmin() ? 'required|exists:users,id' : 'nullable',
        ]);

        if ($user->isAdmin()) {
            $opto = User::find($validated['optometrist_id']);
            if (!$opto || $opto->role !== 'optometrist') {
                throw ValidationException::withMessages([
                    'optometrist_id' => ['The selected user must have the optometrist role.'],
                ]);
            }
        } else {
            $validated['optometrist_id'] = $user->id;
        }

        $prescription = Prescription::create($validated);

        return response()->json($prescription->load(['patient', 'optometrist']), 201);
    }

    public function show(Prescription $prescription)
    {
        return response()->json($prescription->load(['patient', 'optometrist']));
    }

    public function update(Request $request, Prescription $prescription)
    {
        $prescription->update($request->validate([
            'exam_date'        => 'sometimes|date',
            'valid_until'      => 'nullable|date',
            'od_sphere'        => 'nullable|numeric',
            'od_cylinder'      => 'nullable|numeric',
            'od_axis'          => 'nullable|numeric',
            'od_add'           => 'nullable|numeric',
            'od_pd'            => 'nullable|numeric',
            'os_sphere'        => 'nullable|numeric',
            'os_cylinder'      => 'nullable|numeric',
            'os_axis'          => 'nullable|numeric',
            'os_add'           => 'nullable|numeric',
            'os_pd'            => 'nullable|numeric',
            'visual_acuity_od' => 'nullable|numeric',
            'visual_acuity_os' => 'nullable|numeric',
            'notes'            => 'nullable|string',
        ]));

        return response()->json($prescription->fresh(['patient', 'optometrist']));
    }

    public function destroy(Prescription $prescription)
    {
        $prescription->delete();
        return response()->json(['message' => 'Prescription deleted.']);
    }

    public function stats(Request $request)
    {
        $today      = now()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();
        $stats = DB::table('prescriptions')
            ->whereNull('deleted_at')
            ->when($request->date, fn($q) => $q->whereDate('exam_date', $request->date))
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN valid_until IS NOT NULL AND valid_until >= ? THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN valid_until IS NOT NULL AND valid_until < ?  THEN 1 ELSE 0 END) as expired,
                SUM(CASE WHEN exam_date >= ? THEN 1 ELSE 0 END) as this_month
            ", [$today, $today, $monthStart])
            ->first();
        return response()->json($stats);
    }
}
