<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\User;
use App\Models\ListRole;
use App\Models\ListUnit;
use App\Models\ListData;
use App\Models\ListStatus;
use App\Models\ListDropdown;
use App\Models\ListSalary;
use App\Models\ListLeave;
use App\Models\ListDeduction;
use App\Models\ListPosition;
use App\Models\AssetVehicle;
use App\Models\LocationRegion;
use App\Models\LocationProvince;
use App\Models\LocationMunicipality;
use App\Models\LocationDistrict;
use App\Models\LocationBarangay;
use App\Models\ProcurementCode;
use App\Models\UnitType;
use App\Models\supplier;
use App\Models\OrgChart;
use App\Models\FinanceDocument;
use App\Models\FinanceRequestType;
use App\Models\ListProject;
use App\Models\FinanceCreditor;

class DropdownClass
{
    // public function __construct()
    // {
    //     $this->agency = (\Auth::user()->role != 'Administrator') ? (\Auth::user()->myroles) ? \Auth::user()->myroles[0]->agency_id : null : null;
    //     $this->ids = (\Auth::check()) ? (\Auth::user()->role == 'Administrator') ? [] : AgencyConfiguration::where('agency_id',$this->agency)->value('laboratories') : '';
    //     $this->laboratory = UserRole::where('user_id',\Auth::user()->id)->whereNotNull('laboratory_id')->pluck('laboratory_id');
    // }

    // public function suppliers(){
    //     $data = InventorySupplier::where('agency_id',$this->agency)->where('is_active',1)->get()->map(function ($item) {
    //         return [
    //             'value' => $item->id,
    //             'name' => $item->name
    //         ];
    //     });
    //     return $data;
    // }

    // public function services(){
    //     $data = TestserviceAddon::where('agency_id',$this->agency)->where('is_additional',0)->get()->map(function ($item) {
    //         return [
    //             'value' => $item->id,
    //             'label' => $item->name.' ('.$item->description.')',
    //             'name' => $item->name,
    //             'description' => $item->description,
    //             'fee' => $item->fee
    //         ];
    //     });
    //     return $data;
    // }

    public function vehicles($date)
    {
        if (strpos($date, ' to ') !== false) {
            [$start, $end] = explode(' to ', $date);
        } else {
            $start = $end = $date;
        }

        $start = Carbon::parse($start)->startOfDay();
        $end = Carbon::parse($end)->endOfDay();

        $vehicles = AssetVehicle::with('driver.organization.division')
            ->whereDoesntHave('reservations.request.dates', function ($query) use ($start, $end) {
                $query->where(function ($q) use ($start, $end) {
                    $q->whereBetween('start', [$start, $end])
                        ->orWhereBetween('end', [$start, $end])
                        ->orWhere(function ($q2) use ($start, $end) {
                            $q2->where('start', '<=', $start)
                                ->where('end', '>=', $end);
                        });
                });
            })
            ->where('is_available', 1)
            ->get()->map(function ($item) {
                return [
                    'value' => $item->id,
                    'name' => $item->name,
                    'driver_id' => $item->driver_id,
                    'division_id' => ($item->driver_id) ? optional($item->driver->organization->division)->id : null,
                ];
            });

        return $vehicles;
    }

