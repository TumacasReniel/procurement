<?php

namespace App\Http\Controllers\FAIMS\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\FinanceRequest as FinanceFormRequest;
use App\Models\FinanceRequest;
use App\Models\FinanceRequestAttachment;
use App\Models\ListDropdown;
use App\Models\ListProject;
use App\Models\Supplier;
use App\Models\User;
use App\Services\DropdownClass;
use App\Services\FAIMS\Finance\FinanceClass;
use App\Services\FAIMS\Finance\ViewClass;
use App\Traits\HandlesTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;

class FinanceController extends Controller
{
    use HandlesTransaction;

    public $document, $view, $dropdown, $finance;

    public function __construct(
        ViewClass $view,
        DropdownClass $dropdown,
        FinanceClass $finance
    ) {
        $this->view = $view;
        $this->dropdown = $dropdown;
        $this->finance = $finance;
    }


    public function index(Request $request)
    {
        switch ($request->option) {
            case 'lists':
                return $this->view->lists($request);
                break;

            default:
                return inertia('Modules/FAIMS/Finance/Index', [
                    'dropdowns' => [
                        'request_types' => $this->dropdown->request_types(),
                        'divisions' => $this->dropdown->dropdowns('Division'),
                        'fund_clusters' => $this->dropdown->dropdowns('Fund Cluster'),
                        'project_types' => $this->dropdown->dropdowns('Project Type'),
                        'projects' => $this->dropdown->projects(),
                        'creditors' => $this->dropdown->creditors(),
                        'statuses' => $this->dropdown->statuses('Finance Request'),
                    ],
                ]);
        }
    }

    public function show($id)
    {
        $request = FinanceRequest::with([
            'status',
            'division',
            'request_type',
            'requestType.documents.document',
            'created_by.profile',
            'creditor',
            'assignments.user.profile',
            'comments.user.profile',
            'attachments.document',
            'attachments.uploadedBy.profile',
            'attachments.verifiedBy.profile',
            'attachments.comments.user.profile',
        ])->findOrFail($id);

        $creditorLabel = $request->payee;
        if (!$creditorLabel && $request->creditor) {
            if ($request->creditor instanceof User) {
                $creditorLabel = $request->creditor->profile?->full_name ?? $request->creditor->name;
            } elseif ($request->creditor instanceof Supplier) {
                $creditorLabel = $request->creditor->name;
            } else {
                $creditorLabel = $request->creditor->name ?? $request->creditor->label ?? null;
            }
        }
        if (!$creditorLabel) {
            $creditorLabel = $request->requested_by;
        }

        $fundSource = $request->fund_cluster_id
            ? ListDropdown::find($request->fund_cluster_id)
            : null;
        $projectType = $request->project_type_id
            ? ListDropdown::find($request->project_type_id)
            : null;
        $project = $request->project_id
            ? ListProject::find($request->project_id)
            : null;

        $assignees = [];
        foreach ($request->assignments as $assignment) {
            $name = $assignment->user?->profile?->full_name ?? $assignment->user?->name ?? 'User #' . $assignment->user_id;
            if (!$name) {
                continue;
            }
            $assignees[$assignment->status][] = $name;
        }

        $requiredDocuments = collect($request->requestType?->documents ?? [])
            ->map(function ($row) {
                if (!$row->document) {
                    return null;
                }

                return [
                    'id' => $row->document->id,
                    'name' => $row->document->name,
                    'description' => $row->document->description,
                ];
            })
            ->filter()
            ->values();

        $attachments = $request->attachments
            ->map(fn ($attachment) => $this->transformAttachment($request->id, $attachment))
            ->values();

        $payload = [
            'id' => $request->id,
            'code' => $request->code,
            'date' => $request->date,
            'payee' => $creditorLabel,
            'creditor' => $creditorLabel,
            'amount' => $request->amount,
            'amount_formatted' => $request->amount !== null ? number_format($request->amount, 2) : null,
            'division' => $request->division?->name,
            'obligation_type' => $request->request_type?->name,
            'fund_source' => $fundSource?->name,
            'project_type' => $projectType?->name,
            'project' => $project?->name,
            'status' => [
                'name' => $request->status?->name ?? 'N/A',
                'bg' => $request->status?->bg ?? 'bg-primary text-white',
            ],
            'created_by' => $request->created_by?->profile?->full_name ?? $request->created_by_id,
            'requested_by' => $request->requested_by,
            'particulars' => $request->particulars,
            'request_type' => $request->request_type?->name,
            'assignees' => $assignees,
            'comments' => $request->comments ?? [],
            'required_documents' => $requiredDocuments,
            'attachments' => $attachments,
        ];

        $logs = Activity::where('subject_type', FinanceRequest::class)
            ->where('subject_id', $id)
            ->with('causer.profile')
            ->orderBy('created_at', 'desc')
            ->get();

        return inertia('Modules/FAIMS/Finance/Overview/View', [
            'request' => $payload,
            'logs' => $logs,
            'roles' => \Auth::user()->roles,
        ]);
    }

