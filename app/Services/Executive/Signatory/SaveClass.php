<?php

namespace App\Services\Executive\Signatory;

use App\Models\ListDropdown;
use App\Models\OrgChart;
use App\Models\OrgSignatory;
use App\Models\OrgSignatorySchedule;

class SaveClass
{
    public function signatory($request)
    {
        if (now()->greaterThanOrEqualTo($request->start_at)) {
            $data = OrgSignatorySchedule::create($request->all());
            $signatory = OrgSignatory::find($request->signatory_id);
            $signatory->update([
                'oic_id' => $request->user_id,
                'is_oic' => 1,
            ]);
            $data->update(['is_ongoing' => 1]);
        } else {
            $data = OrgSignatorySchedule::create(
                array_merge($request->all(), ['is_ongoing' => 0])
            );
        }

        return [
            'data' => $data,
            'message' => 'Employee created successfully',
            'info' => 'You can now manage this employee\'s details in the system',
        ];
    }

    public function designate($request)
    {
        $userId = $request->user_id;
        if ($request->is_oic) {
            $data = OrgChart::find($request->signatory_id);
            $data->update(['oic_id' => $request->user_id, 'is_oic' => 1]);
            if ($data) {
                $signatory = $data->designationable;
                $signatory->update([
                    'user_id' => $userId
                ]);
                $signatory->schedules()->update([
                    'is_ongoing' => 0
                ]);
                $signatory->schedules()->create([
                    'start_at' => $request->start_at,
                    'end_at' => $request->end_at,
                    'user_id' => $userId,
                    'is_designated' => 1,
                    'is_ongoing' => 1,
                ]);
            }
        } else {
            $data = OrgChart::find($request->signatory_id);
            $data->update([
                'user_id' => $request->user_id,
                'oic_id' => null,
                'is_oic' => 0,
            ]);

            if ($data) {
                $signatory = $data->designationable;
                $signatory->update([
                    'user_id' => $userId,
                    'oic_id' => null,
                    'is_oic' => 0,
                ]);
                $signatory->schedules()->create([
                    'start_at' => now(),
                    'user_id' => $userId,
                    'is_designated' => 1,
                    'is_ongoing' => 1,
                ]);
            }
        }

        return [
            'data' => $data,
            'message' => 'Employee created successfully',
            'info' => 'You can now manage this employee\'s details in the system',
        ];
    }

    public function bacMember($request)
    {
        return $this->createCommitteeMember(
            $request->user_id,
            'BAC Member',
            6,
            'Unable to add BAC member',
            'BAC committee configuration is incomplete. Please check the list dropdown setup.',
            'BAC member added successfully',
            'The new BAC committee member slot is now available on the committee page.'
        );
    }

    public function iarMember($request)
    {
        return $this->createCommitteeMember(
            $request->user_id,
            'IAR Member',
            10,
            'Unable to add IAR member',
            'IAR committee configuration is incomplete. Please check the list dropdown setup.',
            'IAR member added successfully',
            'The new IAR committee member slot is now available on the committee page.'
        );
    }

    private function createCommitteeMember($userId, $designationName, $order, $errorMessage, $errorInfo, $successMessage, $successInfo)
    {
        $designationId = ListDropdown::getID($designationName, 'Designation');
        $assignedId = OrgChart::where('designation_id', $designationId)->value('assigned_id')
            ?? ListDropdown::getID('Regional Office', 'Station');

        if (!$designationId || !$assignedId) {
            return [
                'data' => null,
                'message' => $errorMessage,
                'info' => $errorInfo,
                'status' => false,
            ];
        }

        $data = new OrgChart();
        $data->order = $order;
        $data->designation_id = $designationId;
        $data->assigned_id = $assignedId;
        $data->user_id = $userId;
        $data->oic_id = null;
        $data->is_oic = 0;
        $data->is_active = 1;
        $data->save();

        $signatory = new OrgSignatory([
            'user_id' => $userId,
            'oic_id' => null,
            'is_oic' => 0,
            'is_topmanagement' => 0,
            'is_active' => 1,
        ]);
        $data->designationable()->save($signatory);

        return [
            'data' => $data,
            'message' => $successMessage,
            'info' => $successInfo,
        ];
    }
}
