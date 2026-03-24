<?php

namespace App\Http\Controllers\FAIMS\Finance;

use App\Http\Controllers\Controller;
use App\Models\FinanceRequestAssignment;
use Illuminate\Http\Request;

class FinanceAssignmentController extends Controller
{
    public function index(Request $request)
    {
        $query = FinanceRequestAssignment::with('user.profile');
        if ($request->filled('finance_request_id')) {
            $query->where('finance_request_id', $request->input('finance_request_id'));
        }

        $assignments = $query->get()->map(function ($assignment) {
            return [
                'id' => $assignment->id,
                'finance_request_id' => $assignment->finance_request_id,
                'status' => $assignment->status,
                'user_id' => $assignment->user_id,
                'name' => $assignment->user?->profile?->full_name ?? $assignment->user?->name ?? 'User #' . $assignment->user_id,
            ];
        });

        return response()->json([
            'data' => $assignments,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'finance_request_id' => 'required|exists:finance_requests,id',
            'status' => 'required|string|max:100',
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
        ]);

        $created = [];
        foreach ($validated['user_ids'] as $userId) {
            $created[] = FinanceRequestAssignment::create([
                'finance_request_id' => $validated['finance_request_id'],
                'status' => $validated['status'],
                'user_id' => $userId,
                'created_by_id' => auth()->id(),
            ]);
        }

        $created = FinanceRequestAssignment::with('user.profile')
            ->whereIn('id', collect($created)->pluck('id'))
            ->get();

        return response()->json([
            'data' => $created,
            'message' => 'Assigned successfully.',
            'status' => true,
        ]);
    }

    public function update(Request $request, FinanceRequestAssignment $finance_requests_assignment)
    {
        $validated = $request->validate([
            'status' => 'required|string|max:100',
            'user_id' => 'required|exists:users,id',
        ]);

        $finance_requests_assignment->update($validated);

        return response()->json([
            'data' => $finance_requests_assignment->load('user.profile'),
            'message' => 'Assignment updated successfully.',
            'status' => true,
        ]);
    }

    public function destroy(FinanceRequestAssignment $finance_requests_assignment)
    {
        $finance_requests_assignment->delete();

        return response()->json([
            'data' => $finance_requests_assignment->id,
            'message' => 'Assignment removed successfully.',
            'status' => true,
        ]);
    }
}
