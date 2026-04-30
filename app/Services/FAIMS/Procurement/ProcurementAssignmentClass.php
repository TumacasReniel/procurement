<?php

namespace App\Services\FAIMS\Procurement;

use App\Http\Resources\FAIMS\Procurement\ProcurementAssignmentResource;
use App\Models\ProcurementAssignment;
use Illuminate\Support\Facades\Auth;

class ProcurementAssignmentClass
{
    public function lists()
    {
        return ProcurementAssignmentResource::collection(
            ProcurementAssignment::with('user.profile')
                ->orderBy('status')
                ->orderByDesc('created_at')
                ->get()
        );
    }

    public function save($request): array
    {
        $validated = $request->validated();

        $created = collect($validated['user_ids'])->map(function ($userId) use ($validated) {
            return ProcurementAssignment::firstOrCreate([
                'status' => $validated['status'],
                'user_id' => $userId,
            ], [
                'created_by_id' => Auth::id(),
            ]);
        });

        $assignments = ProcurementAssignment::with('user.profile')
            ->whereIn('id', $created->pluck('id'))
            ->get();

        return [
            'data' => ProcurementAssignmentResource::collection($assignments)->resolve(),
            'message' => 'Assigned successfully.',
            'info' => "You've successfully updated the assignment.",
            'status' => true,
        ];
    }

    public function update($request, ProcurementAssignment $assignment): array
    {
        $assignment->update($request->validated());

        return [
            'data' => (new ProcurementAssignmentResource($assignment->load('user.profile')))->resolve(),
            'message' => 'Assignment updated successfully.',
            'info' => "You've successfully updated the assignment.",
            'status' => true,
        ];
    }

    public function destroy(ProcurementAssignment $assignment): array
    {
        $id = $assignment->id;
        $assignment->delete();

        return [
            'data' => $id,
            'message' => 'Assignment removed successfully.',
            'info' => "You've successfully removed the assignment.",
            'status' => true,
        ];
    }
}
