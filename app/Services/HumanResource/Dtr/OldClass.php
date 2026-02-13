<?php

namespace App\Services\HumanResource\Dtr;

use App\Models\Dtr;
use App\Models\OldDtr;
use App\Models\User;
use Carbon\Carbon;

class OldClass
{
    public function dtr($request){
        set_time_limit(1200);

        $success = [];
        $failed = [];
        $startOfMonth = Carbon::create(2025, 12, 1)->startOfDay()->toDateString();
        $endOfMonth = Carbon::now()->endOfMonth()->toDateString();

        $normalizedUsernames = User::pluck('username')
        ->map(fn ($u) => str_replace('-', '', $u))
        ->toArray();
        $dtrs = OldDtr::with('user')
        ->whereBetween('date', [$startOfMonth,$endOfMonth])->get();

        foreach($dtrs as $dtr){
            $username = $dtr->user?->username ?? null;
            if($username){
                $normalizedOld = strtolower(str_replace('-', '', $username));
                $user = User::with('profile','organization.division')
                ->whereRaw("REPLACE(LOWER(username), '-', '') = ?", [$normalizedOld])
                ->first();
                if($user){
                    $remarks = [
                        'tardiness' => null,
                        'undertime' => null
                    ];

                    $amin_tardiness = ($dtr->inAM) ? $this->computeLateMinutes($dtr->date,'Time In (am)',date('H:i:s',$dtr->inAM)) : 0;
                    $pmin_tardiness = ($dtr->inPM) ? $this->computeLateMinutes($dtr->date,'Time In (pm)',date('H:i:s',$dtr->inPM)) : 0;
                    $amout_undertime = ($dtr->outAM) ? $this->computeLateMinutes($dtr->date,'Time Out (am)',date('H:i:s',$dtr->outAM)) : 0;
                    $pmout_undertime = ($dtr->outPM) ? $this->computeLateMinutes($dtr->date,'Time Out (pm)',date('H:i:s',$dtr->outPM),date('H:i:s',$dtr->inAM)) : 0;

                    $amin = $dtr->inAM 
                    ? [
                        'ip' => $dtr->ip,
                        'pcname' => gethostname(),
                        'browser' => $request->header('User-Agent'),
                        'time' =>  date('H:i:s',$dtr->inAM),
                        'date' => $dtr->date,
                        'minutes' => $amin_tardiness,
                        'is_updated' => false,
                        'changes' => []
                    ] 
                    : null;

                    $amout = $dtr->outAM 
                    ? [
                        'ip' => $dtr->ip,
                        'pcname' => gethostname(),
                        'browser' => $request->header('User-Agent'),
                        'time' => date('H:i:s',$dtr->outAM),
                        'date' => $dtr->date,
                        'minutes' => $amout_undertime,
                        'is_updated' => false,
                        'changes' => []
                    ] 
                    : null;

                    $pmin = $dtr->inPM 
                    ? [
                        'ip' => $dtr->ip,
                        'pcname' => gethostname(),
                        'browser' => $request->header('User-Agent'),
                        'time' => date('H:i:s',$dtr->inPM),
                        'date' => $dtr->date,
                        'minutes' => $pmin_tardiness,
                        'is_updated' => false,
                        'changes' => []
                    ] 
                    : null;

                    $pmout = $dtr->outPM 
                    ? [
                        'ip' => $dtr->ip,
                        'pcname' => gethostname(),
                        'browser' => $request->header('User-Agent'),
                        'time' => date('H:i:s',$dtr->outPM),
                        'date' => $dtr->date,
                        'minutes' => $pmout_undertime,
                        'is_updated' => false,
                        'changes' => []
                    ] 
                    : null;
                    
                    $tardiness = $amin_tardiness + $pmin_tardiness;
                    $undertime = $amout_undertime + $pmout_undertime;

                    $data = new Dtr;
                    $data->date = $dtr->date;
                    $data->tardiness = $tardiness;
                    $data->undertime = $undertime;
                    $data->am_in_at = ($dtr->inAM) ? json_encode($amin) : null;
                    $data->am_out_at = ($dtr->outAM) ? json_encode($amout) : null;
                    $data->pm_in_at = ($dtr->inPM) ? json_encode($pmin) : null;
                    $data->pm_out_at =  ($dtr->outPM) ? json_encode($pmout) : null;
                    $data->remarks = json_encode($remarks);
                    $data->user_id = $user->id;
                    $data->is_completed = ($amin && $amout && $pmin && $pmout) ? 1 : 0;
                    $data->save();
                    $success[] = $dtr->user->username;
                }else{
                    $failed[] = $dtr->user->username;
                }
            }else{
                $failed[] = $dtr->id;
            }
        }
        return [$success,array_unique($failed)];


        // $employees = Employee::where('is_active',1)->get();
        // foreach($employees as $employee){
        //     $user = User::create([
        //         'username' => $employee->username,
        //         'email' => ($employee->email) ? $employee->email : $employee->username.'@gmail.com',
        //         'password' => bcrypt($employee->username.'!@#$%'),
        //         'created_at' => $employee->created_at,
        //         'updated_at' => $employee->updated_at,
        //     ]);
        //     if($user){
        //         $profile = $user->profile()->create([
        //             'firstname' => $employee->first_name,
        //             'middlename' => $employee->middle_name,
        //             'lastname' => $employee->last_name,
        //             'suffix' => $employee->name_suffix,
        //             'sex' => 'Male',
        //             'birthdate' => now(),
        //             'contact_no' => '09123456789',
        //             'avatar' => ($employee->picture) ? $employee->picture : 'avatar.jpg',
        //             'signature' => ($employee->signature) ? $employee->signature : 'signature.jpg',
        //             'marital_id' => 1,
        //             'religion_id' => 1,
        //             'blood_id' => 1,
        //         ]);

        //         if($profile){
        //             $user->organization()->create([
        //                 'status_id' => 2,
        //                 'type_id' => $this->status($employee->employee_status_id),
        //                 'position_id' => 1,
        //                 'division_id' => 1,
        //                 'unit_id' => 1,
        //                 'station_id' => 1
        //             ]);

        //             $this->information($user->id);
        //         }
        //     }
        // }
    }

