<?php

namespace App\Services\FAIMS\Procurement;

use App\Http\Resources\FAIMS\Procurement\ModeOfProcurementResource;
use App\Models\ListDropdown;

class ModeOfProcurementClass
{
    protected string $classification = 'mode_of_procurement';
    protected array $legacyClassifications = [
        'modes_of_procurement',
        'Mode of Procurement',
    ];

    public function show($id)
    {
        $data = new ModeOfProcurementResource(
            ListDropdown::query()
                ->whereIn('classification', $this->classificationFilters())
                ->withCount('procurement_codes')
                ->findOrFail($id)
        );

        return $data;
    }

    public function lists($request)
    {
        return ModeOfProcurementResource::collection(
            ListDropdown::query()
                ->whereIn('classification', $this->classificationFilters())
                ->withCount('procurement_codes')
                ->when($request->keyword, function ($query, $keyword) {
                    $query->where(function ($innerQuery) use ($keyword) {
                        $innerQuery->where('name', 'LIKE', "%{$keyword}%")
                            ->orWhere('type', 'LIKE', "%{$keyword}%")
                            ->orWhere('others', 'LIKE', "%{$keyword}%");
                    });
                })
                ->orderByDesc('is_active')
                ->orderBy('type')
                ->orderBy('name')
                ->paginate($request->count ?? 10)
        );
    }

    public function save($request)
    {
        $modeOfProcurement = ListDropdown::create([
            'name' => trim((string) $request->name),
            'classification' => $this->classification,
            'type' => $this->normalizeOptionalValue($request->type),
            'color' => 'n/a',
            'others' => $this->normalizeOptionalValue($request->others),
            'is_active' => $request->boolean('is_active', true),
        ]);

        $modeOfProcurement->loadCount('procurement_codes');

        return [
            'data' => new ModeOfProcurementResource($modeOfProcurement),
            'message' => 'Mode of Procurement created successfully!',
            'info' => "You've successfully added a new Mode of Procurement.",
            'status' => 'success',
        ];
    }

    public function update($request, $id)
    {
        $modeOfProcurement = ListDropdown::query()
            ->whereIn('classification', $this->classificationFilters())
            ->findOrFail($id);

        $modeOfProcurement->update([
            'name' => trim((string) $request->name),
            'classification' => $this->classification,
            'type' => $this->normalizeOptionalValue($request->type),
            'others' => $this->normalizeOptionalValue($request->others),
            'is_active' => $request->boolean('is_active'),
        ]);

        $modeOfProcurement->loadCount('procurement_codes');

        return [
            'data' => new ModeOfProcurementResource($modeOfProcurement),
            'message' => 'Mode of Procurement updated successfully!',
            'info' => "You've successfully updated the Mode of Procurement.",
            'status' => 'success',
        ];
    }

    public function destroy($id)
    {
        $modeOfProcurement = ListDropdown::query()
            ->whereIn('classification', $this->classificationFilters())
            ->withCount('procurement_codes')
            ->findOrFail($id);

        if ((int) $modeOfProcurement->procurement_codes_count > 0) {
            return [
                'data' => new ModeOfProcurementResource($modeOfProcurement),
                'message' => 'Mode of Procurement cannot be deleted.',
                'info' => 'This mode of procurement is already used by existing PAP Codes.',
                'status' => 'error',
            ];
        }

        $modeOfProcurement->delete();

        return [
            'data' => null,
            'message' => 'Mode of Procurement deleted successfully!',
            'info' => "You've successfully deleted the Mode of Procurement.",
            'status' => 'success',
        ];
    }

    protected function normalizeOptionalValue($value): string
    {
        $normalized = trim((string) $value);

        return $normalized !== '' ? $normalized : 'n/a';
    }

    protected function classificationFilters(): array
    {
        return array_values(array_unique([
            $this->classification,
            ...$this->legacyClassifications,
        ]));
    }
}