    public function leaves()
    {
        $data = ListLeave::where('is_active', 1)->get()->map(function ($item) {
            if ($item->requires_balance === 1) {
                return [
                    'label' => 'Require Credits',
                    'options' => [
                        'value' => $item->id,
                        'name' => $item->name,
                        'citation' => $item->citation,
                        'is_regular' => $item->is_regular,
                        'is_increasing' => $item->is_increasing,
                        'is_after' => $item->is_after,
                        'is_active' => $item->is_active,
                        'requires_balance' => $item->requires_balance
                    ]
                ];
            } else if ($item->requires_balance === 0) {
                return [
                    'label' => 'Require Documents',
                    'options' => [
                        'value' => $item->id,
                        'name' => $item->name,
                        'citation' => $item->citation,
                        'is_regular' => $item->is_regular,
                        'is_increasing' => $item->is_increasing,
                        'is_after' => $item->is_after,
                        'is_active' => $item->is_active,
                        'requires_balance' => $item->requires_balance
                    ]
                ];
            } else {
                return [
                    'label' => 'Others',
                    'options' => [
                        'value' => $item->id,
                        'name' => $item->name,
                        'citation' => $item->citation,
                        'is_regular' => $item->is_regular,
                        'is_increasing' => $item->is_increasing,
                        'is_after' => $item->is_after,
                        'is_active' => $item->is_active,
                        'requires_balance' => $item->requires_balance
                    ]
                ];
            }
        });
        $grouped = $data->groupBy('label')->map(function ($items) {
            return [
                'label' => $items->first()['label'],
                'options' => $items->pluck('options')->values()
            ];
        })->values();

        return $grouped;
    }

    public function dropdowns($classifications, $type = null)
    {
       
        $data = ListDropdown::where('classification', $classifications)
            ->when($type, function ($query) use ($type) {
                $query->where('type', $type);
            })
            ->get()->map(function ($item) {
                return [
                    'value' => $item->id,
                    'name' => $item->name,
                    'others' => $item->others
                ];
            });
        return $data;
    }

    public function datas($type)
    {
        $data = ListData::where('type', $type)->where('is_active', 1)->get()->map(function ($item) {
            return [
                'value' => $item->id,
                'name' => $item->name
            ];
        });
        return $data;
    }

    protected function resolveDropdownClassifications(string $classification): array
    {
        return match ($classification) {
            'mode_of_procurement', 'modes_of_procurement' => [
                'mode_of_procurement',
                'modes_of_procurement',
                'Mode of Procurement',
            ],
            'app_type', 'app_types', 'APP Type', 'App Type', 'APP Type Classification' => [
                'APP Type',
                'App Type',
                'app_type',
                'app_types',
            ],
            default => [$classification],
        };
    }

    public function units($code)
    {
        $data = ListUnit::where('division_id', $code)->where('is_active', 1)->get()->map(function ($item) {
            return [
                'value' => $item->id,
                'name' => $item->name,
                'short' => $item->short
            ];
        });
        return $data;
    }

    public function events()
    {
        $data = ListDropdown::where('classification', 'Calendar')->get()->map(function ($item) {
            return [
                'value' => $item->id,
                'name' => $item->name,
                'type' => $item->type,
                'color' => $item->color,
                'others' => $item->others
            ];
        });
        return $data;
    }

    public function stations()
    {
        $data = ListDropdown::where('classification', 'Station')->where('is_active', 1)->get()->map(function ($item) {
            return [
                'value' => $item->id,
                'name' => $item->name . ' (' . $item->others . ')',
                'others' => $item->others
            ];
        });
        return $data;
    }

    public function salaries()
    {
        $data = ListSalary::orderBy('grade', 'ASC')->get()->map(function ($item) {
            return [
                'value' => $item->id,
                'name' => 'SG ' . $item->grade . ' (' . $item->amount . ')',
                'grade' => $item->grade,
                'amount' => $item->amount,
                'year' => $item->year,
                'is_regular' => $item->is_regular
            ];
        });
        return $data;
    }

    public function positions()
    {
        $data = ListPosition::with('salary')->get()->map(function ($item) {
            return [
                'value' => $item->id,
                'name' => $item->name,
                'salary_id' => $item->salary_id,
                'year' => $item->salary->year,
                'is_regular' => $item->is_regular
            ];
        });
        return $data;
    }

    public function deductions()
    {
        $data = ListDeduction::get()->map(function ($item) {
            return [
                'value' => $item->id,
                'name' => ($item->subtype != 'n/a') ? $item->name . ' (' . $item->subtype . ')' : $item->name,
                'subtype' => $item->subtype,
                'is_regular' => $item->is_regular,
                'is_contribution' => $item->is_contribution,
                'is_loan' => $item->is_loan,
                'is_enrollable' => $item->is_enrollable
            ];
        });
        return $data;
    }

