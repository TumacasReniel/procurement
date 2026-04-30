<?php

namespace App\Http\Controllers\FAIMS\Procurement;

use App\Http\Controllers\Controller;
use App\Services\FAIMS\Procurement\ProcurementClass;
use App\Traits\HandlesTransaction;
use Illuminate\Http\Request;

class ProcurementCommentController extends Controller
{
    use HandlesTransaction;

    public function __construct(protected ProcurementClass $procurement)
    {
    }

    public function store($id, Request $request)
    {
        $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        $result = $this->handleTransaction(function () use ($id, $request) {
            return $this->procurement->addComment($id, $request);
        });

        return back()->with([
            'data' => $result['data'],
            'message' => $result['message'],
            'info' => $result['info'],
            'status' => $result['status'],
        ]);
    }
}
