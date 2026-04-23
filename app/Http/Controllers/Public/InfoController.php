<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OrgChart;
use App\Models\ListDropdown;
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
        $this->ensureCommitteeSlots([
            ['designation' => 'BAC Chairperson', 'order' => 4, 'count' => 1],
            ['designation' => 'BAC Vice Chairperson', 'order' => 5, 'count' => 1],
            ['designation' => 'BAC Member', 'order' => 6, 'count' => 3],
        ]);

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
        ->whereIn('order', [4, 5, 6])
        ->orderBy('order','ASC')
        ->get();
   

        return DesignationResource::collection($data);
    }


    private function iarDesignations(){
        $this->ensureCommitteeSlots([
            ['designation' => 'IAR Chairperson', 'order' => 8, 'count' => 1],
            ['designation' => 'IAR Member', 'order' => 10, 'count' => 3],
        ]);

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
            ->orWhere('name', 'like', 'IAR Member');
        })
        ->whereIn('order', [8, 10])
        ->orderBy('order','ASC')
        ->get();
   

        return DesignationResource::collection($data);
    }

    private function ensureCommitteeSlots(array $slots): void
    {
        $defaultAssignedId = ListDropdown::getID('Regional Office', 'Station');

        foreach ($slots as $slot) {
            $designationId = ListDropdown::getID($slot['designation'], 'Designation');

            if (!$designationId) {
                continue;
            }

            $charts = OrgChart::with('designationable')
                ->where('designation_id', $designationId)
                ->where('order', $slot['order'])
                ->orderBy('id')
                ->get();

            foreach ($charts as $chart) {
                $this->ensureSignatory($chart);
            }

            $missingCount = max(0, $slot['count'] - $charts->count());
            $assignedId = $charts->value('assigned_id') ?? $defaultAssignedId;

            if (!$assignedId) {
                continue;
            }

            for ($i = 0; $i < $missingCount; $i++) {
                $chart = new OrgChart();
                $chart->order = $slot['order'];
                $chart->designation_id = $designationId;
                $chart->assigned_id = $assignedId;
                $chart->user_id = null;
                $chart->oic_id = null;
                $chart->is_oic = 0;
                $chart->is_active = 1;
                $chart->save();

                $this->ensureSignatory($chart);
            }
        }
    }

    private function ensureSignatory(OrgChart $chart): void
    {
        if ($chart->designationable) {
            return;
        }

        $chart->designationable()->create([
            'user_id' => $chart->user_id,
            'oic_id' => $chart->oic_id,
            'is_oic' => $chart->is_oic,
            'is_topmanagement' => 0,
            'is_active' => $chart->is_active,
        ]);
    }
}
