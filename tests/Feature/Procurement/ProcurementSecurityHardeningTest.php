<?php

use App\Models\ListRole;
use App\Models\User;
use App\Services\FAIMS\Procurement\ProcurementGate;
use App\Support\RichText;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

function hardeningUser(string $prefix): User
{
    $suffix = strtolower(Str::random(6));

    return User::create([
        'username' => substr(strtolower($prefix), 0, 8).$suffix,
        'email' => "{$prefix}.{$suffix}@example.com",
        'password' => 'password',
        'is_active' => 1,
        'email_verified_at' => now(),
    ]);
}

function hardeningAssignRole(User $user, string $roleName): void
{
    $role = ListRole::firstOrCreate(
        ['name' => $roleName],
        [
            'type' => 'Procurement',
            'definition' => "Test role for {$roleName}",
            'is_active' => 1,
        ]
    );

    DB::table('user_roles')->updateOrInsert(
        [
            'user_id' => $user->id,
            'role_id' => $role->id,
        ],
        [
            'is_active' => 1,
            'added_by' => $user->id,
            'removed_by' => null,
            'removed_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]
    );

    $user->load('roles');
}

/*
| A1 — the /faims group must not be reachable without authentication.
*/
it('rejects unauthenticated access to procurement endpoints', function (string $uri) {
    $this->get($uri)->assertRedirect('/login');
})->with([
    '/faims/procurements',
    '/faims/procurement-dashboard',
    '/faims/suppliers',
    '/faims/quotations',
    '/faims/purchase-orders',
    '/faims/bac-resolutions',
    '/faims/procurement-ppmp',
]);

/*
| A3 — separation of duties: role gates on the approval actions.
*/
it('denies procurement approval actions to a plain employee', function () {
    $user = hardeningUser('employee');
    hardeningAssignRole($user, 'Employee');
    $this->actingAs($user);

    $gate = app(ProcurementGate::class);

    expect($gate->allows(ProcurementGate::APPROVE_PR))->toBeFalse()
        ->and($gate->allows(ProcurementGate::APPROVE_BAC_RESOLUTION))->toBeFalse()
        ->and($gate->allows(ProcurementGate::EVALUATE_BIDS))->toBeFalse()
        ->and($gate->allows(ProcurementGate::MANAGE_PO))->toBeFalse()
        ->and($gate->allows(ProcurementGate::DELETE_PR))->toBeFalse();
});

it('does not let a procurement officer approve a BAC resolution', function () {
    $user = hardeningUser('procoff');
    hardeningAssignRole($user, 'Procurement Officer');
    $this->actingAs($user);

    $gate = app(ProcurementGate::class);

    // BAC approval is reserved for the BAC chair/vice-chair, even for the PO.
    expect($gate->allows(ProcurementGate::APPROVE_BAC_RESOLUTION))->toBeFalse()
        ->and($gate->allows(ProcurementGate::APPROVE_PR))->toBeTrue();
});

it('allows the BAC chairperson to approve a BAC resolution', function () {
    $user = hardeningUser('bacchair');
    hardeningAssignRole($user, 'BAC Chairperson');
    $this->actingAs($user);

    expect(app(ProcurementGate::class)->allows(ProcurementGate::APPROVE_BAC_RESOLUTION))->toBeTrue();
});

it('grants an administrator every procurement action', function () {
    $user = hardeningUser('admin');
    hardeningAssignRole($user, 'Administrator');
    $this->actingAs($user);

    $gate = app(ProcurementGate::class);

    expect($gate->allows(ProcurementGate::APPROVE_PR))->toBeTrue()
        ->and($gate->allows(ProcurementGate::APPROVE_BAC_RESOLUTION))->toBeTrue()
        ->and($gate->allows(ProcurementGate::MANAGE_PO))->toBeTrue();
});

it('denies every action to a guest', function () {
    expect(app(ProcurementGate::class)->allows(ProcurementGate::APPROVE_PR))->toBeFalse();
});

/*
| A5 — stored XSS in the print templates.
*/
it('strips script and event handlers from rich text but keeps formatting', function () {
    expect(RichText::sanitize('<script>alert(1)</script>Hello'))
        ->not->toContain('<script>')
        ->toContain('Hello');

    expect(RichText::sanitize('<img src=x onerror=alert(1)>'))->not->toContain('onerror');
    expect(RichText::sanitize('<p onclick="alert(1)">text</p>'))->not->toContain('onclick');
    expect(RichText::sanitize('<a href="javascript:alert(1)">x</a>'))->not->toContain('javascript:');

    // Legitimate formatting used by the WYSIWYG must survive.
    expect(RichText::sanitize('<p>Supply of <b>10 units</b></p>'))
        ->toContain('<b>10 units</b>');

    expect(RichText::sanitize(null))->toBe('');
});

/*
| A4 / soft deletes — schema integrity.
*/
it('enforces a unique index on the purchase request number', function () {
    $indexes = collect(DB::select('SHOW INDEX FROM procurements'))
        ->where('Key_name', 'procurements_code_unique');

    expect($indexes)->not->toBeEmpty()
        ->and($indexes->first()->Non_unique)->toEqual(0);
});

it('keeps procurement documents soft-deletable for the audit trail', function (string $table) {
    expect(Schema::hasColumn($table, 'deleted_at'))->toBeTrue();
})->with([
    'procurements',
    'procurement_items',
    'procurement_quotations',
    'procurement_quotation_items',
    'procurement_bacs',
    'procurement_bac_noas',
    'procurement_noa_pos',
    'procurement_ppmps',
    'procurement_ppmp_items',
    'procurement_apps',
]);
