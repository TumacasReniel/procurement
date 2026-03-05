<?php

namespace App\Http\Controllers\FAIMS\Procurement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\FAIMS\Procurement\OfferClass;
use App\Services\FAIMS\Procurement\ViewClass;
use App\Services\DropdownClass;
use App\Traits\HandlesTransaction;


class OfferController extends Controller
{
     use HandlesTransaction;

    public function __construct(
        OfferClass $offer, 
        ViewClass $view, 
        DropdownClass $dropdown
    ){
        $this->offer = $offer;
        $this->dropdown = $dropdown;
        $this->view = $view;
    }

    public function store(Request $request) {
        switch($request->option){     
            case 'save_bid_for_award':
                $result = $this->handleTransaction(function () use ($request) {
                    return $this->offer->save_bid_for_award($request);
                });
            break;   
            default:
                $result = $this->handleTransaction(function () use ($request) {
                    return $this->offer->save($request);
                });
            break;         
        }  
        
        return back()->with([
            'data' => $result['data'],
            'message' => $result['message'],
            'info' => $result['info'],
            'status' => $result['status'],
        ]);
    }
}
