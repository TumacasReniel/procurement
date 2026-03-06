<?php

namespace App\Http\Controllers\Public;

use App\Traits\HandlesTransaction;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Public\Dtr\SaveClass;
use App\Services\Public\Dtr\ViewClass;

class AttendanceController extends Controller
{
    use HandlesTransaction;

    protected ViewClass $view;
    protected SaveClass $save;

    public function __construct(SaveClass $save, ViewClass $view){
        $this->view = $view;
        $this->save = $save;
    }

    public function index(Request $request){
        switch($request->option){
            case 'list':
                return $this->view->list($request);
            break;
            default:
               return inertia('Public/Dtr/Index');
        }   
    }

    public function store(Request $request){
        $result = $this->handleTransaction(function () use ($request) {
            return $this->save->store($request);
        });
        return $result;
    }

    public function recognize(Request $request) //AWS REKOGNITION
    {
        return $this->save->recognize($request);
    }
}