    public function storeAttachment($id, Request $request)
    {
        $validated = $request->validate([
            'finance_document_id' => 'required|exists:finance_documents,id',
            'file' => 'required|file|mimes:pdf|max:10240',
        ]);

        $financeRequest = FinanceRequest::findOrFail($id);
        $file = $validated['file'];

        $result = $this->handleTransaction(function () use ($financeRequest, $file, $validated) {
            $existing = FinanceRequestAttachment::where('finance_request_id', $financeRequest->id)
                ->where('finance_document_id', (int) $validated['finance_document_id'])
                ->first();

            if ($existing && $existing->path && Storage::disk('public')->exists($existing->path)) {
                Storage::disk('public')->delete($existing->path);
            }

            $path = $file->store('finance_attachments', 'public');

            $attachment = FinanceRequestAttachment::updateOrCreate(
                [
                    'finance_request_id' => $financeRequest->id,
                    'finance_document_id' => (int) $validated['finance_document_id'],
                ],
                [
                    'name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'mime_type' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                    'uploaded_by_id' => auth()->id(),
                    'verification_status' => 'pending',
                    'verification_note' => null,
                    'verified_by_id' => null,
                    'verified_at' => null,
                ]
            );

            $attachment->load(['document', 'uploadedBy.profile', 'verifiedBy.profile', 'comments.user.profile']);

            return [
                'data' => $this->transformAttachment($financeRequest->id, $attachment),
                'message' => 'Attachment submitted successfully.',
                'info' => null,
                'status' => true,
            ];
        });

        $httpStatus = ($result['status'] ?? false) ? 200 : 422;

        return response()->json($result, $httpStatus);
    }

    public function deleteAttachment($id, $attachmentId)
    {
        $financeRequest = FinanceRequest::findOrFail($id);

        $result = $this->handleTransaction(function () use ($financeRequest, $attachmentId) {
            $attachment = FinanceRequestAttachment::where('finance_request_id', $financeRequest->id)
                ->where('id', $attachmentId)
                ->firstOrFail();

            if ($attachment->path && Storage::disk('public')->exists($attachment->path)) {
                Storage::disk('public')->delete($attachment->path);
            }

            $attachment->delete();

            return [
                'data' => $attachmentId,
                'message' => 'Attachment deleted successfully.',
                'info' => null,
                'status' => true,
            ];
        });

        $httpStatus = ($result['status'] ?? false) ? 200 : 422;

        return response()->json($result, $httpStatus);
    }

    public function verifyAttachment($id, $attachmentId, Request $request)
    {
        $validated = $request->validate([
            'status' => 'required|in:verified,incorrect',
            'note' => 'nullable|string|max:1000',
        ]);

        $financeRequest = FinanceRequest::findOrFail($id);
        $attachment = FinanceRequestAttachment::where('finance_request_id', $financeRequest->id)
            ->where('id', $attachmentId)
            ->firstOrFail();

        $attachment->update([
            'verification_status' => $validated['status'],
            'verification_note' => $validated['note'] ?? null,
            'verified_by_id' => auth()->id(),
            'verified_at' => now(),
        ]);

        return response()->json([
            'data' => [
                'id' => $attachment->id,
                'verification_status' => $attachment->verification_status,
                'verification_note' => $attachment->verification_note,
                'verified_at' => optional($attachment->verified_at)->format('M d, Y h:i A'),
                'verified_by' => auth()->user()?->profile?->full_name ?? auth()->user()?->name ?? 'User',
            ],
            'message' => 'Attachment verification status updated.',
            'status' => true,
        ]);
    }
    public function previewAttachment($id, $attachmentId)
    {
        $financeRequest = FinanceRequest::findOrFail($id);
        $attachment = FinanceRequestAttachment::where('finance_request_id', $financeRequest->id)
            ->where('id', $attachmentId)
            ->firstOrFail();

        if (!$attachment->path || !Storage::disk('public')->exists($attachment->path)) {
            abort(404, 'Attachment file not found.');
        }

        $absolutePath = Storage::disk('public')->path($attachment->path);
        $headers = [
            'Content-Type' => $attachment->mime_type ?: 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . ($attachment->name ?: 'attachment.pdf') . '"',
        ];

        return response()->file($absolutePath, $headers);
    }

