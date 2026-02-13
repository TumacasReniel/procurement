<?php

namespace App\Services\Profile;

use App\Models\User;
use App\Models\UserInformation;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class SaveClass
{
    public function save($request){

        $user = User::find(\Auth::user()->id);
        if ($user->profile->avatar) {
            Storage::disk('public')->delete($user->profile->avatar);
        }

        $imagePath = $request->file('image')->store('profile-pictures', 'public');
        $user->profile->avatar = $imagePath;
        $user->profile->save();

        return [
            'data' => [],
            'message' => 'Profile picture updated successfully.', 
            'info' => "The user's profile image has been changed to the new photo."
        ];
    }

    public function update($request){
        $data = User::find(\Auth::user()->id);
        if($data->save()){
            $profile = $data->profile;
            $profile->sex_id = $request->sex_id;
            $profile->blood_id = $request->blood_id;
            $profile->marital_id = $request->marital_id;
            $profile->religion_id = $request->religion_id;
            $profile->mobile = $request->mobile;
            $profile->is_completed = 1;
            if($profile->save()){
                if($request->filled('permanent.address')) {
                    $data->addresses()->updateOrCreate(
                        [
                            'is_permanent'      => 1,
                            'address'           => $request->permanent['address'],
                            'region_code'       => $request->permanent['region_code'],
                            'province_code'     => $request->permanent['province_code'],
                            'municipality_code' => $request->permanent['municipality_code'],
                            'barangay_code'     => $request->permanent['barangay_code'],
                            'latitude'          => $request->permanent['latitude'],
                            'longitude'         => $request->permanent['longitude'],
                        ]
                    );
                }elseif($request->filled('home.address')) {
                        $data->addresses()->updateOrCreate(
                        [
                            'is_permanent'      => 0,
                            'address'           => $request->home['address'],
                            'region_code'       => $request->home['region_code'],
                            'province_code'     => $request->home['province_code'],
                            'municipality_code' => $request->home['municipality_code'],
                            'barangay_code'     => $request->home['barangay_code'],
                            'latitude'          => $request->home['latitude'],
                            'longitude'         => $request->home['longitude'],
                        ]
                    );
                }

                $this->information($data->id);
            }
        }
        $data = User::find(\Auth::user()->id);
        return [
            'data' => $data,
            'message' => 'User information updated successfully.', 
            'info' => "All relevant fields have been refreshed with the latest data."
        ];
    }

    public function destroy($request)
    {
        if (!Auth::guard('web')->validate([
            'email' => $request->user()->email,
            'password' => $request->password,
        ])) {
            throw ValidationException::withMessages([
                'password' => __('auth.password'),
            ]);
        }
        $this->deleteOtherSessionRecords($request);
        return back(303);
    }

    protected function deleteOtherSessionRecords(Request $request)
    {
        if (config('session.driver') !== 'database') {
            return;
        }
        \DB::connection(config('session.connection'))->table(config('session.table', 'sessions'))
            ->where('user_id', $request->user()->getAuthIdentifier())
            ->where('id', '!=', $request->session()->getId())
            ->delete();
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
}
