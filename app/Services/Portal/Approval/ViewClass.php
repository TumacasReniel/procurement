<?php

namespace App\Services\Portal\Approval;

use App\Http\Resources\Portal\Approval\TagResource;
use App\Models\ListStatus;
use App\Models\OrgSignatory;
use App\Models\Procurement;
use App\Models\Request;
use App\Models\RequestSignatory;
use Hashids\Hashids;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class ViewClass
{
    public function lists($request): JsonResponse
    {
        $signatories = $this->signatories();
        $hasRecommendationRole = $signatories->contains(fn($signatory) => $signatory->designationable?->designation_id == 44);
        $hasApprovalRole = $signatories->contains(fn($signatory) => $signatory->designationable?->designation_id == 43);
        $hasHRMORole = $signatories->contains(fn($signatory) => $signatory->designationable?->designation_id == 48);

        $requestApprovalUserIds = $signatories
            ->pluck('user_id')
            ->filter()
            ->unique()
            ->values();

        $procurementApproverIds = $requestApprovalUserIds
            ->push(auth()->id())
            ->filter()
            ->unique()
            ->values();

        $requestItems = collect(
            $this->requestSignatoryQuery(
                $request,
                $signatories,
                $requestApprovalUserIds,
                $hasRecommendationRole,
                $hasApprovalRole,
                $hasHRMORole
            )->get()->map(fn($item) => $this->transformRequestSignatory($item))->all()
        );

        $procurementItems = collect(
            $this->procurementQuery($request, $procurementApproverIds)
                ->get()
                ->map(fn($item) => $this->transformProcurement($item))
                ->all()
        );

        $items = $requestItems
            ->merge($procurementItems)
            ->sortBy(
                fn($item) => strtotime($item['sort_at'] ?? $item['created_at'] ?? now()->toDateTimeString()),
                SORT_REGULAR,
                filled($request->status)
            )
            ->values();

        return $this->paginateResponse($items, (int) ($request->count ?? 10));
    }

    public function count(): array
    {
        $signatories = $this->signatories();

        $procurementApproverIds = $signatories
            ->pluck('user_id')
            ->push(auth()->id())
            ->filter()
            ->unique()
            ->values();

        return [
            Request::whereHas('signatories', function ($query) {
                $query->whereHas('recommended', function ($subQuery) {
                    $subQuery->where('user_id', auth()->id());
                });
            })->count(),
            Request::whereHas('signatories', function ($query) {
                $query->whereHas('approved', function ($subQuery) {
                    $subQuery->where('user_id', auth()->id());
                });
            })->count() + $this->approvedProcurementCount($procurementApproverIds),
            Request::whereHas('signatories', function ($query) {
                $query->whereHas('disapproved', function ($subQuery) {
                    $subQuery->where('user_id', auth()->id());
                });
            })->count(),
        ];
    }

    protected function signatories(): Collection
    {
        return OrgSignatory::with('designationable')
            ->where(function ($query) {
                $query->where('user_id', auth()->id())
                    ->orWhere('oic_id', auth()->id());
            })
            ->where('is_active', 1)
            ->get();
    }

    protected function requestSignatoryQuery(
        $request,
        Collection $signatories,
        Collection $requestApprovalUserIds,
        bool $hasRecommendationRole,
        bool $hasApprovalRole,
        bool $hasHRMORole
    ): Builder {
        $status = $request->status ?? ($hasRecommendationRole || $hasHRMORole ? 24 : 25);

        return RequestSignatory::with([
            'status',
            'recommended',
            'approved',
            'disapproved',
            'request.tags.user:id',
            'request.tags.user.profile:user_id,firstname,middlename,lastname,avatar,suffix_id',
            'request.type',
            'request.dates',
            'request.detail',
            'request.travel.mode',
            'request.leave.type',
            'request.reservation.vehicle',
        ])
            ->when($request->type, function ($query, $type) {
                $query->whereHas('request', function ($requestQuery) use ($type) {
                    $requestQuery->where('type_id', $type);
                });
            })
            ->when($request->keyword, function ($query, $keyword) {
                $keyword = trim((string) $keyword);
                $keywordLower = strtolower($keyword);

                $query->where(function ($searchQuery) use ($keyword, $keywordLower) {
                    $searchQuery->where('code', 'LIKE', "%{$keyword}%")
                        ->orWhereHas('request', function ($requestQuery) use ($keyword, $keywordLower) {
                            $requestQuery->where('code', 'LIKE', "%{$keyword}%")
                                ->orWhereHas('type', function ($typeQuery) use ($keyword) {
                                    $typeQuery->where('name', 'LIKE', "%{$keyword}%");
                                })
                                ->orWhereHas('detail', function ($detailQuery) use ($keyword) {
                                    $detailQuery->where('purpose', 'LIKE', "%{$keyword}%")
                                        ->orWhere('remarks', 'LIKE', "%{$keyword}%");
                                })
                                ->orWhereHas('tags.user.profile', function ($profileQuery) use ($keywordLower) {
                                    $profileQuery->whereRaw('LOWER(firstname) LIKE ?', ["%{$keywordLower}%"])
                                        ->orWhereRaw('LOWER(lastname) LIKE ?', ["%{$keywordLower}%"])
                                        ->orWhereRaw("LOWER(CONCAT(firstname, ' ', lastname)) LIKE ?", ["%{$keywordLower}%"]);
                                });
                        });
                });
            })
            ->when(blank($request->status), fn($query) => $query->where('status_id', $status))
            ->when(true, function ($query) use (
                $request,
                $signatories,
                $requestApprovalUserIds,
                $hasRecommendationRole,
                $hasApprovalRole,
                $hasHRMORole
            ) {
                if (filled($request->status)) {
                    if ((int) $request->status === 26) {
                        $query->whereHas('approved', function ($subQuery) {
                            $subQuery->where('user_id', auth()->id());
                        });
                    } elseif ((int) $request->status === 25) {
                        $query->whereHas('recommended', function ($subQuery) {
                            $subQuery->where('user_id', auth()->id());
                        });
                    } else {
                        $query->whereHas('disapproved', function ($subQuery) {
                            $subQuery->where('user_id', auth()->id());
                        })->where('is_disapproved', 1);
                    }

                    return;
                }

                $divisionIds = $signatories
                    ->filter(fn($signatory) => $signatory->designationable?->designation_id == 44)
                    ->pluck('designationable.assigned_id')
                    ->filter()
                    ->unique()
                    ->values();

                if (
                    !($hasApprovalRole && $requestApprovalUserIds->isNotEmpty()) &&
                    !$hasHRMORole &&
                    $divisionIds->isEmpty()
                ) {
                    $query->whereRaw('1 = 0');

                    return;
                }

                $query->where(function ($visibilityQuery) use (
                    $divisionIds,
                    $requestApprovalUserIds,
                    $hasApprovalRole,
                    $hasHRMORole
                ) {
                    if ($hasApprovalRole && $requestApprovalUserIds->isNotEmpty()) {
                        $visibilityQuery->orWhereHas('approved', function ($approvedQuery) use ($requestApprovalUserIds) {
                            $approvedQuery->whereIn('user_id', $requestApprovalUserIds);
                        });
                    }

                    if ($hasHRMORole) {
                        $visibilityQuery->orWhere(function ($hrmoQuery) {
                            $hrmoQuery->where('division_id', 48)
                                ->where('is_approval_only', 0);
                        });
                    }

                    if ($divisionIds->isNotEmpty()) {
                        $visibilityQuery->orWhere(function ($recommendationQuery) use ($divisionIds) {
                            $recommendationQuery->whereIn('division_id', $divisionIds)
                                ->where('is_approval_only', 0);
                        });
                    }
                });
            });
    }

    protected function procurementQuery($request, Collection $procurementApproverIds): Builder
    {
        $reviewedStatusId = ListStatus::getID('Reviewed', 'Procurement');
        $approvedStatusId = ListStatus::getID('Approved', 'Procurement');

        return Procurement::with([
            'request',
            'status',
            'classification',
            'requested_by.profile',
            'created_by.profile',
            'approved_by.profile',
        ])
            ->whereIn('approved_by_id', $procurementApproverIds)
            ->when($request->keyword, function ($query, $keyword) {
                $keyword = trim((string) $keyword);
                $keywordLower = strtolower($keyword);

                $query->where(function ($searchQuery) use ($keyword, $keywordLower) {
                    $searchQuery->where('code', 'LIKE', "%{$keyword}%")
                        ->orWhere('title', 'LIKE', "%{$keyword}%")
                        ->orWhere('purpose', 'LIKE', "%{$keyword}%")
                        ->orWhereHas('request', function ($requestQuery) use ($keyword) {
                            $requestQuery->where('code', 'LIKE', "%{$keyword}%");
                        })
                        ->orWhereHas('requested_by.profile', function ($profileQuery) use ($keywordLower) {
                            $profileQuery->whereRaw('LOWER(firstname) LIKE ?', ["%{$keywordLower}%"])
                                ->orWhereRaw('LOWER(lastname) LIKE ?', ["%{$keywordLower}%"])
                                ->orWhereRaw("LOWER(CONCAT(firstname, ' ', lastname)) LIKE ?", ["%{$keywordLower}%"]);
                        });
                });
            })
            ->when(filled($request->status), function ($query) use ($request, $approvedStatusId) {
                if ((int) $request->status === 26) {
                    $query->where('status_id', $approvedStatusId);
                } else {
                    $query->whereRaw('1 = 0');
                }
            }, function ($query) use ($reviewedStatusId) {
                $query->where('status_id', $reviewedStatusId);
            });
    }

    protected function transformRequestSignatory(RequestSignatory $item): array
    {
        $hashids = new Hashids('krad', 10);
        $key = $hashids->encode($item->id);
        $link = Str::slug($item->request->type->name) . 'krad' . $key;
        $encryptedLink = Crypt::encryptString($link);

        return [
            'id' => $item->id,
            'key' => $key,
            'code' => $item->code,
            'request_code' => $item->request->code,
            'type' => $item->request->type->name,
            'link' => $encryptedLink,
            'href' => "/approvals/{$encryptedLink}",
            'purpose' => $item->request->detail?->purpose,
            'remarks' => $item->request->detail?->remarks,
            'start' => $item->request->dates->first()?->start,
            'end' => $item->request->dates->first()?->end,
            'status' => $this->normalizeStatus($item->status),
            'tags' => TagResource::collection($item->request->tags)->resolve(),
            'created_at' => $item->request->created_at,
            'updated_at' => $item->request->updated_at,
            'subtype' => $this->requestSubtype($item),
            'sort_at' => optional($item->created_at)->toDateTimeString(),
        ];
    }

    protected function transformProcurement(Procurement $item): array
    {
        $isAwaitingApproval = $item->status?->name === 'Reviewed';
        $people = collect([$item->requested_by, $item->created_by])
            ->filter()
            ->unique('id')
            ->values();

        return [
            'id' => 'procurement-' . $item->id,
            'key' => null,
            'code' => $item->code,
            'request_code' => $item->request?->code ?? $item->code,
            'type' => 'Procurement',
            'link' => null,
            'href' => "/faims/procurements/{$item->id}?option=" . ($isAwaitingApproval ? 'approve' : 'view'),
            'purpose' => $item->purpose,
            'remarks' => null,
            'start' => $item->date,
            'end' => $item->date,
            'status' => $this->normalizeStatus($item->status, 'bg-warning'),
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
            'updated_at' => optional($item->updated_at)->format('M d, Y g:i a'),
            'subtype' => $item->classification?->name ?? 'Procurement',
            'sort_at' => optional($item->created_at)->toDateTimeString(),
        ];
    }

    protected function requestSubtype(RequestSignatory $item): ?string
    {
        return match ($item->request->type->name) {
            'Travel Order' => $item->request->travel?->mode?->name,
            'Leave Form' => $item->request->leave?->type?->name,
            'Render Overtime Service' => $item->request->type->name,
            default => $item->request->reservation?->vehicle?->name,
        };
    }

    protected function normalizeStatus($status, string $fallbackBg = 'bg-secondary'): array
    {
        return [
            'id' => $status?->id,
            'name' => $status?->name,
            'bg' => (!empty($status?->bg) && $status->bg !== 'n/a') ? $status->bg : $fallbackBg,
            'color' => (!empty($status?->color) && $status->color !== 'n/a') ? $status->color : 'text-dark',
            'icon' => $status?->icon,
        ];
    }

    protected function approvedProcurementCount(Collection $procurementApproverIds): int
    {
        if ($procurementApproverIds->isEmpty()) {
            return 0;
        }

        return Procurement::whereIn('approved_by_id', $procurementApproverIds)
            ->where('status_id', ListStatus::getID('Approved', 'Procurement'))
            ->count();
    }

    protected function paginateResponse(Collection $items, int $perPage): JsonResponse
    {
        $perPage = $perPage > 0 ? $perPage : 10;
        $currentPage = max((int) request('page', 1), 1);
        $total = $items->count();
        $lastPage = max((int) ceil(max($total, 1) / $perPage), 1);
        $path = url('/approvals');

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
}
