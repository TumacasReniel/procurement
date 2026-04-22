<?php

namespace App\Http\Controllers\Executive;

use App\Traits\HandlesTransaction;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\DropdownClass;
use App\Services\Executive\Signatory\SaveClass;
use App\Services\Executive\Signatory\ViewClass;
use App\Http\Requests\Executive\SignatoryRequest;

class SignatoryController extends Controller
{
    use HandlesTransaction;

    public $view, $save, $dropdown;

    public function __construct(SaveClass $save, ViewClass $view, DropdownClass $dropdown){
        $this->dropdown = $dropdown;
        $this->view = $view;
        $this->save = $save;
    }

    public function index(Request $request){
        switch($request->option){
            case 'list':
                return [];
            break;
            default:
                return inertia('Modules/System/Signatories/Index',[
                    'designations' => $this->view->designations()
                ]); 
        }   
    }

    public function store(SignatoryRequest $request){
        $result = $this->handleTransaction(function () use ($request) {
            switch($request->option){
                case 'signatory':
                    return $this->save->signatory($request);
                break;
                case 'designate':
                    return $this->save->designate($request);
                break;
                case 'create_bac_member':
                    return $this->save->bacMember($request);
                break;
                case 'create_iar_member':
                    return $this->save->iarMember($request);
                break;
            }
        });

        return back()->with([
            'data' => $result['data'],
            'message' => $result['message'],
            'info' => $result['info'],
            'status' => $result['status'],
        ]);
    }
}
