<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OrgChart;
use App\Http\Resources\Public\DesignationResource;

class InfoController extends Controller
{
    public function keyofficials(){
        return inertia('Modules/Others/Organization/Index',[
            'designations' => $this->designations()
        ]);
    }

    public function baccommittee(){
        return inertia('Modules/FAIMS/Procurement/Pages/BACComittee/Index',[
            'designations' => $this->bacDesignations()
        ]);
    }

    public function iarcommittee(){
        return inertia('Modules/FAIMS/Procurement/Pages/IARComittee/Index',[
            'designations' => $this->iarDesignations()
        ]);
    }

    public function mailing(){
        return view('emails.account-activation');
    }

    private function designations(){
        $data = OrgChart::with('designation','assigned')
        ->with([
            // 'designationable.schedules.user:id,email,username',
            // 'designationable.schedules.user.profile:user_id,firstname,middlename,lastname,suffix_id,avatar',
            'designationable.schedules' => function ($q) {
                $q->where('is_completed', 0)
                  ->whereIn('is_ongoing', [0, 1])
                  ->where('is_designated', 0)
                  ->whereDate('end_at', '>=', now()->toDateString())
                  ->with([
                      'user:id,email,username',
                      'user.profile:user_id,firstname,middlename,lastname,suffix_id,avatar',
                  ]);
            },
            'designationable.user:id,email,username',
            'designationable.user.profile:user_id,firstname,middlename,lastname,suffix_id,avatar',
            'designationable.oic:id,email,username',
            'designationable.oic.profile:user_id,firstname,middlename,lastname,suffix_id,avatar'
        ])
        ->with('user:id,email,username','user.profile:user_id,firstname,middlename,lastname,suffix_id,avatar','oic:id,email,username','oic.profile:user_id,firstname,middlename,lastname,suffix_id,avatar')
        ->orderBy('order','ASC')
        ->get();
        return DesignationResource::collection($data);
    }

    private function bacDesignations(){
        $data = OrgChart::with('designation','assigned')
        ->with([
            'designationable.schedules' => function ($q) {
                $q->where('is_completed', 0)
                  ->whereIn('is_ongoing', [0, 1])
                  ->where('is_designated', 0)
                  ->whereDate('end_at', '>=', now()->toDateString())
                  ->with([
                      'user:id,email,username',
                      'user.profile:user_id,firstname,middlename,lastname,suffix_id,avatar',
                  ]);
            },
            'designationable.user:id,email,username',
            'designationable.user.profile:user_id,firstname,middlename,lastname,suffix_id,avatar',
            'designationable.oic:id,email,username',
            'designationable.oic.profile:user_id,firstname,middlename,lastname,suffix_id,avatar'
        ])
        ->with('user:id,email,username','user.profile:user_id,firstname,middlename,lastname,suffix_id,avatar','oic:id,email,username','oic.profile:user_id,firstname,middlename,lastname,suffix_id,avatar')
        ->whereHas('designation', function($q) {
            $q->where('name', 'like', 'BAC Chairperson')
            ->orWhere('name', 'like', 'BAC Vice Chairperson')
            ->orWhere('name', 'like', 'BAC Member');
        })
        ->where('order', 4)
        ->orWhere('order', 5)
        ->orWhere('order', 6)
        ->orderBy('order','ASC')
        ->get();
   

        return DesignationResource::collection($data);
    }


    private function iarDesignations(){
        $data = OrgChart::with('designation','assigned')
        ->with([
            'designationable.schedules' => function ($q) {
                $q->where('is_completed', 0)
                  ->whereIn('is_ongoing', [0, 1])
                  ->where('is_designated', 0)
                  ->whereDate('end_at', '>=', now()->toDateString())
                  ->with([
                      'user:id,email,username',
                      'user.profile:user_id,firstname,middlename,lastname,suffix_id,avatar',
                  ]);
            },
            'designationable.user:id,email,username',
            'designationable.user.profile:user_id,firstname,middlename,lastname,suffix_id,avatar',
            'designationable.oic:id,email,username',
            'designationable.oic.profile:user_id,firstname,middlename,lastname,suffix_id,avatar'
        ])
        ->with('user:id,email,username','user.profile:user_id,firstname,middlename,lastname,suffix_id,avatar','oic:id,email,username','oic.profile:user_id,firstname,middlename,lastname,suffix_id,avatar')
        ->whereHas('designation', function($q) {
            $q->where('name', 'like', 'IAR Chairperson')
            ->orWhere('name', 'like', 'IAR Vice Chairperson')
            ->orWhere('name', 'like', 'IAR Member');
        })
        ->where('order', 8)
        ->orWhere('order', 9)
        ->orWhere('order', 10)
        ->orderBy('order','ASC')
        ->get();
   

        return DesignationResource::collection($data);
    }
}
