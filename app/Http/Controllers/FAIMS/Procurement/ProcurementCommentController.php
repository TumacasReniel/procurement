<?php

namespace App\Http\Controllers\FAIMS\Procurement;

use App\Http\Controllers\Controller;
use App\Services\FAIMS\Procurement\CommentClass;
use App\Traits\HandlesTransaction;
use Illuminate\Http\Request;

class ProcurementCommentController extends Controller
{
    use HandlesTransaction;

    public function __construct(protected CommentClass $comments)
    {
    }

    public function store($id, Request $request)
    {
        $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        $result = $this->handleTransaction(function () use ($id, $request) {
            return $this->comments->addComment($id, $request);
        });

        if ($request->expectsJson()) {
            return response()->json($result);
        }

        return back()->with([
            'data' => $result['data'],
        ]);
    }
}
