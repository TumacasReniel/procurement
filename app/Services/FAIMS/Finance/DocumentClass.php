<?php

namespace App\Services\FAIMS\Finance;

use App\Models\FinanceDocument;
use App\Http\Resources\FAIMS\Finance\FinanceDocumentResource;

class DocumentClass
{
    public function lists($request)
    {
        $data = FinanceDocumentResource::collection(
            FinanceDocument::when($request->keyword, function ($query, $keyword) {
                $query->where('name', 'LIKE', "%{$keyword}%")
                      ->orWhere('name', 'LIKE', "%{$keyword}%");
            })
            ->orderBy('created_at','DESC')
            ->paginate($request->count ?: 10)
        );

        return $data;
    }

    public function save($request)
    {
        $data = FinanceDocument::create([
            'name' =>  $request->name,
            'description' =>  $request->description,
        ]);

        return [
            'data' => new FinanceDocumentResource($data),
            'message' => 'Document created successfully!',
            'info' => "You've successfully added new Document.",
        ]; 
    }

    public function update($request)
    {
        $data = FinanceDocument::findOrFail($request->id);
        $data->update([
            'name' =>  $request->name,
            'description' =>  $request->description,
        ]);

        return [
            'data' => new FinanceDocumentResource($data),
            'message' => 'Document updated successfully!',
            'info' => "You've successfully updated Document.",
        ]; 
    }
}
