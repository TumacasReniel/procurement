<?php

namespace App\Http\Controllers\FAIMS\Procurement;

use App\Http\Controllers\Controller;
use App\Models\ProcurementAssignment;
use App\Services\DropdownClass;
use Illuminate\Http\Request;

class ProcurementAssignmentController extends Controller
{
    public function __construct(public DropdownClass $dropdown)
    {
    }

    public function index(Request $request)
    {
        if (
            !$request->filled('procurement_id') &&
            !$request->boolean('json') &&
            !$request->expectsJson()
        ) {
            return inertia('Modules/FAIMS/Procurement/Assignments', [
                'statuses' => $this->dropdown->statuses('Procurement'),
            ]);
        }

        $query = ProcurementAssignment::with('user.profile', 'procurement.status', 'procurement.sub_status');
        if ($request->filled('procurement_id')) {
            $query->where('procurement_id', $request->input('procurement_id'));
        }

        $assignments = $query->get()->map(function ($assignment) {
            return [
                'id' => $assignment->id,
                'procurement_id' => $assignment->procurement_id,
                'status' => $assignment->status,
                'user_id' => $assignment->user_id,
                'name' => $assignment->user?->profile?->full_name ?? $assignment->user?->name ?? 'User #' . $assignment->user_id,
                'procurement' => $assignment->procurement ? [
                    'id' => $assignment->procurement->id,
                    'code' => $assignment->procurement->code,
                    'purpose' => $assignment->procurement->purpose,
                    'status' => $assignment->procurement->status?->name,
                    'sub_status' => $assignment->procurement->sub_status?->name,
                ] : null,
            ];
        });

        return response()->json([
            'data' => $assignments,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'procurement_id' => 'required|exists:procurements,id',
            'status' => 'required|string|max:100',
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
        ]);

        $created = [];
        foreach ($validated['user_ids'] as $userId) {
            $created[] = ProcurementAssignment::create([
                'procurement_id' => $validated['procurement_id'],
                'status' => $validated['status'],
                'user_id' => $userId,
                'created_by_id' => auth()->id(),
            ]);
        }

        $created = ProcurementAssignment::with('user.profile')
            ->whereIn('id', collect($created)->pluck('id'))
            ->get();

        return response()->json([
            'data' => $created,
            'message' => 'Assigned successfully.',
            'status' => true,
        ]);
    }

    public function update(Request $request, ProcurementAssignment $procurement_assignment)
    {
        $validated = $request->validate([
            'status' => 'required|string|max:100',
            'user_id' => 'required|exists:users,id',
        ]);

        $procurement_assignment->update($validated);

        return response()->json([
            'data' => $procurement_assignment->load('user.profile'),
            'message' => 'Assignment updated successfully.',
            'status' => true,
        ]);
    }

    public function destroy(ProcurementAssignment $procurement_assignment)
    {
        $procurement_assignment->delete();

        return response()->json([
            'data' => $procurement_assignment->id,
            'message' => 'Assignment removed successfully.',
            'status' => true,
        ]);
    }
}
