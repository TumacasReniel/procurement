<?php

namespace App\Services\FAIMS\Procurement;

use App\Models\Supplier;
use App\Http\Resources\FAIMS\Procurement\SupplierResource;
use Illuminate\Support\Facades\Auth;

class SupplierClass
{
    public function lists($request)
    {
        $data = SupplierResource::collection(
            Supplier::query()
            ->with(['address', 'conformes', 'attachments', 'created_by.profile'])
            ->withCount(['conformes', 'attachments'])
            ->when($request->keyword, function ($query, $keyword) {
                $query->where(function ($searchQuery) use ($keyword) {
                    $searchQuery->where('name', 'LIKE', "%{$keyword}%")
                        ->orWhere('code', 'LIKE', "%{$keyword}%")
                        ->orWhereHas('address', function ($q) use ($keyword) {
                            $q->where('address', 'LIKE', "%{$keyword}%");
                        })
                        ->orWhereHas('conformes', function ($q) use ($keyword) {
                            $q->where('name', 'LIKE', "%{$keyword}%")
                                ->orWhere('position', 'LIKE', "%{$keyword}%")
                                ->orWhere('contact_no', 'LIKE', "%{$keyword}%");
                        })
                        ->orWhereHas('created_by.profile', function ($q) use ($keyword) {
                            $q->where('firstname', 'LIKE', "%{$keyword}%")
                                ->orWhere('middlename', 'LIKE', "%{$keyword}%")
                                ->orWhere('lastname', 'LIKE', "%{$keyword}%");
                        });
                });
            })
            ->when($request->status !== null && $request->status !== '', function ($query) use ($request) {
                $query->where('is_active', $request->status);
            })
            ->orderBy('created_at', 'DESC')
            ->paginate($request->count ?: 10)
        );

        return $data;
    }

    public function save($request)
    {
        $code = Supplier::generateCode();

        $supplier = Supplier::create([
            'name' => $request->name,
            'code' => $code,
            'is_active' => $request->is_active ?? 1,
            'user_id' => Auth::id(),
        ]);

        if ($request->address) {
            $supplier->address()->create(['address' => $request->address]);
        }

        if ($request->conformes && is_array($request->conformes)) {
            foreach ($request->conformes as $conforme) {
                if (!empty($conforme['name'])) {
                    $supplier->conformes()->create([
                        'name' => $conforme['name'],
                        'position' => $conforme['position'] ?? null,
                        'contact_no' => $conforme['contact_no'] ?? null,
                    ]);
                }
            }
        }

        if ($request->hasFile('attachments')) {
            $attachment_codes = $request->attachment_codes ?? [];
            $attachment_types = $request->attachment_types ?? [];

            foreach ($request->file('attachments') as $index => $file) {
                $path = $file->store('supplier_attachments', 'public');
                if ($path) {
                    $supplier->attachments()->create([
                        'name' => $file->getClientOriginalName(),
                        'path' => $path,
                        'type_id' => $attachment_types[$index] ?? null,
                        'code' => $attachment_codes[$index] ?? null,
                    ]);
                }
            }
        }

        return [
            'data' => new SupplierResource($supplier->load(['address', 'conformes', 'attachments', 'created_by.profile'])),
            'message' => 'Supplier created successfully!',
            'info' => "You've successfully added new Supplier.",
        ];
    }

    public function update($request)
    {
        $supplier = Supplier::findOrFail($request->id);

        $supplier->update([
            'name' => $request->name,
            'code' => $request->code,
            'is_active' => $request->is_active ?? $supplier->is_active,
        ]);

        if ($request->address) {
            $supplier->address()->updateOrCreate(
                ['supplier_id' => $supplier->id],
                ['address' => $request->address]
            );
        }

        if ($request->conformes && is_array($request->conformes)) {
            $supplier->conformes()->delete();
            foreach ($request->conformes as $conforme) {
                if (!empty($conforme['name'])) {
                    $supplier->conformes()->create([
                        'name' => $conforme['name'],
                        'position' => $conforme['position'] ?? null,
                        'contact_no' => $conforme['contact_no'] ?? null,
                    ]);
                }
            }
        }

        $existingAttachmentIds = $request->existing_attachments ?? [];
        $attachmentCodes = $request->attachment_codes ?? [];
        $attachmentTypes = $request->attachment_types ?? [];

        $supplier->attachments()->whereNotIn('id', $existingAttachmentIds)->delete();

        foreach ($existingAttachmentIds as $index => $attachmentId) {
            if (isset($attachmentCodes[$index]) || isset($attachmentTypes[$index])) {
                $supplier->attachments()->where('id', $attachmentId)->update([
                    'code' => $attachmentCodes[$index] ?? null,
                    'type_id' => $attachmentTypes[$index] ?? null,
                ]);
            }
        }

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $index => $file) {
                $path = $file->store('supplier_attachments', 'public');
                if ($path) {
                    $supplier->attachments()->create([
                        'name' => $file->getClientOriginalName(),
                        'path' => $path,
                        'type_id' => $attachmentTypes[$index] ?? null,
                        'code' => $attachmentCodes[$index] ?? null,
                    ]);
                }
            }
        }

        return [
            'data' => new SupplierResource($supplier->load(['address', 'conformes', 'attachments', 'created_by.profile'])),
            'message' => 'Supplier updated successfully!',
            'info' => "You've successfully updated the Supplier.",
        ];
    }

    public function status($request, $id)
    {
        $supplier = Supplier::findOrFail($id);
        $supplier->update([
            'is_active' => $request->is_active,
        ]);

        return [
            'data' => new SupplierResource($supplier->load(['address', 'conformes', 'attachments', 'created_by.profile'])),
            'message' => 'Supplier status updated successfully!',
            'info' => "You've successfully " . ($request->is_active ? 'activated' : 'deactivated') . " the supplier.",
        ];
    }

}
