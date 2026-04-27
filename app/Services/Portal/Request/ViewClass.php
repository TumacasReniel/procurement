<?php

namespace App\Services\Portal\Request;

use App\Models\ListData;
use App\Models\Procurement;
use App\Models\Request;
use App\Models\RequestSignatory;
use App\Models\ListLeave;
use App\Models\UserCredit;
use App\Http\Resources\Portal\Request\IndexResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class ViewClass
{
    public function counts($types){
        $user_id = \Auth::user()->id;
        $procurementTypeId = $this->procurementTypeId();

        foreach($types as $type){
            if ((int) $type['value'] === (int) $procurementTypeId) {
                $counts[] = $this->ownedProcurementsQuery($user_id)->count();
                continue;
            }

            $counts[] = Request::whereHas('tags', function ($query) use ($user_id) {
                $query->where('user_id', $user_id);
            })
            ->where('type_id',$type['value'])->count();
        }
        return $counts;
    }

    public function lists($request){
        $user_id = \Auth::user()->id;
        $division_id = \Auth::user()->organization->division_id;
        $procurementTypeId = $this->procurementTypeId();
        $requestedTypeId = $request->type ? (int) $request->type : null;

        $requestItems = RequestSignatory::with([
            'status',
            'request.tags.user:id',
            'request.tags.user.profile:user_id,firstname,middlename,lastname,avatar,suffix_id',
            'request.type',
            'request.dates',
            'request.detail',
        ])
        ->when($request->status, fn($q, $status) => $q->where('status_id', $status))
        ->when($requestedTypeId && $requestedTypeId !== (int) $procurementTypeId, fn($q) =>
            $q->whereHas('request', function ($query) use ($requestedTypeId) {
                $query->where('type_id', $requestedTypeId);
            })
        )
        ->when($request->keyword, function ($query, $keyword) {
            $query->whereHas('request.user.profile', function ($q) use ($keyword) {
                $q->whereRaw('LOWER(lastname) LIKE ?', ['%' . strtolower($keyword) . '%']);
            });
        })
        ->whereHas('request.tags', function ($query) use ($user_id) {
            $query->where('user_id', $user_id);
        })
        ->whereIn('division_id',[$division_id,48])
        ->latest()
        ->get()
        ->map(fn($item) => $this->transformRequestSignatory($item))
        ->toBase();

        $procurementItems = collect();

        if (!$requestedTypeId || $requestedTypeId === (int) $procurementTypeId) {
            $procurementItems = $this->ownedProcurementsQuery($user_id)
            ->with([
                'request',
                'classification',
                'status',
                'created_by.profile',
                'requested_by.profile',
            ])
            ->when($request->status, fn($q, $status) => $q->where('status_id', $status))
            ->when($request->keyword, function ($query, $keyword) {
                $keyword = strtolower(trim($keyword));

                $query->where(function ($nested) use ($keyword) {
                    $nested->whereRaw('LOWER(code) LIKE ?', ['%' . $keyword . '%'])
                        ->orWhereRaw('LOWER(purpose) LIKE ?', ['%' . $keyword . '%'])
                        ->orWhereRaw('LOWER(title) LIKE ?', ['%' . $keyword . '%']);
                });
            })
            ->latest()
            ->get()
            ->map(fn($item) => $this->transformProcurement($item))
            ->toBase();
        }

        return $this->paginateResponse(
            $requestItems
                ->merge($procurementItems)
                ->sortByDesc('sort_at')
                ->values(),
            (int) ($request->count ?? 10)
        );
    }

    protected function transformRequestSignatory(RequestSignatory $item): array
    {
        return (new IndexResource($item))->resolve();
    }

    protected function procurementTypeId(): ?int
    {
        return ListData::getID('Procurement');
    }

    protected function ownedProcurementsQuery(int $userId): Builder
    {
        return Procurement::query()
            ->where(function ($procurementQuery) use ($userId) {
                $procurementQuery
                    ->where('created_by_id', $userId)
                    ->orWhere('requested_by_id', $userId);

                if (Schema::hasColumn('procurements', 'request_id')) {
                    $procurementQuery->orWhereHas('request', function ($requestQuery) use ($userId) {
                        $requestQuery->where('user_id', $userId);
                    });
                }
            });
    }

    protected function transformProcurement(Procurement $item): array
    {
        $people = collect([$item->requested_by, $item->created_by])
            ->filter()
            ->unique('id')
            ->values();

        return [
            'id' => 'procurement-' . $item->id,
            'key' => null,
            'code' => $item->code,
            'type' => 'Procurement',
            'status' => [
                'id' => $item->status?->id,
                'name' => $item->status?->name,
                'bg' => (!empty($item->status?->bg) && $item->status->bg !== 'n/a') ? $item->status->bg : 'bg-primary',
            ],
            'link' => null,
            'href' => "/faims/procurements/{$item->id}?option=view",
            'purpose' => $item->purpose,
            'remarks' => null,
            'start' => $item->date,
            'end' => $item->date,
            'tags' => $people->map(function ($user) use ($item) {
                return [
                    'name' => $user->profile?->full_name ?? ('User #' . $user->id),
                    'division' => $item->division_id,
                    'avatar' => ($user->profile && $user->profile->avatar && $user->profile->avatar !== 'noavatar.jpg')
                        ? asset('storage/' . $user->profile->avatar)
                        : asset('images/avatars/avatar.jpg'),
                ];
            })->values()->all(),
            'created_at' => optional($item->created_at)->format('F d, Y g:i a'),
            'updated_at' => optional($item->updated_at)->format('F d, Y g:i a'),
            'subtype' => $item->classification?->name ?? 'Procurement',
            'sort_at' => optional($item->created_at)->toDateTimeString(),
        ];
    }

    protected function paginateResponse(Collection $items, int $perPage): JsonResponse
    {
        $perPage = $perPage > 0 ? $perPage : 10;
        $currentPage = max((int) request('page', 1), 1);
        $total = $items->count();
        $lastPage = max((int) ceil(max($total, 1) / $perPage), 1);
        $path = url('/requests');

        $data = $items
            ->forPage($currentPage, $perPage)
            ->values()
            ->map(fn($item) => collect($item)->except('sort_at')->all())
            ->all();

        return response()->json([
            'data' => $data,
            'links' => [
                'first' => $total > 0 ? "{$path}?page=1" : null,
                'last' => $total > 0 ? "{$path}?page={$lastPage}" : null,
                'prev' => $currentPage > 1 ? "{$path}?page=" . ($currentPage - 1) : null,
                'next' => $currentPage < $lastPage ? "{$path}?page=" . ($currentPage + 1) : null,
            ],
            'meta' => [
                'current_page' => $currentPage,
                'from' => $total > 0 ? (($currentPage - 1) * $perPage) + 1 : null,
                'last_page' => $lastPage,
                'path' => $path,
                'per_page' => $perPage,
                'to' => $total > 0 ? min($currentPage * $perPage, $total) : null,
                'total' => $total,
            ],
        ]);
    }

    public function credits()
    {
        $year = now()->year;
        $user_id = \Auth::user()->id;
        $is_regular = \Auth::user()->organization->type_id == 15;
        $sex = \Auth::user()->profile->sex;
        $options = [];

        if($is_regular){
            $options = collect();
            $leaves = ListLeave::where('is_regular',1)->where('is_active',1)->where('is_requested',0)->get();
            foreach($leaves as $leave){
                $item = UserCredit::with('leave')->where('leave_id',$leave->id)->where('user_id',$user_id)->where('is_active',1)->where('year',$year)->first();
                if($item){
                    $options[] = [
                        'label' => 'Require Credits',
                        'options' => [
                            'value' => $item->id,
                            'type_id' => $item->leave->id,
                            'label' => $item->leave->name.' - '.$item->balance,
                            'name' => $item->leave->name,
                            'citation' => $item->leave->citation,
                            'is_regular' => $item->leave->is_regular,
                            'is_after' => $item->leave->is_after,
                            'type' => $item->leave->type,
                            'others' => $item->leave->others,
                            'balance' => $item->balance,
                            'disabled'   => ($item->balance == 0 || $item->balance == 0.00),
                            'required_credits' => true,
                            'required_document' =>  $item->leave->requires_document
                        ]
                    ];
                }
            }
            $leaves = ListLeave::where(function ($query) use ($sex){
                $query->whereNull('sex')->orWhere('sex',$sex->name);
            })
            ->where('is_regular',1)->where('is_active',1)->where('is_requested',1)->get();
            foreach($leaves as $item){
                $options->push([
                    'label' => 'Require Documents',
                    'options' => [
                        'value' => $item->id,
                        'type_id' => $item->id,
                        'label' => $item->name,
                        'name' => $item->name,
                        'citation' => $item->citation,
                        'is_regular' => $item->is_regular,
                        'is_after' => $item->is_after,
                        'type' => $item->type,
                        'others' => $item->others,
                        'max_days' => $item->max_days,
                        'renewal' => $item->renewal_period,
                        'required_credits' => false,
                        'required_document' =>  $item->requires_document
                    ]
                ]);
            }
        }else{
            $options = collect();
            $item = UserCredit::with('leave')
            ->where('leave_id', 14)
            ->where('user_id', $user_id)
            ->where('is_active', 1)
            ->where('year', $year)
            ->first();

            if ($item) {
                $options->push([
                    'label' => 'Require Credits',
                    'options' => [
                        'value' => $item->id,
                        'type_id' => $item->leave->id,
                        'label' => $item->leave->name.' - '.$item->balance,
                        'name' => $item->leave->name,
                        'citation' => $item->leave->citation,
                        'is_regular' => $item->leave->is_regular,
                        'is_after' => $item->leave->is_after,
                        'type' => $item->leave->type,
                        'others' => $item->leave->others,
                        'balance' => $item->balance,
                        'disabled'   => ($item->balance == 0 || $item->balance == 0.00),
                        'required_credits' => true,
                        'required_document' => false
                    ]
                ]);
            }

            $leaves = ListLeave::where(function ($query) use ($sex){
                $query->whereNull('sex')->orWhere('sex',$sex->name);
            })
            ->where('is_nonregular',1)->where('is_active',1)->get();
            foreach($leaves as $item){
                $options->push([
                    'label' => 'Require Documents',
                    'options' => [
                        'value' => $item->id,
                        'type_id' => $item->id,
                        'label' => $item->name,
                        'name' => $item->name,
                        'citation' => $item->citation,
                        'is_regular' => $item->is_regular,
                        'is_after' => $item->is_after,
                        'type' => $item->type,
                        'others' => $item->others,
                        'max_days' => $item->max_days,
                        'renewal' => $item->renewal_period,
                        'required_credits' => false,
                        'required_document' =>  $item->requires_document
                    ]
                ]);
            }

            $item = ListLeave::where('id', 16)
            ->where('is_active', 1)
            ->first();

            if ($item) {
                $options->push([
                    'label' => 'Others',
                    'options' => [
                        'value' => $item->id,
                        'type_id' => $item->id,
                        'label' => $item->name,
                        'name' => $item->name,
                        'citation' => $item->citation,
                        'is_regular' => $item->is_regular,
                        'is_after' => $item->is_after,
                        'type' => $item->type,
                        'others' => $item->others,
                        'required_credits' => false,
                        'required_document' => false
                    ]
                ]);
            }
        }

        $grouped = $options->groupBy('label')->map(function ($items) {
            return [
                'label' => $items->first()['label'],
                'options' => $items->pluck('options')->values()
            ];
        })->values();

        return $grouped;
    }
}