    protected function transformAttachment(int $requestId, FinanceRequestAttachment $attachment): array
    {
        return [
            'id' => $attachment->id,
            'finance_document_id' => $attachment->finance_document_id,
            'document_name' => $attachment->document?->name,
            'name' => $attachment->name,
            'path' => $attachment->path,
            'url' => $attachment->path ? Storage::disk('public')->url($attachment->path) : null,
            'preview_url' => url("/faims/finance-requests/{$requestId}/attachments/{$attachment->id}/preview"),
            'mime_type' => $attachment->mime_type,
            'size' => $attachment->size,
            'uploaded_by' => $attachment->uploadedBy?->profile?->full_name
                ?? $attachment->uploadedBy?->name
                ?? null,
            'uploaded_at' => optional($attachment->created_at)->format('M d, Y h:i A'),
            'verification_status' => $attachment->verification_status ?? 'pending',
            'verification_note' => $attachment->verification_note,
            'verified_at' => optional($attachment->verified_at)->format('M d, Y h:i A'),
            'verified_by' => $attachment->verifiedBy?->profile?->full_name
                ?? $attachment->verifiedBy?->name
                ?? null,
            'comments_count' => $attachment->comments->count(),
            'comments' => $attachment->comments->map(function ($comment) {
                return [
                    'id' => $comment->id,
                    'content' => $comment->content,
                    'created_at' => optional($comment->created_at)->format('M d, Y h:i A'),
                    'created_ago' => $comment->created_ago,
                    'user' => [
                        'id' => $comment->user?->id,
                        'name' => $comment->user?->profile?->full_name ?? $comment->user?->name ?? 'User',
                    ],
                ];
            })->values(),
        ];
    }
    public function addAttachmentComment($id, $attachmentId, Request $request)
    {
        $validated = $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        $financeRequest = FinanceRequest::findOrFail($id);
        $attachment = FinanceRequestAttachment::where('finance_request_id', $financeRequest->id)
            ->where('id', $attachmentId)
            ->firstOrFail();

        $comment = $attachment->comments()->create([
            'content' => $validated['content'],
            'user_id' => auth()->id(),
        ]);

        $comment->load('user.profile');

        return response()->json([
            'data' => [
                'id' => $comment->id,
                'content' => $comment->content,
                'created_at' => optional($comment->created_at)->format('M d, Y h:i A'),
                'created_ago' => $comment->created_ago,
                'user' => [
                    'id' => $comment->user?->id,
                    'name' => $comment->user?->profile?->full_name ?? $comment->user?->name ?? 'User',
                ],
            ],
            'message' => 'Attachment comment added successfully.',
            'status' => true,
        ]);
    }
    public function addComment($id, Request $request)
    {
        $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        $result = $this->handleTransaction(function () use ($id, $request) {
            $financeRequest = FinanceRequest::findOrFail($id);

            $comment = $financeRequest->comments()->create([
                'content' => $request->content,
                'user_id' => auth()->id(),
            ]);

            return [
                'data' => $comment->load('user.profile'),
                'message' => 'Comment added successfully',
                'info' => 'Your comment has been added to the finance request.',
                'status' => true,
            ];
        });

        return back()->with([
            'data' => $result['data'],
            'status' => $result['status'],
            'message' => $result['message'],
            'info' => $result['info'],
        ]);
    }

    public function store(FinanceFormRequest $request)
    {

        $result = $this->handleTransaction(function () use ($request) {
            return $this->finance->save($request);
        });
        return back()->with([
            'data' => $result['data'],
            'message' => $result['message'],
            'info' => $result['info'],
            'status' => $result['status'],
        ]);
    }

    public function update(FinanceFormRequest $request, $id)
    {
        $result = $this->handleTransaction(function () use ($request, $id) {
            return $this->finance->update($request, $id);
        });


        return back()->with([
            'data' => $result['data'],
            'message' => $result['message'],
            'info' => $result['info'],
            'status' => $result['status'],
        ]);
    }

    public function destroy($id)
    {
        $result = $this->handleTransaction(function () use ($id) {
            $model = FinanceRequest::findOrFail($id);
            $model->delete();
            return [
                'data' => $id,
                'message' => 'Finance request deleted successfully.',
                'info' => null,
                'status' => true,
            ];
        });

        $httpStatus = ($result['status'] ?? false) ? 200 : 422;

        return response()->json($result, $httpStatus);
    }

    public function disbursementsObligations(Request $request)
    {
        if ($request->option === 'lists') {
            return response()->json([
                'data' => [],
                'meta' => null,
                'links' => null,
            ]);
        }

        return inertia('Modules/FAIMS/Finance/DisbursementsObligations/Index', [
            'roles' => \Auth::user()->roles,
        ]);
    }

    public function dashboard(Request $request)
    {
        return inertia('Modules/FAIMS/Finance/Dashboard', [
            'dropdowns' => [
                'roles' => \Auth::user()->roles,
                'designation' => \Auth::user()->designation,
            ],
        ]);
    }
}