    public function statuses($type)
    {
        $data = ListStatus::where('classification', $type)->where('is_active', 1)->get()->map(function ($item) {
            return [
                'value' => $item->id,
                'name' => $item->name,
                'type' => $item->type,
                'color' => $item->color,
                'others' => $item->others,
            ];
        });
        return $data;
    }

    public function roles()
    {
        $data = ListRole::where('is_active', 1)
            ->whereIn('type', ['Staff', 'Hr'])
            ->get()
            ->groupBy(fn($item) => $item->type === 'Staff' ? 'Regular' : 'Human Resource')
            ->map(function ($items, $label) {
                return [
                    'label' => $label,
                    'options' => $items->map(fn($item) => [
                        'value' => $item->id,
                        'name' => $item->name
                    ])->values()
                ];
            })
            ->values();
        return $data;
    }


    public function regions()
    {
        $data = LocationRegion::all()->map(function ($item) {
            return [
                'value' => $item->code,
                'name' => $item->region
            ];
        });
        return $data;
    }

    public function provinces($code)
    {
        $data = LocationProvince::where('region_code', $code)->get()->map(function ($item) {
            return [
                'value' => $item->code,
                'name' => $item->name
            ];
        });
        return $data;
    }

    public function municipalities($code)
    {
        $data = LocationMunicipality::where('province_code', $code)->get()->map(function ($item) {
            return [
                'value' => $item->code,
                'name' => $item->name
            ];
        });
        return $data;
    }

    public function barangays($code)
    {
        $data = LocationBarangay::where('municipality_code', $code)->get()->map(function ($item) {
            return [
                'value' => $item->code,
                'name' => $item->name
            ];
        });
        return $data;
    }

    public function users($keyword, $is_regular = null, $limit = 10)
    {
        $limit = max(1, min((int) $limit, 25));

        $data = User::with('profile')
            ->with('organization.position', 'organization.division', 'organization.type')
            ->when(!is_null($is_regular) && $is_regular == 1, function ($query) {
                $query->whereHas('organization', function ($query) {
                    $query->where('type_id', 15);
                });
            })
            ->when($keyword, function ($query) use ($keyword) {
                $keyword = trim((string) $keyword);

                $query->where(function ($query) use ($keyword) {
                    $query
                        ->where('username', 'like', '%' . $keyword . '%')
                        ->orWhereHas('profile', function ($q) use ($keyword) {
                            $q->where('firstname', 'like', '%' . $keyword . '%')
                                ->orWhere('middlename', 'like', '%' . $keyword . '%')
                                ->orWhere('lastname', 'like', '%' . $keyword . '%');
                        });
                });
            })
            ->limit($limit)->get()->map(function ($item) {
                $profile = $item->profile;
                $middleInitial = $profile?->middlename ? substr($profile->middlename, 0, 1) . '.' : '';
                $name = trim(($profile?->lastname ? $profile->lastname . ', ' : '') . ($profile?->firstname ?? '') . ' ' . $middleInitial);

                return [
                    'id' => $item->id,
                    'value' => $item->id,
                    'username' => $item->username,
                    'signatory' => $item->signatory,
                    'name' => $name ?: ($item->username ?: 'User #' . $item->id),
                    'position' => optional($item->organization?->position)->name,
                    'division' => optional($item->organization?->division)->name,
                    'division_id' => optional($item->organization?->division)->id,
                    'type' => optional($item->organization?->type)->name,
                    'avatar' => ($item->profile && $item->profile->avatar && $item->profile->avatar !== 'noavatar.jpg')
                        ? asset('storage/' . $item->profile->avatar)
                        : asset('images/avatars/avatar.jpg'),
                ];
            });
        return $data;
    }


    //procurement

    public function list_units()
    {
        $data = ListUnit::where('is_active', 1)->get()->map(function ($item) {
            return [
                'value' => $item->id,
                'name' => $item->name,
                'short' => $item->short
            ];
        });
        return $data;
    }


