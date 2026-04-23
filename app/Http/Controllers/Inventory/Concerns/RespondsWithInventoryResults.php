<?php

namespace App\Http\Controllers\Inventory\Concerns;

use Illuminate\Http\Request;

trait RespondsWithInventoryResults
{
    protected function shouldReturnJson(Request $request): bool
    {
        if ($request->header('X-Inertia')) {
            return false;
        }

        return $request->wantsJson() || $request->ajax() || $request->boolean('json');
    }

    protected function inventoryResultResponse(Request $request, array $result, string $tab)
    {
        if ($this->shouldReturnJson($request)) {
            return response()->json($result, ($result['status'] ?? true) ? 200 : 500);
        }

        return redirect("/inventory-stocks?tab={$tab}")->with([
            'data' => $result['data'],
            'message' => $result['message'],
            'info' => $result['info'],
            'status' => $result['status'],
        ]);
    }
}
