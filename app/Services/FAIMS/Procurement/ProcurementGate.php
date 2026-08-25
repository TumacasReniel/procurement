<?php

namespace App\Services\FAIMS\Procurement;

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Separation-of-duties gate for procurement actions (RA 9184).
 *
 * Before this existed the approval actions — PR review/approve, BAC resolution
 * approval, NOA serving, PO transitions, PR deletion — only checked that a user
 * was logged in, so any authenticated account could approve its own request.
 * Every state-changing procurement action routes through here.
 */
class ProcurementGate
{
    public const REVIEW_PR = 'review_pr';

    public const APPROVE_PR = 'approve_pr';

    public const DELETE_PR = 'delete_pr';

    public const MANAGE_RFQ = 'manage_rfq';

    public const EVALUATE_BIDS = 'evaluate_bids';

    public const CREATE_BAC_RESOLUTION = 'create_bac_resolution';

    public const APPROVE_BAC_RESOLUTION = 'approve_bac_resolution';

    public const MANAGE_NOA = 'manage_noa';

    public const MANAGE_PO = 'manage_po';

    /**
     * Roles permitted per action. Administrator is implicitly allowed everywhere.
     */
    protected const ROLE_MAP = [
        self::REVIEW_PR => ['Budget Officer', 'Procurement Officer', 'Procurement Staff'],
        self::APPROVE_PR => ['Procurement Officer', 'Regional Director'],
        self::DELETE_PR => ['Procurement Officer'],
        self::MANAGE_RFQ => ['Procurement Officer', 'Procurement Staff', 'Supply Officer'],
        self::EVALUATE_BIDS => ['BAC User', 'BAC Chairperson', 'BAC Vice Chairperson', 'BAC Member', 'Procurement Officer'],
        self::CREATE_BAC_RESOLUTION => ['BAC User', 'BAC Chairperson', 'BAC Vice Chairperson', 'BAC Member', 'Procurement Officer'],
        self::APPROVE_BAC_RESOLUTION => ['BAC Chairperson', 'BAC Vice Chairperson'],
        self::MANAGE_NOA => ['BAC User', 'BAC Chairperson', 'BAC Vice Chairperson', 'Procurement Officer'],
        self::MANAGE_PO => ['Procurement Officer', 'Procurement Staff', 'Supply Officer'],
    ];

    protected const MESSAGES = [
        self::REVIEW_PR => 'Only Budget Officers and Procurement staff can review a purchase request.',
        self::APPROVE_PR => 'Only the Procurement Officer or Regional Director can approve a purchase request.',
        self::DELETE_PR => 'Only the Procurement Officer can delete a purchase request.',
        self::MANAGE_RFQ => 'Only Procurement/Supply staff can manage Requests for Quotation.',
        self::EVALUATE_BIDS => 'Only BAC members can evaluate bids and set awards.',
        self::CREATE_BAC_RESOLUTION => 'Only BAC members can create a BAC resolution.',
        self::APPROVE_BAC_RESOLUTION => 'Only the BAC Chairperson or Vice Chairperson can approve a BAC resolution.',
        self::MANAGE_NOA => 'Only BAC members or the Procurement Officer can act on a Notice of Award.',
        self::MANAGE_PO => 'Only Procurement/Supply staff can act on a Purchase Order.',
    ];

    public function allows(string $action): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        if ($user->hasRole('Administrator')) {
            return true;
        }

        foreach (self::ROLE_MAP[$action] ?? [] as $role) {
            if ($user->hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @throws ValidationException when the current user may not perform the action.
     */
    public function authorize(string $action, string $field = 'authorization'): void
    {
        if ($this->allows($action)) {
            return;
        }

        throw ValidationException::withMessages([
            $field => self::MESSAGES[$action] ?? 'You are not allowed to perform this procurement action.',
        ]);
    }
}
