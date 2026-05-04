<?php

namespace App\Http\Resources\FAIMS\Procurement;

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
        $plan_type = $this->plan_type($plan_name);
        $ppmp_status = $this->ppmp_status($plan_name);
        $reviewed_by = $ppmp_status === 'Final' ? $this->approved_by?->profile?->full_name : null;
        $approval_status = $this->approval_status($plan_name);
        $can_add_items = !$plan_name && !$this->has_completed_status();
        $year = $this->date ? date('Y', strtotime($this->date)) : date('Y', strtotime((string) $this->created_at));
        $ppmp_no = $this->ppmp_no_override ?: 'PPMP-' . $year . '-' . str_pad((string) $this->id, 4, '0', STR_PAD_LEFT);
        $start_date = $this->start_date_override ?: $this->date;

        return [
            'id' => $this->id,
            'code' => $this->code,
            'pr_no' => $this->pr_no_override ?: $this->code,
            'ppmp_no' => $ppmp_no,
            'ppmp_status' => $ppmp_status,
            'approval_status' => $approval_status,
            'is_final' => $ppmp_status === 'Final',
            'is_pending_app_approval' => $approval_status === 'For Procurement Officer Approval',
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
            'reviewed_by' => $reviewed_by,
            'reviewed_at' => $reviewed_by ? $this->updated_at : null,
            'prepared_by' => $this->created_by?->profile?->full_name,
            'submitted_by' => $this->requested_by?->profile?->full_name,
            'codes' => $this->codes,
            'source_ppmps' => $this->source_ppmps(),
            'item_details' => $this->item_details($items),
            'items_count' => $items->count(),
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
                $unit = trim((string) ($item->item_unit_type?->name ?? ''));
                $name = trim((string) ($item->item_name ?? $item->item_description ?? ''));

                return trim($quantity . ' ' . $unit . ($name ? ' - ' . $name : ''));
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

                return [
                    'id' => $item->id,
                    'item_no' => $item->item_no,
                    'name' => $item->item_name,
                    'description' => $item->item_description,
                    'quantity' => $quantity,
                    'unit' => $item->item_unit_type?->name,
                    'unit_price' => round($unit_cost, 2),
                    'abc' => round((float) ($item->total_cost ?? ($quantity * $unit_cost)), 2),
                    'status' => $item->status,
                ];
            })
            ->values();
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
            ?: ($plan_name || $this->status?->name === 'Reviewed' ? 'Final' : 'Indicative');
    }

    protected function approval_status(?string $plan_name): string
    {
        return $this->approval_status_override ?: match ($this->status?->name) {
            'Reviewed' => 'For Procurement Officer Approval',
            'Approved' => $plan_name === 'Supplemental Procurement Plan'
                ? 'Approved in SPP'
                : ($plan_name ? 'Approved in APP' : 'Approved'),
            default => 'Draft',
        };
    }

    protected function source_ppmps(): Collection
    {
        return collect($this->source_ppmps_override ?: [])
            ->map(fn ($source) => [
                'id' => data_get($source, 'id'),
                'ppmp_no' => data_get($source, 'ppmp_no'),
                'unit' => data_get($source, 'unit'),
                'division' => data_get($source, 'division'),
                'pr_no' => data_get($source, 'pr_no'),
                'items_count' => (int) data_get($source, 'items_count', 0),
                'total_amount' => round((float) data_get($source, 'total_amount', 0), 2),
                'approval_status' => data_get($source, 'approval_status', 'Approved in APP/SPP'),
            ])
            ->values();
    }

    protected function can_mark_final_ppmp(?string $plan_name, string $plan_type): bool
    {
        return !$plan_name
            && $plan_type === 'ppmp'
            && !$this->has_completed_status()
            && auth()->user()?->hasRole('Procurement Officer');
    }

    protected function can_approve_to_app(string $approval_status): bool
    {
        return $approval_status === 'For Procurement Officer Approval'
            && (
                auth()->user()?->hasRole('Procurement Officer')
                || auth()->user()?->hasRole('Administrator')
            );
    }

    protected function has_completed_status(): bool
    {
        return in_array($this->status?->name, ['Reviewed', 'Approved'], true);
    }
}
