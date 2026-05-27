<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Prescription;
use Illuminate\Http\Request;

class PrescriptionController extends Controller
{
    public function index(Request $request)
    {
        $query = Prescription::with(['patient', 'optometrist'])
            ->when($request->patient_id, fn($q) => $q->where('patient_id', $request->patient_id));

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
        ]);

        $validated['optometrist_id'] = $request->user()->id;
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
}