    public function computeLateMinutes($date, $type, $time, $in = null)
    {
        $date = Carbon::parse($date);

        // 🧩 If $time is null or empty — skip (no tardiness/undertime)
        if (empty($time) || $time === '00:00:00') {
            return $minutes = 0;
        }

        try {
            $time = Carbon::createFromTimeString($time);
        } catch (\Exception $e) {
            return $minutes = 0;
        }

        switch ($type) {
            case 'Time In (am)':
                // Monday special rule
                if ($date->isMonday()) {
                    $officialStart = Carbon::createFromTimeString('08:00:00');
                    $grace = Carbon::createFromTimeString('08:00:59');
                    $minutes = $time->greaterThan($grace)
                        ? (int) $officialStart->diffInMinutes($time)
                        : 0;
                } else {
                    $officialStart = Carbon::createFromTimeString('08:00:00');
                    $flexibleCutoff = Carbon::createFromTimeString('08:30:59');
                    // ✅ If within 8:00–8:30, no tardiness
                    $minutes = $time->greaterThan($flexibleCutoff)
                        ? (int) $officialStart->diffInMinutes($time)
                        : 0;
                }
                break;

            case 'Time Out (am)':
                // 🧩 If AM out is empty (no half-day break), skip
                if (empty($time)) {
                    return 0;
                }
                $officialMorningOut = Carbon::createFromTimeString('12:00:00');
                $minutes = $time->lessThan($officialMorningOut)
                    ? ceil($time->diffInMinutes($officialMorningOut))
                    : 0;
                break;

            case 'Time In (pm)':
                // 🧩 If PM in is empty, skip (flexible schedule)
                if (empty($time)) {
                    return 0;
                }
                $officialAfternoonTimeIn = Carbon::createFromTimeString('13:00:59');
                $minutes = $time->greaterThan($officialAfternoonTimeIn)
                    ? (int) $officialAfternoonTimeIn->diffInMinutes($time)
                    : 0;
                break;

            case 'Time Out (pm)':
                $officialStart = Carbon::createFromTimeString('08:00:00');
                $officialAfternoonOut = Carbon::createFromTimeString('17:00:00');

                // 🧩 Handle flexible afternoon extension if AM in is valid
                if (!$date->isMonday() && !empty($in) && $in !== '00:00:00') {
                    try {
                        $timeIn = Carbon::createFromFormat('H:i:s', $in);
                        if ($timeIn->between(
                            Carbon::createFromTimeString('08:00:00'),
                            Carbon::createFromTimeString('08:30:59')
                        )) {
                            $flexMinutes = $officialStart->diffInMinutes($timeIn);
                            // extend the official afternoon out time
                            $officialAfternoonOut->addMinutes($flexMinutes);
                        }
                    } catch (\Exception $e) {
                        // ignore invalid in time
                    }
                }

                // ✅ Compare outPM with adjusted officialOut
                $actualOut = $time->copy()->setSeconds(0);
                $adjustedOut = $officialAfternoonOut->copy()->setSeconds(0);

                $minutes = $actualOut->lessThan($adjustedOut)
                    ? $actualOut->diffInMinutes($adjustedOut)
                    : 0;
                break;

            default:
                $minutes = 0;
                break;
        }

        return $minutes;
    }
}
