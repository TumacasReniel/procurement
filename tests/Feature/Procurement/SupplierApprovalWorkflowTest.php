<?php

use App\Models\ListRole;
use App\Models\Supplier;
use App\Models\User;
use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

function createTestUser(string $prefix): User
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

function assignRoleToUser(User $user, string $roleName): void
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

function supplierPayload(string $name): array
{
    return [
        'name' => $name,
        'tin' => '123-456-789-000',
        'address' => 'Zamboanga City, Philippines',
        'is_active' => 1,
        'conformes' => [
            [
                'name' => 'Juan Dela Cruz',
                'position' => 'Owner',
                'contact_no' => '09123456789',
            ],
        ],
        'attachment_rows' => [
            [
                'document_type' => 'Business Permit',
                'code' => 'BP-001',
                'file' => UploadedFile::fake()->create('business-permit.pdf', 120, 'application/pdf'),
            ],
            [
                'document_type' => 'PhilGEPS Registration',
                'code' => 'PG-001',
                'file' => UploadedFile::fake()->create('philgeps-registration.pdf', 120, 'application/pdf'),
            ],
        ],
    ];
}

beforeEach(function () {
    $this->withoutMiddleware(VerifyCsrfToken::class);
});

test('procurement staff supplier submissions stay pending approval', function () {
    Storage::fake('public');

    $staff = createTestUser('staff');
    assignRoleToUser($staff, 'Procurement Staff');

    $response = $this
        ->actingAs($staff)
        ->withHeader('Accept', 'application/json')
        ->post('/faims/suppliers', supplierPayload('Pending Supplier Test'));

    $response
        ->assertCreated()
        ->assertJsonPath('data.approval_status', 'Pending Approval')
        ->assertJsonPath('data.approved_by_id', null);

    $supplier = Supplier::query()->where('name', 'Pending Supplier Test')->firstOrFail();

    expect($supplier->approval_status)->toBe('Pending Approval');
    expect($supplier->approved_by_id)->toBeNull();
    expect($supplier->approved_at)->toBeNull();
    expect($supplier->user_id)->toBe($staff->id);
});

test('procurement officers can create suppliers as approved immediately', function () {
    Storage::fake('public');

    $officer = createTestUser('officer');
    assignRoleToUser($officer, 'Procurement Officer');

    $response = $this
        ->actingAs($officer)
        ->withHeader('Accept', 'application/json')
        ->post('/faims/suppliers', supplierPayload('Approved Supplier Test'));

    $response
        ->assertCreated()
        ->assertJsonPath('data.approval_status', 'Approved')
        ->assertJsonPath('data.approved_by_id', $officer->id);

    $supplier = Supplier::query()->where('name', 'Approved Supplier Test')->firstOrFail();

    expect($supplier->approval_status)->toBe('Approved');
    expect($supplier->approved_by_id)->toBe($officer->id);
    expect($supplier->approved_at)->not->toBeNull();
});

test('procurement officers can approve supplier submissions from procurement staff', function () {
    $staff = createTestUser('staff');
    $officer = createTestUser('officer');

    assignRoleToUser($staff, 'Procurement Staff');
    assignRoleToUser($officer, 'Procurement Officer');

    $supplier = Supplier::create([
        'name' => 'Needs Officer Approval',
        'code' => 'SUP-26-04-9999',
        'tin' => '987-654-321-000',
        'approval_status' => 'Pending Approval',
        'approved_by_id' => null,
        'approved_at' => null,
        'is_active' => 1,
        'user_id' => $staff->id,
    ]);

    $response = $this
        ->actingAs($officer)
        ->withHeader('Accept', 'application/json')
        ->patch("/faims/suppliers/{$supplier->id}/approve");

    $response
        ->assertOk()
        ->assertJsonPath('data.approval_status', 'Approved')
        ->assertJsonPath('data.approved_by_id', $officer->id);

    $supplier->refresh();

    expect($supplier->approval_status)->toBe('Approved');
    expect($supplier->approved_by_id)->toBe($officer->id);
    expect($supplier->approved_at)->not->toBeNull();
});
