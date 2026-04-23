<?php

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\ListDropdown;
use App\Models\ListRole;
use App\Models\ProcurementCode;
use App\Models\ProcurementCodeBudgetLog;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

function createProcurementBudgetTestUser(string $prefix): User
{
    $suffix = strtolower(Str::random(6));

    return User::create([
        'username' => substr(strtolower($prefix), 0, 8) . $suffix,
        'email' => "{$prefix}.{$suffix}@example.com",
        'password' => 'password',
        'is_active' => 1,
        'email_verified_at' => now(),
    ]);
}

function assignProcurementBudgetRole(User $user, string $roleName): void
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
}

function createProcurementBudgetDropdown(string $name, string $classification): ListDropdown
{
    return ListDropdown::create([
        'name' => $name,
        'classification' => $classification,
        'type' => 'test',
        'color' => 'n/a',
        'others' => 'n/a',
        'is_active' => 1,
    ]);
}

function createProcurementBudgetCode(float $allocatedBudget = 10000): ProcurementCode
{
    $appType = createProcurementBudgetDropdown('Regular APP', 'APP Type');
    $modeOfProcurement = createProcurementBudgetDropdown('Shopping', 'Mode of Procurement');

    return ProcurementCode::create([
        'title' => 'Test PAP Code',
        'code' => 'PAP-' . Str::upper(Str::random(6)),
        'allocated_budget' => $allocatedBudget,
        'remaining_budget' => $allocatedBudget,
        'year' => 2026,
        'app_type_id' => $appType->id,
        'mode_of_procurement_id' => $modeOfProcurement->id,
    ]);
}

beforeEach(function () {
    $this->withoutMiddleware(VerifyCsrfToken::class);
});

test('procurement officers can request additional pap budget and budget officers can approve it', function () {
    Storage::fake('public');

    $procurementOfficer = createProcurementBudgetTestUser('officer');
    $budgetOfficer = createProcurementBudgetTestUser('budget');
    $papCode = createProcurementBudgetCode(10000);
    $attachment = UploadedFile::fake()->create('basis.pdf', 128, 'application/pdf');

    assignProcurementBudgetRole($procurementOfficer, 'Procurement Officer');
    assignProcurementBudgetRole($budgetOfficer, 'Budget Officer');

    $requestResponse = $this
        ->from('/faims/procurement-codes')
        ->actingAs($procurementOfficer)
        ->post("/faims/procurement-codes/{$papCode->id}/budget-increase-requests", [
            'amount' => 1500,
            'description' => 'Additional allocation needed after revised costing.',
            'attachment' => $attachment,
        ]);

    $requestResponse
        ->assertRedirect('/faims/procurement-codes')
        ->assertSessionHas('status', true)
        ->assertSessionHas('data.data.pending_budget_increase_requests_count', 1)
        ->assertSessionHas('data.logs.0.status', 'pending')
        ->assertSessionHas('data.logs.0.type', 'budget_increase')
        ->assertSessionHas('data.logs.0.requested_by.id', $procurementOfficer->id)
        ->assertSessionHas('data.logs.0.attachment_name', 'basis.pdf');

    $papCode->refresh();

    expect((float) $papCode->allocated_budget)->toBe(10000.0);
    expect((float) $papCode->remaining_budget)->toBe(10000.0);

    $requestPayload = session('data');

    $approvalResponse = $this
        ->from('/faims/procurement-codes')
        ->actingAs($budgetOfficer)
        ->patch("/faims/procurement-codes/{$papCode->id}/budget-increase-requests/" . data_get($requestPayload, 'logs.0.id') . '/approve');

    $approvalResponse
        ->assertRedirect('/faims/procurement-codes')
        ->assertSessionHas('status', true)
        ->assertSessionHas('data.data.allocated_budget', 11500)
        ->assertSessionHas('data.data.remaining_budget', 11500)
        ->assertSessionHas('data.data.pending_budget_increase_requests_count', 0)
        ->assertSessionHas('data.logs.0.status', 'approved')
        ->assertSessionHas('data.logs.0.reviewed_by.id', $budgetOfficer->id);

    $papCode->refresh();

    expect((float) $papCode->allocated_budget)->toBe(11500.0);
    expect((float) $papCode->remaining_budget)->toBe(11500.0);

    $budgetLog = ProcurementCodeBudgetLog::query()->where('procurement_code_id', $papCode->id)->firstOrFail();

    expect($budgetLog->status)->toBe('approved');
    expect($budgetLog->requested_by_id)->toBe($procurementOfficer->id);
    expect($budgetLog->reviewed_by_id)->toBe($budgetOfficer->id);
    expect($budgetLog->attachment_name)->toBe('basis.pdf');

    Storage::disk('public')->assertExists($budgetLog->attachment_path);
});

