<?php

namespace App\Services\FAIMS\Finance;

use App\Models\ListProject;
use App\Http\Resources\FAIMS\Finance\ProjectResource;

class ProjectClass
{
    public function lists($request)
    {
        $data = ProjectResource::collection(
            ListProject::with('type')
                ->when($request->keyword, function ($query, $keyword) {
                    $query->where('name', 'LIKE', "%{$keyword}%")
                          ->orWhere('description', 'LIKE', "%{$keyword}%");
                })
                ->orderBy('created_at','DESC')
                ->paginate($request->count ?: 10)
        );

        return $data;
    }

    public function save($request)
    {
        $data = ListProject::create([
            'name' =>  $request->name,
            'description' =>  $request->description,
            'type_id' =>  $request->type_id,
        ]);

        return [
            'data' => new ProjectResource($data),
            'message' => 'Project created successfully!',
            'info' => "You've successfully added new Project.",
        ]; 
    }

    public function update($request)
    {
        $data = ListProject::findOrFail($request->id);
        $data->update([
            'name' =>  $request->name,
            'description' =>  $request->description,
            'type_id' =>  $request->type_id,
        ]);

        return [
            'data' => new ProjectResource($data),
            'message' => 'Project updated successfully!',
            'info' => "You've successfully updated Project.",
        ]; 
    }
}
?>

