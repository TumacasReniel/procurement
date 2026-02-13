<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DropdownClass;

class SearchController extends Controller
{
    protected $dropdown;

    public function __construct(
            DropdownClass $dropdown
        ){
        $this->dropdown = $dropdown;
    }

    public function dropdowns(){
        return [
            'dropdowns' => [
                'sexes' => $this->dropdown->datas('Sex'),
                'suffixes' => $this->dropdown->datas('Suffix'),
                'bloods' => $this->dropdown->datas('Blood Type'),
                'religions' => $this->dropdown->datas('Religion'),
                'maritals' => $this->dropdown->datas('Marital Status'),
                'divisions' => $this->dropdown->dropdowns('Division'),
                'stations' => $this->dropdown->stations(),
                'positions' => $this->dropdown->positions(),
                'salaries' => $this->dropdown->salaries(),
                'statuses' => $this->dropdown->statuses('Status'),
                'employment_statuses' => $this->dropdown->datas('Employment Status'),
                'regions' => $this->dropdown->regions()
            ],
        ];
    }

    public function search(Request $request){
        $option = $request->option;
        switch($option){
            case 'provinces':
                return $this->dropdown->provinces($request->code);
            break;
            case 'municipalities':
                return $this->dropdown->municipalities($request->code);
            break;
            case 'barangays':
                return $this->dropdown->barangays($request->code);
            break;
            case 'units':
                return $this->dropdown->units($request->code);
            break;
            case 'users':
                return $this->dropdown->users($request->keyword,$request->is_regular);
            break;
            case 'vehicles':
                return $this->dropdown->vehicles($request->keyword);
            break;
            case 'schools':
                return $this->dropdown->schools($request->keyword);
            break;
            case 'courses':
                return $this->dropdown->courses($request->keyword);
            break;
        }
    }
}
