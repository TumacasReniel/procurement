<?php

namespace App\Http\Resources\FAIMS\Procurement;

use App\Models\ListStatus;
use App\Models\ProcurementPpmp;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class ProcurementPPMPResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $items = $this->items();
        $total_amount = $this->total_amount($items);
        $plan_name = $this->plan_name_override ?: $this->reference_app?->name;
        $plan_name = $plan_name === 'PPMP' ? null : $plan_name;
        $plan_type = $this->plan_type($plan_name);
        $ppmp_status = $this->ppmp_status($plan_name);
        $is_final = $this->is_final_ppmp($plan_name);
        $is_reviewed = in_array($this->status?->name, ['Reviewed', 'Approved'], true);
        $reviewed_by = $is_reviewed ? $this->approved_by?->profile?->full_name : null;
        $submitted_by = $is_final
            ? ($this->approved_by?->profile?->full_name ?? $this->requested_by?->profile?->full_name)
            : $this->requested_by?->profile?->full_name;
        $prepared_by_designation = $this->created_by?->org_chart?->designation?->name
            ?? $this->created_by?->organization?->position?->name
            ?? $this->created_by?->designation;
        $approval_status = $this->approval_status($plan_name);
        $is_consolidated = str_starts_with($approval_status, 'Consolidated/Added to');
        $consolidated_by = $is_consolidated ? $this->approved_by?->profile?->full_name : null;
        $consolidated_at = $is_consolidated && $this->approved_by_id ? $this->updated_at : null;
        $can_add_items = ! $plan_name && ! $this->has_completed_status();
        $year = $this->date ? date('Y', strtotime($this->date)) : date('Y', strtotime((string) $this->created_at));
        $ppmp_no = $this->ppmp_no_override ?: 'PPMP-'.$year.'-'.str_pad((string) $this->id, 4, '0', STR_PAD_LEFT);
        $start_date = $this->start_date_override ?: $this->date;
        $item_details = $this->item_details($items);
        $consolidated_item_details = $plan_type === 'ppmp'
            ? $item_details
            : $this->consolidated_item_details($item_details);

        return [
            'id' => $this->id,
            'code' => $this->code,
            'pr_no' => $this->pr_no_override ?: $this->code,
            'ppmp_no' => $ppmp_no,
            'ppmp_status' => $ppmp_status,
            'approval_status' => $approval_status,
            'is_final' => $is_final,
            'is_pending_app_approval' => $approval_status === 'Submitted/For Consolidation',
            'can_submit_final' => $this->can_mark_final_ppmp($plan_name, $plan_type),
            'can_add_items' => $can_add_items,
            'can_approve_to_app' => $this->can_approve_to_app($approval_status),
            'plan_name' => $plan_name ?: 'PPMP',
            'plan_type' => $plan_type,
            'date' => $this->date,
            'formatted_date' => $this->date ? date('F j, Y', strtotime($this->date)) : null,
            'general_description_objective' => $this->title ?: $this->purpose,
            'type_of_project' => $this->classification_override ?: $this->classification?->name,
            'quantity_and_size' => $this->quantity_and_size($items),
            'recommended_mode_of_procurement' => $this->mode_of_procurement(),
            'pre_procurement_conference' => null,
            'start_of_procurement_activity' => $start_date,
            'end_of_procurement_activity' => null,
            'expected_delivery_implementation_period' => null,
            'source_of_funds' => $this->source_of_funds_override ?: $this->fund_cluster?->name,
            'estimated_budget' => round($total_amount, 2),
            'attached_supporting_documents' => null,
            'remarks' => null,
            'purpose' => $this->purpose,
            'title' => $this->title,
            'division' => $this->division,
            'unit' => $this->unit_override ?: $this->unit,
            'fund_cluster' => $this->fund_cluster,
            'classification' => $this->classification,
            'reference_app' => $this->reference_app,
            'created_by' => $this->created_by?->profile?->full_name,
            'created_by_id' => $this->created_by_id,
            'requested_by' => $this->requested_by?->profile?->full_name,
            'requested_by_id' => $this->requested_by_id,
            'approved_by' => $this->approved_by?->profile?->full_name,
            'approved_by_id' => $this->approved_by_id,
            'approved_at' => $this->approved_by_id ? $this->updated_at : null,
            'formatted_approved_at' => $this->approved_by_id && $this->updated_at
                ? date('F j, Y', strtotime((string) $this->updated_at))
                : null,
            'consolidated_by' => $consolidated_by,
            'consolidated_at' => $consolidated_at,
            'formatted_consolidated_at' => $consolidated_at
                ? date('F j, Y', strtotime((string) $consolidated_at))
                : null,
            'comments_count' => $this->comments_count ?? 0,
            'reviewed_by' => $reviewed_by,
            'reviewed_at' => $reviewed_by ? $this->updated_at : null,
            'formatted_reviewed_at' => $reviewed_by && $this->updated_at
                ? date('F j, Y', strtotime((string) $this->updated_at))
                : null,
            'prepared_by' => $this->created_by?->profile?->full_name,
            'prepared_by_designation' => $prepared_by_designation,
            'submitted_by' => $submitted_by,
            'codes' => $this->codes,
            'source_ppmps' => $this->source_ppmps(),
            'item_details' => $consolidated_item_details,
            'raw_item_details' => $item_details,
            'consolidation_match_groups' => $this->consolidation_match_groups($item_details, $approval_status),
            'consolidation_average_groups' => $this->consolidation_average_groups($item_details, $approval_status),
            'items_count' => $items->count(),
            'consolidated_items_count' => $consolidated_item_details->count(),
            'ppmp_count' => $this->aggregated_ppmp_count ?: 1,
            'total_amount' => round($total_amount, 2),
            'status' => $this->status,
            'sub_status' => $this->sub_status,
        ];
    }

    protected function items(): Collection
    {
        return $this->whenLoaded('items', fn () => $this->items, collect());
    }

    protected function total_amount(Collection $items): float
    {
        return (float) $items->sum(fn ($item) => (float) ($item->total_cost ?? 0));
    }

    protected function mode_of_procurement(): ?string
    {
        return $this->codes
            ?->pluck('procurement_code.mode_of_procurement.name')
            ->filter()
            ->unique()
            ->implode(', ');
    }

    protected function quantity_and_size(Collection $items): string
    {
        return $items
            ->map(function ($item) {
                $quantity = trim((string) ($item->item_quantity ?? ''));
                $unit = trim((string) $this->item_unit_label($item));
                $name = trim((string) ($item->item_name ?? $item->item_description ?? ''));

                return trim($quantity.' '.$unit.($name ? ' - '.$name : ''));
            })
            ->filter()
            ->values()
            ->implode('; ');
    }

    protected function item_details(Collection $items): Collection
    {
        return $items
            ->map(function ($item) {
                $quantity = (float) ($item->item_quantity ?? 0);
                $unit_cost = (float) ($item->item_unit_cost ?? 0);
                $pr_no = $item->pr_items
                    ?->pluck('procurement.code')
                    ->filter()
                    ->unique()
                    ->implode(', ');

                return [
                    'id' => $item->id,
                    'pr_id' => $item->pr_items?->pluck('procurement.id')->filter()->first(),
                    'pr_no' => $pr_no ?: null,
                    'ppmp_no' => $this->item_ppmp_no($item),
                    'item_no' => $item->item_no,
                    'name' => $item->item_name,
                    'description' => $item->item_description,
                    'item_unit_type_id' => $item->item_unit_type_id,
                    'project_type' => $item->project_type,
                    'item_category_id' => $item->item_category_id,
                    'item_category' => $item->item_category?->name,
                    'recommended_mode_of_procurement' => $item->recommended_mode_of_procurement,
                    'pre_procurement_conference' => $item->pre_procurement_conference,
                    'quantity' => $quantity,
                    'unit' => $this->item_unit_label($item),
                    'unit_price' => round($unit_cost, 2),
                    'abc' => round((float) ($item->total_cost ?? ($quantity * $unit_cost)), 2),
                    'end_of_procurement_activity' => $item->end_of_procurement_activity,
                    'expected_delivery_date' => $item->expected_delivery_date,
                    'attached_supporting_documents' => $item->attached_supporting_documents,
                    'supporting_document_path' => $item->supporting_document_path,
                    'supporting_document_original_name' => $item->supporting_document_original_name,
                    'supporting_document_url' => $item->supporting_document_path
                        ? asset('storage/'.$item->supporting_document_path)
                        : null,
                    'remarks' => $item->remarks,
                    'status' => $item->status,
                ];
            })
            ->values();
    }

    protected function consolidated_item_details(Collection $item_details): Collection
    {
        $groups = collect();

        foreach ($item_details as $item) {
            $group_key = $this->consolidation_group_key($item);

            if (! $groups->has($group_key)) {
                $groups->put($group_key, [
                    'id' => $item['id'],
                    'source_item_ids' => [],
                    'name' => $item['name'],
                    'description' => $item['description'],
                    'item_unit_type_id' => $item['item_unit_type_id'],
                    'project_type' => $item['project_type'],
                    'item_category_id' => $item['item_category_id'],
                    'item_category' => $item['item_category'],
                    'recommended_mode_of_procurement' => $item['recommended_mode_of_procurement'],
                    'pre_procurement_conference' => $item['pre_procurement_conference'],
                    'end_of_procurement_activity' => $item['end_of_procurement_activity'],
                    'expected_delivery_date' => $item['expected_delivery_date'],
                    'quantity' => 0,
                    'unit' => $item['unit'],
                    'unit_prices' => [],
                    'unit_price' => 0,
                    'abc' => 0,
                    'pr_nos' => [],
                    'ppmp_nos' => [],
                    'source_pr_nos' => [],
                    'source_ppmp_nos' => [],
                    'consolidated_count' => 0,
                    'attached_supporting_documents' => $item['attached_supporting_documents'],
                    'supporting_document_original_name' => $item['supporting_document_original_name'],
                    'remarks' => $item['remarks'],
                    'status' => $item['status'],
                ]);
            }

            $group = $groups->get($group_key);
            $quantity = (float) ($item['quantity'] ?? 0);
            $abc = (float) ($item['abc'] ?? 0);

            $group['source_item_ids'][] = $item['id'];
            $group['quantity'] += $quantity;
            $group['abc'] += $abc;
            $group['consolidated_count'] += 1;

            if (($item['unit_price'] ?? null) !== null) {
                $group['unit_prices'][] = (float) $item['unit_price'];
            }

            foreach (explode(',', (string) ($item['pr_no'] ?? '')) as $pr_no) {
                $pr_no = trim($pr_no);

                if ($pr_no !== '') {
                    $group['pr_nos'][$pr_no] = $pr_no;
                }
            }

            foreach (explode(',', (string) ($item['ppmp_no'] ?? '')) as $ppmp_no) {
                $ppmp_no = trim($ppmp_no);

                if ($ppmp_no !== '') {
                    $group['ppmp_nos'][$ppmp_no] = $ppmp_no;
                }
            }

            $groups->put($group_key, $group);
        }

        return $groups
            ->values()
            ->map(function ($group, $index) {
                $quantity = (float) $group['quantity'];
                $abc = (float) $group['abc'];
                $average_unit_price = $quantity > 0
                    ? $abc / $quantity
                    : collect($group['unit_prices'])->avg();
                $pr_nos = $group['pr_nos'];
                $ppmp_nos = $group['ppmp_nos'];

                unset($group['pr_nos'], $group['ppmp_nos'], $group['unit_prices']);

                return array_merge($group, [
                    'id' => 'consolidated-'.($index + 1),
                    'item_no' => $index + 1,
                    'quantity' => round($quantity, 2),
                    'unit_price' => round((float) $average_unit_price, 2),
                    'abc' => round($abc, 2),
                    'pr_no' => implode(', ', $pr_nos),
                    'ppmp_no' => implode(', ', $ppmp_nos),
                    'source_pr_nos' => array_values($pr_nos),
                    'source_ppmp_nos' => array_values($ppmp_nos),
                ]);
            });
    }

    protected function consolidation_group_key(array $item): string
    {
        return implode('|', [
            $item['item_category_id'] ?? '',
            $item['item_unit_type_id'] ?? '',
            $this->normalize_consolidation_text($item['project_type'] ?? ''),
            $this->normalize_consolidation_text($item['name'] ?? ''),
            $this->normalize_consolidation_text($item['description'] ?? ''),
        ]);
    }

    protected function normalize_consolidation_text($value): string
    {
        $text = html_entity_decode(strip_tags(strtolower((string) $value)));
        $text = preg_replace('/[^a-z0-9.\s-]+/', ' ', $text);
        $text = preg_replace('/\s+/', ' ', (string) $text);

        return trim((string) $text);
    }

    protected function consolidation_average_groups(Collection $current_items, string $approval_status): array
    {
        if ($approval_status !== 'Submitted/For Consolidation' || $current_items->isEmpty()) {
            return [];
        }

        $existing_items = $this->existing_consolidation_items();

        return $current_items
            ->map(fn ($item) => array_merge($item, ['source_type' => 'current']))
            ->merge($existing_items->map(fn ($item) => array_merge($item, ['source_type' => 'existing'])))
            ->groupBy(fn ($item) => $this->consolidation_group_key($item))
            ->filter(function (Collection $items) {
                $has_current_item = $items->contains(fn ($item) => ($item['source_type'] ?? null) === 'current');
                $has_existing_item = $items->contains(fn ($item) => ($item['source_type'] ?? null) === 'existing');
                $unit_prices = $items
                    ->pluck('unit_price')
                    ->map(fn ($price) => round((float) $price, 2))
                    ->unique()
                    ->values();

                return $has_current_item && $has_existing_item && $unit_prices->count() > 1;
            })
            ->values()
            ->map(function (Collection $items, int $index) {
                $quantity = $items->sum(fn ($item) => (float) ($item['quantity'] ?? 0));
                $abc = $items->sum(fn ($item) => (float) ($item['abc'] ?? 0));
                $representative = $items->first();

                return [
                    'id' => $index + 1,
                    'name' => $representative['name'] ?? '-',
                    'description' => $representative['description'] ?? null,
                    'unit' => $representative['unit'] ?? null,
                    'quantity' => round($quantity, 2),
                    'average_unit_price' => $quantity > 0 ? round($abc / $quantity, 2) : 0,
                    'total_amount' => round($abc, 2),
                    'items' => $items
                        ->map(fn ($item) => [
                            'source' => ($item['source_type'] ?? null) === 'current' ? 'This PPMP' : 'Existing APP/Approved PPMP',
                            'ppmp_no' => $item['ppmp_no'] ?? null,
                            'pr_no' => $item['pr_no'] ?? null,
                            'quantity' => round((float) ($item['quantity'] ?? 0), 2),
                            'unit_price' => round((float) ($item['unit_price'] ?? 0), 2),
                            'abc' => round((float) ($item['abc'] ?? 0), 2),
                        ])
                        ->values()
                        ->all(),
                ];
            })
            ->all();
    }

    protected function consolidation_match_groups(Collection $current_items, string $approval_status): array
    {
        if ($approval_status !== 'Submitted/For Consolidation' || $current_items->isEmpty()) {
            return [];
        }

        $existing_items = $this->existing_consolidation_items();

        if ($existing_items->isEmpty()) {
            return [];
        }

        return $current_items
            ->map(function (array $current_item, int $index) use ($existing_items) {
                $current_key = $this->consolidation_group_key($current_item);
                $current_keywords = $this->consolidation_keywords($current_item);

                $matches = $existing_items
                    ->map(function (array $existing_item) use ($current_item, $current_key, $current_keywords) {
                        $existing_key = $this->consolidation_group_key($existing_item);
                        $shared_keywords = array_values(array_intersect(
                            $current_keywords,
                            $this->consolidation_keywords($existing_item)
                        ));
                        $same_specs = $current_key === $existing_key;

                        if (! $same_specs && count($shared_keywords) < 2) {
                            return null;
                        }

                        return [
                            'id' => $existing_item['id'] ?? null,
                            'name' => $existing_item['name'] ?? '-',
                            'description' => $existing_item['description'] ?? null,
                            'ppmp_no' => $existing_item['ppmp_no'] ?? null,
                            'pr_no' => $existing_item['pr_no'] ?? null,
                            'quantity' => round((float) ($existing_item['quantity'] ?? 0), 2),
                            'unit' => $existing_item['unit'] ?? null,
                            'unit_price' => round((float) ($existing_item['unit_price'] ?? 0), 2),
                            'abc' => round((float) ($existing_item['abc'] ?? 0), 2),
                            'match_reason' => $same_specs ? 'Same specs/description' : 'Shared description keywords',
                            'matched_keywords' => $same_specs ? [] : array_slice($shared_keywords, 0, 8),
                        ];
                    })
                    ->filter()
                    ->values();

                if ($matches->isEmpty()) {
                    return null;
                }

                return [
                    'id' => $current_item['id'] ?? $index + 1,
                    'name' => $current_item['name'] ?? '-',
                    'description' => $current_item['description'] ?? null,
                    'ppmp_no' => $current_item['ppmp_no'] ?? null,
                    'pr_no' => $current_item['pr_no'] ?? null,
                    'quantity' => round((float) ($current_item['quantity'] ?? 0), 2),
                    'unit' => $current_item['unit'] ?? null,
                    'unit_price' => round((float) ($current_item['unit_price'] ?? 0), 2),
                    'abc' => round((float) ($current_item['abc'] ?? 0), 2),
                    'matches' => $matches->all(),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    protected function existing_consolidation_items(): Collection
    {
        $approved_status_id = ListStatus::query()
            ->where('classification', 'Procurement')
            ->where('name', 'Approved')
            ->value('id');

        if (! $approved_status_id) {
            return collect();
        }

        $year = $this->date
            ? date('Y', strtotime((string) $this->date))
            : date('Y', strtotime((string) $this->created_at));

        $existing_procurements = ProcurementPpmp::query()
            ->with(['items.procurement', 'items.item_unit_type', 'items.item_category', 'items.status', 'items.pr_items.procurement'])
            ->whereKeyNot($this->id)
            ->whereYear('date', $year)
            ->where(function ($query) use ($approved_status_id) {
                $query->where('status_id', $approved_status_id)
                    ->orWhereHas('reference_app', fn ($reference_query) => $reference_query->where('name', 'Annual Procurement Plan'));
            })
            ->get();

        return $this->item_details(
            $existing_procurements
                ->flatMap(fn ($procurement) => $procurement->items ?? collect())
                ->values()
        );
    }

    protected function consolidation_keywords(array $item): array
    {
        $text = $this->normalize_consolidation_text(
            implode(' ', [
                $item['name'] ?? '',
                $item['description'] ?? '',
            ])
        );

        $stop_words = [
            'and', 'for', 'the', 'with', 'pcs', 'piece', 'pieces', 'unit', 'units',
            'set', 'sets', 'lot', 'lots', 'each', 'item', 'items', 'supply', 'supplies',
        ];

        return collect(explode(' ', $text))
            ->map(fn ($word) => trim($word, '.- '))
            ->filter(fn ($word) => strlen($word) >= 3 && ! in_array($word, $stop_words, true))
            ->unique()
            ->values()
            ->all();
    }

    protected function item_unit_label($item): ?string
    {
        $quantity = (float) ($item->item_quantity ?? 0);

        if ($quantity > 1) {
            return $item->item_unit_type?->name_long
                ?? $item->item_unit_type?->name
                ?? $item->item_unit_type?->name_short;
        }

        return $item->item_unit_type?->name_short
            ?? $item->item_unit_type?->name
            ?? $item->item_unit_type?->name_long;
    }

    protected function item_ppmp_no($item): ?string
    {
        $procurement = $item->procurement;

        if (! $procurement) {
            return null;
        }

        $year = $procurement->date
            ? date('Y', strtotime($procurement->date))
            : date('Y', strtotime((string) $procurement->created_at));

        return 'PPMP-'.$year.'-'.str_pad((string) $procurement->id, 4, '0', STR_PAD_LEFT);
    }

    protected function plan_type(?string $plan_name): string
    {
        return match ($plan_name) {
            'Annual Procurement Plan' => 'annual',
            'Supplemental Procurement Plan' => 'supplemental',
            default => 'ppmp',
        };
    }

    protected function ppmp_status(?string $plan_name): string
    {
        return $this->ppmp_status_override
            ?: match ($this->status?->name) {
                'Reviewed' => 'Reviewed/For Submission',
                'Approved' => $plan_name ? 'Consolidated/Added to APP' : 'Submitted/For Consolidation',
                default => 'Pending',
            };
    }

    protected function approval_status(?string $plan_name): string
    {
        return $this->approval_status_override ?: match ($this->status?->name) {
            'Reviewed' => 'Reviewed/For Submission',
            'Approved' => $plan_name === 'Supplemental Procurement Plan'
                ? 'Consolidated/Added to SPP'
                : ($plan_name ? 'Consolidated/Added to APP' : 'Submitted/For Consolidation'),
            default => 'Pending',
        };
    }

    protected function source_ppmps(): Collection
    {
        return collect($this->source_ppmps_override ?: [])
            ->map(fn ($source) => [
                'id' => data_get($source, 'id'),
                'ppmp_no' => data_get($source, 'ppmp_no'),
                'unit_id' => data_get($source, 'unit_id'),
                'unit' => $this->source_label(data_get($source, 'unit')),
                'division' => data_get($source, 'division'),
                'pr_no' => data_get($source, 'pr_no'),
                'items_count' => (int) data_get($source, 'items_count', 0),
                'total_amount' => round((float) data_get($source, 'total_amount', 0), 2),
                'approval_status' => data_get($source, 'approval_status', 'Consolidated/Added to APP/SPP'),
            ])
            ->values();
    }

    protected function source_label($value): ?string
    {
        if (! $value) {
            return null;
        }

        if (is_string($value)) {
            return $value;
        }

        $label = data_get($value, 'name')
            ?? data_get($value, 'label')
            ?? data_get($value, 'short')
            ?? data_get($value, 'value');

        return $label ? (string) $label : null;
    }

    protected function can_mark_final_ppmp(?string $plan_name, string $plan_type): bool
    {
        return ! $plan_name
            && $plan_type === 'ppmp'
            && $this->can_advance_ppmp_status();
    }

    protected function can_approve_to_app(string $approval_status): bool
    {
        return $approval_status === 'Submitted/For Consolidation'
            && $this->can_consolidate_ppmp();
    }

    protected function is_final_ppmp(?string $plan_name): bool
    {
        return (bool) $plan_name || $this->status?->name === 'Approved';
    }

    protected function has_completed_status(): bool
    {
        return in_array($this->status?->name, ['Reviewed', 'Approved'], true);
    }

    protected function can_advance_ppmp_status(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if ($user->hasRole('Administrator')) {
            return true;
        }

        return match ($this->status?->name) {
            'Pending' => $user->hasRole('Budget Officer'),
            'Reviewed' => $user->hasRole('Procurement Officer'),
            default => false,
        };
    }

    protected function can_consolidate_ppmp(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        foreach (['Administrator', 'BAC User', 'BAC Chairperson', 'BAC Vice Chairperson', 'BAC Member'] as $role) {
            if ($user->hasRole($role)) {
                return true;
            }
        }

        return in_array($user->org_chart?->designation?->name, ['BAC Chairperson', 'BAC Vice Chairperson', 'BAC Member'], true);
    }
}