    public function procurement_codes()
    {
        $data = ProcurementCode::get()->map(function ($item) {
            $label = $item->code;
            $remainingBudget = (float) ($item->remaining_budget ?? $item->allocated_budget ?? 0);

            if (!empty($item->title)) {
                $label .= ' - ' . $item->title;
            }

            return [
                'value' => $item->id,
                'code' => $item->code,
                'title' => $item->title,
                'allocated_budget' => (float) $item->allocated_budget,
                'remaining_budget' => $remainingBudget,
                'label' => $label,
            ];
        });
        return $data;
    }

    public function unit_types()
    {
        $data = UnitType::get()->map(function ($item) {
            return [
                'value' => $item->id,
                'name_short' => $item->name_short,
                'name_long' => $item->name_long
            ];
        });
        return $data;
    }

    public function unit_type($code)
    {
        $data = UnitType::where('id', $code)->get()->map(function ($item) {
            return [
                'value' => $item->id,
                'name_short' => $item->name_short,
                'name_long' => $item->name_long
            ];
        });
        return $data;
    }

    public function requesters()
    {
        $data = User::with(['roles', 'profile.suffix'])
            ->get()
            ->map(function ($item) {
                $profile = $item->profile;
                $firstname = $profile->firstname ?? '';
                $middlename = $profile->middlename ? strtoupper(substr($profile->middlename, 0, 1)) . '.' : '';
                $lastname = $profile->lastname ?? '';
                $suffix = $profile->suffix?->name ? ' ' . $profile->suffix->name : '';

                return [
                    'value' => $item->id,
                    'name' => trim("{$firstname} {$middlename} {$lastname}{$suffix}"),
                ];
            });

        return $data;
    }


    public function approvers()
    {
        $data = User::with(['roles', 'profile.suffix'])
            ->get()
            ->map(function ($user) {
                return [
                    'value' => $user->id,
                    'name' => $user->profile->full_name,
                ];
            });

        return $data;
    }


    public function supply_officers()
    {
        return User::with('roles', 'profile')
            ->whereHas('roles', function ($query) {
                $query->where('list_roles.name', 'Supply Officer')
                    ->where('user_roles.is_active', 1);
            })
            ->orderBy('id')
            ->get()
            ->map(function ($item) {
                return [
                    'value' => $item->id,
                    'name' => $item->profile?->full_name ?? ('User #' . $item->id),
                ];
            });
    }

    public function suppliers()
    {
        $data = Supplier::with('conformes')
            ->where('is_active', 1)
            ->where('approval_status', 'Approved')
            ->get()
            ->map(function ($item) {
            return [
                'value' => $item->id,
                'name' => $item->name,
            ];
        });
        return $data;
    }


    public function attachment_types()
    {
        $data = ListData::where('type', 'Attachment')->where('is_active', 1)->get()->map(function ($item) {
            return [
                'value' => $item->id,
                'name' => $item->name
            ];
        });
        return $data;
    }


    public function regional_director()
    {
        $data = OrgChart::with('user')
            ->where('designation_id', ListDropdown::getID('Regional Director', 'Designation'))->first();


        if (!$data) {
            return null; // or return an empty array []
        }

        return [
            'value' => $data->user_id,
            'name' => strtoupper(
                $data->user->profile->full_name ?? null,
            ),
            'designation' => $data->designation
        ];
    }


    public function bac_members()
    {
        $data = OrgChart::where('designation_id', ListDropdown::getID('BAC Member', 'Designation'))
            ->get()
            ->map(function ($item) {
                return [
                    'value' => $item->id,
                    'name' => $item->user->profile->full_name,
                ];
            });

        return $data;
    }


    public function bac_chairperson()
    {
        $data = OrgChart::where('designation_id', ListDropdown::getID('BAC Chairperson', 'Designation'))->first();


        if (!$data) {
            return null; // or return an empty array []
        }

        return [
            'value' => $data->id,
            'name' => strtoupper(
                $data->user->profile->full_name ?? null,
            ),
            'designation' => $data->designation
        ];
    }

