<?php

namespace App\Http\Controllers\Public;

use Hashids\Hashids;
use App\Traits\HandlesTransaction;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ListDropdown;
use Illuminate\Support\Facades\Crypt;
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
               abort(403, 'Downloading 100 TB of files.');
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

    public function show(Request $request)
    {
        $decrypted = Crypt::decryptString($request->station);
        $code = explode('/', $decrypted)[0];

        $hashids = new Hashids('krad',10);
        $id = $hashids->decode($code);

        return inertia('Public/Dtr/Index',[
            'code' => $code,
            'station' => ListDropdown::where('id',$id)->value('name')
        ]);
    }
}
