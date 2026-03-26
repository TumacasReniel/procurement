<?php

namespace App\Services\FAIMS\Finance;

use App\Models\FinanceRequestTypeDocument;
use App\Models\FinanceRequestType;
use App\Http\Resources\FAIMS\Finance\FinanceRequestTypeResource;

class RequestTypeClass
{
    public function lists($request)
    {
       $data = FinanceRequestTypeResource::collection(
            FinanceRequestType::when($request->keyword, function ($query, $keyword) {
                $query->where('name', 'LIKE', "%{$keyword}%")
                      ->orWhere('default_text', 'LIKE', "%{$keyword}%");
            })
            ->orderBy('created_at','DESC')
            ->paginate($request->count ?: 10)
        );

        return $data;
    }

    public function save($request)
    {
        $data = FinanceRequestType::create([
            'name' => $request->name,
            'default_text' => $request->default_text,
        ]);

        $documentIds = collect($request->required_documents ?? [])
            ->filter()
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        foreach ($documentIds as $documentId) {
            FinanceRequestTypeDocument::create([
                'request_type_id' => $data->id,
                'document_id' => $documentId,
            ]);
        }
      

        return [
            'data' => new FinanceRequestTypeResource($data),
            'message' => 'Request type created successfully!',
            'info' => "You've successfully added new Request Type.",
        ]; 
    }

    public function update($request)
    {
        $type = FinanceRequestType::findOrFail($request->id);
        $type->update([
            'name' => $request->name,
            'default_text' => $request->default_text,
        ]);

        FinanceRequestTypeDocument::where('request_type_id', $type->id)->delete();
        $documentIds = collect($request->required_documents ?? [])
            ->filter()
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        foreach ($documentIds as $documentId) {
            FinanceRequestTypeDocument::create([
                'request_type_id' => $type->id,
                'document_id' => $documentId,
            ]);
        }

        
        return [
            'data' => new FinanceRequestTypeResource($type),
            'message' => 'Request type updated successfully!',
            'info' => "You've successfully updated Request Type.",
        ]; 
    }

    public function format(FinanceRequestType $type)
    {
        return [
            'id' => $type->id,
            'value' => $type->id,
            'name' => $type->name,
            'label' => $type->name,
            'default_text' => $type->default_text,
            'required_documents' => $type->required_documents
                ? json_decode($type->required_documents, true)
                : [],
            'is_active' => (bool) $type->is_active,
        ];
    }
}
