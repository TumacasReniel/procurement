<?php

namespace App\Services\FAIMS\Finance;

use App\Models\FinanceCreditor;
use App\Http\Resources\FAIMS\Finance\FinanceCreditorResource;

class CreditorClass
{
    public function lists($request)
    {
        $data = FinanceCreditorResource::collection(
        FinanceCreditor::with('creditorable')
                ->when($request->keyword, function ($query, $keyword) {
                    $query->where('name', 'LIKE', "%{$keyword}%")
                          ->orWhere('account_code', 'LIKE', "%{$keyword}%");
                })
                ->orderBy('created_at','DESC')
                ->paginate($request->count ?: 10)
        );

        return $data;
    }

    public function save($request)
    {
        $data = FinanceCreditor::create([
            'name' =>  $request->name,
            'account_code' =>  $request->account_code,
            'creditorable_id' => $request->creditorable_id ?? null,
            'creditorable_type' => $request->creditorable_type ?? null,
        ]);

        return [
            'data' => new FinanceCreditorResource($data),
            'message' => 'Creditor created successfully!',
            'info' => "You've successfully added new Creditor.",
        ]; 
    }

    public function update($request)
    {
        $data = FinanceCreditor::findOrFail($request->id);
        $data->update([
            'name' =>  $request->name,
            'account_code' =>  $request->account_code,
            'creditorable_id' => $request->creditorable_id ?? null,
            'creditorable_type' => $request->creditorable_type ?? null,
        ]);

        return [
            'data' => new FinanceCreditorResource($data),
            'message' => 'Creditor updated successfully!',
            'info' => "You've successfully updated Creditor.",
        ]; 
    }
}
?>