test('procurement staff can view pap codes and submit budget increase requests', function () {
    Storage::fake('public');

    $procurementStaff = createProcurementBudgetTestUser('staff');
    $papCode = createProcurementBudgetCode(8000);
    $attachment = UploadedFile::fake()->image('basis.jpg');

    assignProcurementBudgetRole($procurementStaff, 'Procurement Staff');

    $listResponse = $this
        ->actingAs($procurementStaff)
        ->withHeader('Accept', 'application/json')
        ->get('/faims/procurement-codes?option=lists');

    $listResponse
        ->assertOk()
        ->assertJsonPath('data.0.id', $papCode->id);

    $requestResponse = $this
        ->from('/faims/procurement-codes')
        ->actingAs($procurementStaff)
        ->post("/faims/procurement-codes/{$papCode->id}/budget-increase-requests", [
            'amount' => 500,
            'description' => 'Additional funds requested by procurement staff.',
            'attachment' => $attachment,
        ]);

    $requestResponse
        ->assertRedirect('/faims/procurement-codes')
        ->assertSessionHas('status', true)
        ->assertSessionHas('data.logs.0.status', 'pending')
        ->assertSessionHas('data.logs.0.requested_by.id', $procurementStaff->id)
        ->assertSessionHas('data.logs.0.attachment_name', 'basis.jpg');
});

test('budget increase requests require a supporting document', function () {
    $procurementOfficer = createProcurementBudgetTestUser('officer');
    $papCode = createProcurementBudgetCode(12000);

    assignProcurementBudgetRole($procurementOfficer, 'Procurement Officer');

    $response = $this
        ->from('/faims/procurement-codes')
        ->actingAs($procurementOfficer)
        ->post("/faims/procurement-codes/{$papCode->id}/budget-increase-requests", [
            'amount' => 750,
            'description' => 'Supporting document will be added later.',
        ]);

    $response
        ->assertRedirect('/faims/procurement-codes')
        ->assertSessionHasErrors(['attachment']);
});

test('pap code list marks records as non editable once a deduction already exists', function () {
    $procurementOfficer = createProcurementBudgetTestUser('officer');
    $papCode = createProcurementBudgetCode(5000);

    assignProcurementBudgetRole($procurementOfficer, 'Procurement Officer');

    ProcurementCodeBudgetLog::create([
        'procurement_code_id' => $papCode->id,
        'procurement_id' => null,
        'processed_by_id' => $procurementOfficer->id,
        'requested_by_id' => null,
        'reviewed_by_id' => null,
        'type' => 'approval_deduction',
        'status' => 'approved',
        'amount' => 750,
        'balance_before' => 5000,
        'balance_after' => 4250,
        'description' => 'Existing budget deduction history.',
        'reviewed_at' => now(),
    ]);

    $papCode->update([
        'remaining_budget' => 4250,
    ]);

    $response = $this
        ->actingAs($procurementOfficer)
        ->withHeader('Accept', 'application/json')
        ->get('/faims/procurement-codes?option=lists');

    $response
        ->assertOk()
        ->assertJsonPath('data.0.id', $papCode->id)
        ->assertJsonPath('data.0.can_edit', false)
        ->assertJsonPath('data.0.has_deductions', true)
        ->assertJsonPath('data.0.deduction_logs_count', 1);
});
