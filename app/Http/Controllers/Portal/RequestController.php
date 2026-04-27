<?php

namespace App\Http\Controllers\Portal;

use Illuminate\Support\Facades\Crypt;
use App\Traits\HandlesTransaction;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ListData;
use App\Services\DropdownClass;
use App\Services\Portal\Request\CtoClass;
use App\Services\Portal\Request\SaveClass;
use App\Services\Portal\Request\ViewClass;
use App\Services\Portal\Request\ShowClass;
use App\Services\Portal\Request\LeaveClass;
use App\Services\Portal\Request\PrintClass;
use App\Services\Portal\Request\TravelClass;
use App\Services\Portal\Request\ReservationClass;
use App\Http\Requests\Portal\MyrequestRequest;

class RequestController extends Controller
{
    use HandlesTransaction;

    public $view,$save,$dropdown,$show,$cto,$leave,$print,$travel,$reservation;

    public function __construct(
        CtoClass $cto,
        SaveClass $save, 
        ViewClass $view, 
        ShowClass $show, 
        LeaveClass $leave,
        PrintClass $print,
        TravelClass $travel,
        ReservationClass $reservation,
        DropdownClass $dropdown
    ){
        $this->cto = $cto;
        $this->view = $view;
        $this->save = $save;
        $this->show = $show;
        $this->leave = $leave;
        $this->print = $print;
        $this->travel = $travel;
        $this->reservation = $reservation;
        $this->dropdown = $dropdown;
    }

    public function index(Request $request){
        switch($request->option){
            case 'lists':
                return $this->view->lists($request);
            break;
            case 'check':
                return $this->view->check($request);
            break;
            case 'print':
                return $this->print->document($request);
            break;
            default:
                $requestTypes = $this->requestTypes();

                return inertia('Modules/Portal/Requests/Index',[
                    'counts' => $this->view->counts($requestTypes),
                    'dropdowns' => [
                        'requests' => $requestTypes,
                        'statuses' => $this->dropdown->statuses('Request'),
                    ],
                    'leave_dropdowns' => [
                        'leaves' => $this->dropdown->leaves(),
                        'details' => $this->dropdown->dropdowns('Leave Details'),
                        'options' => $this->view->credits(),
                    ],
                    'travel_dropdowns' => [
                        'modes' => $this->dropdown->datas('Travel'),
                        'expenses' => $this->dropdown->datas('Travel Expense'),
                        'transportations' => $this->dropdown->datas('Public Conveyance'),
                        'regions' => $this->dropdown->regions()
                    ],
                    'vehicle_dropdowns' => [
                        'transportations' => $this->dropdown->datas('Public Conveyance'),
                        'regions' => $this->dropdown->regions()
                    ],
                    'procurement_dropdowns' => [
                        'divisions' => $this->dropdown->dropdowns('Division'),
                        'fund_clusters' => $this->dropdown->dropdowns('Fund Cluster'),
                        'classifications' => $this->dropdown->dropdowns('Classification'),
                        'procurement_codes' => $this->dropdown->procurement_codes(),
                        'unit_types' => $this->dropdown->unit_types(),
                        'requesters' => $this->dropdown->requesters(),
                        'approvers' => $this->dropdown->approvers(),
                        'regional_director' => $this->dropdown->regional_director(),
                    ]
                ]); 
        }   
    }

    protected function requestTypes()
    {
        $types = collect($this->dropdown->datas('Request Type'));
        $procurementTypeId = ListData::getID('Procurement');

        if (
            $procurementTypeId &&
            !$types->contains(fn ($type) => (int) ($type['value'] ?? 0) === (int) $procurementTypeId)
        ) {
            $types->push([
                'value' => $procurementTypeId,
                'name' => 'Procurement',
            ]);
        }

        return $types->values()->all();
    }

    public function store(MyrequestRequest $request){
        $result = $this->handleTransaction(function () use ($request) {
            switch($request->option){
                case 'cto':
                    return $this->cto->store($request);
                break;
                case 'leave':
                    return $this->leave->store($request);
                break;
                case 'travel':
                    return $this->travel->store($request);
                break;
                case 'reservation':
                    return $this->reservation->store($request);
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

    public function show($string){
        $string = Crypt::decryptString($string);
        $parts = explode("krad", $string);
   
        $type = $parts[0]; 
        $code  = $parts[1]; 

        switch($type){
            case 'travel-order':
                return inertia('Modules/Portal/Requests/View/Travels/View',[
                    'information_data' => $this->show->travel($code),
                    'attachments' => $this->dropdown->datas('Attachment'),
                ]);
            break;
            case 'vehicle-reservation':
                return inertia('Modules/Portal/Requests/View/Reservations/View',[
                    'information_data' => $this->show->reservation($code)
                ]);
            break;
            case 'leave-form':
                return inertia('Modules/Portal/Requests/View/Leaves/View',[
                    'information_data' => $this->show->leave($code)
                ]);
            break;
            case 'render-overtime-service':
                return inertia('Modules/Portal/Requests/View/Overtime/View',[
                    'information_data' => $this->show->overtime($code)
                ]);
            break;
        }
    }

    public function update(Request $request){
        $result = $this->handleTransaction(function () use ($request) {
            switch($request->option){
                case 'overtime':
                    return $this->save->overtime($request);
                break;
                case 'document':
                    return $this->save->document($request);
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
