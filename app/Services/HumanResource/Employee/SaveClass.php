<?php

namespace App\Services\HumanResource\Employee;

use Carbon\Carbon;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserOrganization;
use App\Models\UserAcademic;
use App\Models\UserContract;
use App\Models\UserDeduction;
use App\Models\UserCredential;
use App\Models\UserInformation;
use App\Models\ListAcademic;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Http\Resources\HumanResource\Employee\IndexResource;

class SaveClass
{
    public function credential($request){
        $data = UserCredential::create($request->all());
        return [
            'data' => $data,
            'message' => 'Credential added successfully', 
            'info' => 'You can now manage this employee’s credentials',
        ];
    }

    public function contract($request){
        $data = UserContract::create($request->all());
        return [
            'data' => $data,
            'message' => 'Contract added successfully', 
            'info' => 'You can now manage this employee’s contract',
        ];
    }

    public function academic($request){
        $data = UserAcademic::create($request->all());
        return [
            'data' => $data,
            'message' => 'Academic added successfully', 
            'info' => 'You can now manage this employee’s academics',
        ];
    }

    public function deduction($request){
        $data = UserDeduction::create($request->all());
        $data = UserDeduction::with('deduction')->where('id',$data->id)->first();
        return [
            'data' => $data,
            'message' => 'Deduction added successfully', 
            'info' => 'You can now manage this employee’s deduction',
        ];
    }

    public function update($request){
        $user = User::where('id',$request->id)->first();
        $user->username = $request->username;
        $user->email = $request->email;
        if($user->save()){
            $profile = UserProfile::where('user_id',$request->id)->first();
            $profile->firstname = $request->firstname;
            $profile->middlename = $request->middlename;
            $profile->lastname = $request->lastname;
            $profile->birthdate = $request->birthdate;
            $profile->mobile = $request->mobile;
            $profile->marital_id = $request->marital_id;
            $profile->religion_id = $request->religion_id;
            $profile->blood_id = $request->blood_id;
            $profile->suffix_id = $request->suffix_id;
            $profile->sex_id = $request->sex_id;
            if($profile->save()){
                $organization = UserOrganization::where('user_id',$request->id)->first();
                $organization->division_id = $request->division_id;
                $organization->station_id = $request->station_id;
                $organization->unit_id = $request->unit_id;
                $organization->type_id = $request->type_id;
                $organization->position_id = $request->position_id;
                $organization->salary_id = $request->salary_id;
                $organization->save();
            }
        }

        $data = new IndexResource(
            User::select('users.id','email','username','users.created_at')
            ->with('profile.religion','profile.blood','profile.marital','profile.sex','profile.suffix')
            ->with('organization.division','organization.position','organization.unit','organization.station','organization.type','organization.status')
            ->where('id',$request->id)->first()
        );

        return [
            'data' => $data,
            'message' => 'User updated successfully', 
            'info' => 'You can now manage this employee’s academics',
        ];
    }

    public function store($request){
        $data = User::create([
            'username' => $request->username,
            'email' => $request->email,
            'is_active' => 1,
            'password' => Hash::make(Str::random(10))
        ]);
        if($data){
            $profile = $data->profile()->create([
                'firstname' => $request->firstname,
                'middlename' => $request->middlename,
                'lastname' => $request->lastname,
                'suffix_id' => $request->suffix_id,
                'sex_id' => $request->sex_id,
                'mobile' => $request->mobile,
                'birthdate' => $request->birthdate,
                'birthmonth' => Carbon::parse($request->birthdate)->format('m'),
                'marital_id' => $request->marital_id,
                'religion_id' => $request->religion_id,
                'blood_id' => $request->blood_id,
            ]);
            if($profile){
                $organization = $data->organization()->create([
                    'status_id' => 1,
                    'type_id' => $request->type_id,
                    'position_id' => $request->position_id,
                    'division_id' => $request->division_id,
                    'station_id' => $request->station_id,
                    'salary_id' => $request->salary_id,
                    'unit_id' => $request->unit_id,
                ]);
                if($organization){
                    $role = $data->myroles()->create([
                        'role_id' => 2,
                        'added_by' => \Auth::user()->id
                    ]);
                    if($role){
                        $this->information($data->id);
                    }
                    // $leave = $data->leaves()->create([
                    //     ''
                    // ]);
                }
            }
        }
        return [
            'data' => $data,
            'message' => 'Employee created successfully', 
            'info' => 'You can now manage this employee’s details in the system',
        ];
    }

    private function information($id){
        $accounts = [
            ["name" => "Pag-Ibig","number" => null,"deduction" => null, "is_contribution" => true],
            ["name" => "SSS","number" => null, "deduction" => null, "is_contribution" => true],
            ["name" => "GSIS", "number" => null, "deduction" => null, "is_contribution" => true],
            ["name" => "PhilHealth", "number" => null, "deduction" => null, "is_contribution" => true],
            ["name" => "TIN",  "number" => null, "deduction" => null, "is_contribution" => false],
            ["name" => "LandBank", "number" => null, "deduction" => null, "is_contribution" => false]
        ];
        
        $family = [
            "parents" => [
                "father" => [
                    "name" => null,
                    "address" => null,
                ],
                "mother" => [
                    "name" => null,
                    "address" => null,
                ]
            ],
            "spouse" => [
                "name" => null,
                "address" => null,
                "contact_no" => null,
                "occupation" => null,
                "company" => null,
            ],
            "children" => []
        ];

        $contacts = [
            "emergency_contact" => [
                "name" => null,
                "relationship" => null,
                "contact_no" => null,
                "address" => [
                    "region" => null,
                    "province" => null,
                    "municipality" => null,
                    "barangay" => null,
                    "street" => null
                ]
            ]
        ];

        UserInformation::create([
            'accounts' => json_encode($accounts),
            'backgrounds' => json_encode($family),
            'contacts' => json_encode($contacts),
            'user_id' => $id
        ]);
        
    }

    public function listacademic($request){
        $data = ListAcademic::create($request->all());
        $item = ListAcademic::findOrFail($data->id);
        $item = [
            'value' => $item->id,
            'name' => $item->name,
            'short' => $item->short,
            'type_id' => $item->type_id
        ];
        return [
            'data' => $item,
            'message' => 'Data added successfully', 
            'info' => 'You can now select the data.',
        ];
    }
}
