<?php

namespace App\Services\FAIMS\Procurement;

use App\Models\Supplier;
use App\Models\User;
use App\Notifications\PendingSupplierApprovalNotification;
use App\Http\Resources\FAIMS\Procurement\SupplierResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class SupplierClass
{
    public function lists($request)
    {
        $data = SupplierResource::collection(
            Supplier::query()
            ->with(['address', 'conformes', 'attachments', 'created_by.profile', 'approved_by.profile'])
            ->withCount(['conformes', 'attachments'])
            ->when($request->keyword, function ($query, $keyword) {
                $normalizedKeyword = preg_replace('/\D+/', '', (string) $keyword);

                $query->where(function ($searchQuery) use ($keyword, $normalizedKeyword) {
                    $searchQuery->where('name', 'LIKE', "%{$keyword}%")
                        ->orWhere('code', 'LIKE', "%{$keyword}%")
                        ->orWhere('tin', 'LIKE', "%{$keyword}%")
                        ->orWhere('approval_status', 'LIKE', "%{$keyword}%")
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
                        })
                        ->orWhereHas('approved_by.profile', function ($q) use ($keyword) {
                            $q->where('firstname', 'LIKE', "%{$keyword}%")
                                ->orWhere('middlename', 'LIKE', "%{$keyword}%")
                                ->orWhere('lastname', 'LIKE', "%{$keyword}%");
                        });

                    if ($normalizedKeyword !== '') {
                        $searchQuery->orWhereRaw("REPLACE(tin, '-', '') LIKE ?", ["%{$normalizedKeyword}%"]);
                    }
                });
            })
            ->when($request->status !== null && $request->status !== '', function ($query) use ($request) {
                if ($request->status === 'pending_approval') {
                    $query->where('approval_status', 'Pending Approval');
                    return;
                }

                if ($request->status === 'active' || (string) $request->status === '1') {
                    $query->where('approval_status', 'Approved')->where('is_active', 1);
                    return;
                }

                if ($request->status === 'inactive' || (string) $request->status === '0') {
                    $query->where('approval_status', 'Approved')->where('is_active', 0);
                }
            })
            ->orderBy('created_at', 'DESC')
            ->paginate($request->count ?: 10)
        );

        return $data;
    }

    public function save($request)
    {
        $code = Supplier::generateCode();
        $user = Auth::user();
        $is_directly_approvable = $this->canApproveSupplier($user);

        $supplier = Supplier::create([
            'name' => $request->name,
            'code' => $code,
            'tin' => $request->tin,
            'approval_status' => $is_directly_approvable ? 'Approved' : 'Pending Approval',
            'approved_by_id' => $is_directly_approvable ? $user?->id : null,
            'approved_at' => $is_directly_approvable ? now() : null,
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

        $this->syncAttachments($supplier, $request);

        if (!$is_directly_approvable) {
            $this->notifyPendingSupplierApprovalRecipients($supplier, $user);
        }

        return [
            'data' => new SupplierResource($supplier->load(['address', 'conformes', 'attachments', 'created_by.profile', 'approved_by.profile'])),
            'message' => $is_directly_approvable
                ? 'Supplier created successfully!'
                : 'Supplier submitted for approval successfully!',
            'info' => $is_directly_approvable
                ? "You've successfully added new Supplier."
                : 'The supplier has been submitted and is now waiting for Procurement Officer approval.',
        ];
    }

    public function update($request)
    {
        $supplier = Supplier::findOrFail($request->id);

        $supplier->update([
            'name' => $request->name,
            'code' => $request->code,
            'tin' => $request->tin,
            'is_active' => $request->is_active ?? $supplier->is_active,
        ]);

        if (filled($request->address)) {
            $supplier->address()->updateOrCreate(
                ['supplier_id' => $supplier->id],
                ['address' => $request->address]
            );
        } else {
            $supplier->address()->delete();
        }

        $supplier->conformes()->delete();

        if (is_array($request->conformes)) {
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

        $this->syncAttachments($supplier, $request, true);

        return [
            'data' => new SupplierResource($supplier->load(['address', 'conformes', 'attachments', 'created_by.profile', 'approved_by.profile'])),
            'message' => 'Supplier updated successfully!',
            'info' => "You've successfully updated the Supplier.",
        ];
    }

    public function approve($id)
    {
        $user = Auth::user();

        if (!$this->canApproveSupplier($user)) {
            abort(403, 'Only Procurement Officer or Administrator can approve suppliers.');
        }

        $supplier = Supplier::findOrFail($id);

        $supplier->update([
            'approval_status' => 'Approved',
            'approved_by_id' => $user?->id,
            'approved_at' => now(),
        ]);

        return [
            'data' => new SupplierResource($supplier->load(['address', 'conformes', 'attachments', 'created_by.profile', 'approved_by.profile'])),
            'message' => 'Supplier approved successfully!',
            'info' => 'The supplier is now approved and available for use when active.',
        ];
    }

    public function status($request, $id)
    {
        $supplier = Supplier::findOrFail($id);

        if (($supplier->approval_status ?: 'Approved') !== 'Approved') {
            throw new \Exception('Only approved suppliers can be activated or deactivated.');
        }

        $supplier->update([
            'is_active' => $request->is_active,
        ]);

        return [
            'data' => new SupplierResource($supplier->load(['address', 'conformes', 'attachments', 'created_by.profile', 'approved_by.profile'])),
            'message' => 'Supplier status updated successfully!',
            'info' => "You've successfully " . ($request->is_active ? 'activated' : 'deactivated') . " the supplier.",
        ];
    }

    protected function canApproveSupplier(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return $user->hasRole('Procurement Officer') || $user->hasRole('Administrator');
    }

    protected function notifyPendingSupplierApprovalRecipients(Supplier $supplier, ?User $actor): void
    {
        if (!$actor || !Schema::hasTable('notifications')) {
            return;
        }

        $recipients = User::query()
            ->with('profile')
            ->where('is_active', 1)
            ->where('id', '!=', $actor->id)
            ->whereHas('roles', function ($query) {
                $query->where('list_roles.name', 'Procurement Officer')
                    ->where('user_roles.is_active', 1);
            })
            ->get()
            ->unique('id');

        foreach ($recipients as $recipient) {
            $recipient->notify(new PendingSupplierApprovalNotification($supplier, $actor));
        }
    }

    protected function syncAttachments(Supplier $supplier, $request, bool $isUpdate = false): void
    {
        $rows = $request->input('attachment_rows', []);
        $retainedAttachmentIds = [];

        foreach ($rows as $index => $row) {
            $documentType = trim((string) data_get($row, 'document_type'));
            $code = filled(data_get($row, 'code')) ? trim((string) data_get($row, 'code')) : null;
            $attachmentId = data_get($row, 'id');
            $uploadedFile = $request->file("attachment_rows.$index.file");

            if (!$attachmentId && !$uploadedFile && $documentType === '' && blank($code)) {
                continue;
            }

            if ($attachmentId) {
                $attachment = $supplier->attachments()->find($attachmentId);

                if (!$attachment) {
                    continue;
                }

                $payload = [
                    'code' => $code,
                    'document_type' => $documentType ?: null,
                ];

                if ($uploadedFile) {
                    if ($attachment->path && Storage::disk('public')->exists($attachment->path)) {
                        Storage::disk('public')->delete($attachment->path);
                    }

                    $payload['name'] = $uploadedFile->getClientOriginalName();
                    $payload['path'] = $uploadedFile->store('supplier_attachments', 'public');
                }

                $attachment->update($payload);
                $retainedAttachmentIds[] = $attachment->id;

                continue;
            }

            if (!$uploadedFile) {
                continue;
            }

            $attachment = $supplier->attachments()->create([
                'name' => $uploadedFile->getClientOriginalName(),
                'path' => $uploadedFile->store('supplier_attachments', 'public'),
                'type_id' => null,
                'document_type' => $documentType ?: null,
                'code' => $code,
            ]);

            $retainedAttachmentIds[] = $attachment->id;
        }

        if (!$isUpdate) {
            return;
        }

        $attachmentsToDelete = $supplier->attachments()
            ->when(!empty($retainedAttachmentIds), function ($query) use ($retainedAttachmentIds) {
                $query->whereNotIn('id', $retainedAttachmentIds);
            }, function ($query) {
                $query->whereNotNull('id');
            })
            ->get();

        foreach ($attachmentsToDelete as $attachment) {
            if ($attachment->path && Storage::disk('public')->exists($attachment->path)) {
                Storage::disk('public')->delete($attachment->path);
            }

            $attachment->delete();
        }
    }

}