    public function bac_vice_chairperson()
    {

        $data = OrgChart::where('designation_id', ListDropdown::getID('BAC Vice Chairperson', 'Designation'))->first();

        if (!$data) {
            return null; // or return an empty array []
        }

        return [
            'value' => $data->id,
            'name' => strtoupper(
                $data->user->profile->full_name ?? null,
            ),
            'designation' => $data->designation
        ];
    }


    public function chief_accountant()
    {

        $data = OrgChart::where('designation_id', ListDropdown::getID('Chief Accountant', 'Designation'))->first();

        if (!$data) {
            return null; // or return an empty array []
        }
        return [
            'value' => $data->id,
            'name' => strtoupper(
                $data->user->profile->full_name,
            ),
            'designation' => $data->designation
        ];
    }


    public function iar_chairperson()
    {
        $data = OrgChart::with('user.profile', 'oic.profile', 'designation')
            ->where('designation_id', ListDropdown::getID('IAR Chairperson', 'Designation'))
            ->first();

        if (!$data) {
            return null; // or return an empty array []
        }

        $name = $this->resolveOrgChartDisplayName($data, true);

        return [
            'value' => $data->id,
            'name' => $name,
            'designation' => $data->designation
        ];
    }


    public function iar_members()
    {
        $data = OrgChart::with('user.profile', 'oic.profile')
            ->where('designation_id', ListDropdown::getID('IAR Member', 'Designation'))
            ->get()->map(function ($item) {
                return [
                    'value' => $item->id,
                    'name' => $this->resolveOrgChartDisplayName($item),
                ];
            })->filter(fn ($item) => filled($item['name']))->values();

        return $data;
    }

    private function resolveOrgChartDisplayName($item, $uppercase = false)
    {
        $person = null;

        if ($item?->is_oic && $item?->oic) {
            $person = $item->oic;
        } elseif ($item?->user) {
            $person = $item->user;
        } elseif ($item?->oic) {
            $person = $item->oic;
        }

        $name = $person?->profile?->full_name;

        if (!filled($name)) {
            return null;
        }

        return $uppercase ? strtoupper($name) : $name;
    }

    public function division_head($division_id)
    {
        $data = OrgChart::with('user')
            ->where('designation_id', ListDropdown::getID('Division Head', 'Designation'))
            ->whereHas('user.organization', function ($query) use ($division_id) {
                $query->where('division_id', $division_id);
            })
            ->first();

        if (!$data) {
            return null;
        }

        return [
            'value' => $data->user_id,
            'name' => strtoupper(
                $data->user->profile->full_name ?? null,
            ),
            'designation' => $data->designation
        ];
    }



    //FINANCE

    public function documents()
    {
        $data = FinanceDocument::get()->map(function ($item) {
            return [
                'value' => $item->id,
                'name' => $item->name
            ];
        });
        return $data;
    }

    public function request_types()
    {
        $data = FinanceRequestType::get()->map(function ($item) {
            return [
                'value' => $item->id,
                'name' => $item->name
            ];
        });
        return $data;
    }


    public function projects()
    {
        $data = ListProject::get()->map(function ($item) {
            return [
                'value' => $item->id,
                'label' => $item->name
            ];
        });
        return $data;
    }

    public function creditors()
    {
        $suppliers = Supplier::where('is_active', 1)
            ->where('approval_status', 'Approved')
            ->get()
            ->map(function ($item) {
            return [
                'value' => 'supplier_' . $item->id,
                'label' => $item->name . ' (Supplier)',
                'name' => $item->name,
                'type' => 'supplier'
            ];
        });

        $users = User::with('profile')->get()->map(function ($user) {
            $name = $user->profile->full_name ?? $user->name ?? 'User ' . $user->id;
            return [
                'value' => 'user_' . $user->id,
                'label' => $name . ' (Employee)',
                'name' => $name,
                'type' => 'user'
            ];
        });

        return $suppliers->concat($users)->values()->all();
    }

}
