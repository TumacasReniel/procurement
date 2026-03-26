<?php

namespace App\Services\FAIMS\Procurement;

use App\Models\ResponsibilityCenter;
use App\Http\Resources\FAIMS\Procurement\ResponsibilityCenterResource;
use Illuminate\Support\Facades\Auth;

class ResponsibilityCenterClass
{
    public function show($id){
        $data = new ResponsibilityCenterResource(
            ResponsibilityCenter::with('unit')->findOrFail($id)
        );
        return $data;
    }

    public function lists($request){
        $data = ResponsibilityCenterResource::collection(
            ResponsibilityCenter::query()
            ->with('unit')
            ->when($request->keyword, function ($query, $keyword) {
                $query->where('code', 'LIKE', "%{$keyword}%")
                        ->orWhere('code', 'LIKE', "%{$keyword}%")
                        ->orWhereHas('unit', function ($q) use ($keyword) {
                            $q->where('name', 'LIKE', "%{$keyword}%");
                        });
            })
            ->orderBy('created_at','DESC')
            ->paginate($request->count)
        );
        return $data;
    }

    public function save($request)
    {
        $responsibility_center = ResponsibilityCenter::create([
            'list_unit_id' => $request->list_unit_id,
            'code' => $request->code,
        ]);

        return [
            'data' => new ResponsibilityCenterResource($responsibility_center),
            'message' => 'Responsibility Center created successfully!',
            'info' => "You've successfully added new Responsibility Center.",
            'status' => 'success',
        ];
    }

    public function update($request)
    {
        $responsibility_center = ResponsibilityCenter::findOrFail($request->id);

        $responsibility_center->update([
            'list_unit_id' => $request->list_unit_id,
            'code' => $request->code,
        ]);

        return [
            'data' => new ResponsibilityCenterResource($responsibility_center),
            'message' => 'Responsibility Center updated successfully!',
            'info' => "You've successfully updated the Responsibility Center.",
            'status' => 'success',
        ];
    }

    public function destroy($id)
    {
        $responsibility_center = ResponsibilityCenter::findOrFail($id);
        $responsibility_center->delete();

        return [
            'data' => null,
            'message' => 'Responsibility Center deleted successfully!',
            'info' => "You've successfully deleted the Responsibility Center.",
            'status' => 'success',
        ];
    }

}
