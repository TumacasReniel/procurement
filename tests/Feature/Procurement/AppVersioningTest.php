<?php

use App\Models\ProcurementApp;
use App\Services\DropdownClass;
use App\Services\FAIMS\Procurement\ProcurementPPMPClass;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;

/*
| APP versioning lives in ProcurementPPMPClass. The rules under test:
|
|   - procurement_apps is keyed by (year, version) unique + code unique
|   - plan_phase splits a year into an Indicative APP and a Final APP (RA 9184)
|   - new APPs get their code from generateAppCode()      -> APP-{year}-{VV}
|   - SPP-driven revisions get theirs from generateAppVersionCode() -> {base}-V{VV}
|   - the "effective" APP for a new PR is picked by ProcurementClass::currentAppIdForRequest()
|
| The version counter is shared across both phases of a year, which is what the
| namespace/ordering cases below pin down.
*/

function appVersioningService(): ProcurementPPMPClass
{
    return new ProcurementPPMPClass(app(DropdownClass::class));
}

function appVersioningCall(string $method, array $args = [])
{
    $service = appVersioningService();
    $reflection = new ReflectionMethod($service, $method);
    $reflection->setAccessible(true);

    return $reflection->invokeArgs($service, $args);
}

function makeApp(int $year, int $version, string $phase, ?string $code = null): ProcurementApp
{
    return ProcurementApp::query()->create([
        'code' => $code ?? 'APP-'.$year.'-'.str_pad((string) $version, 2, '0', STR_PAD_LEFT),
        'year' => $year,
        'version' => $version,
        'plan_phase' => $phase,
        'title' => 'Annual Procurement Plan',
    ]);
}

beforeEach(function () {
    ProcurementApp::query()->forceDelete();
});

/*
| 1 — schema contract
*/
it('keeps the APP register keyed by year, version and plan phase', function () {
    expect(Schema::hasColumn('procurement_apps', 'version'))->toBeTrue()
        ->and(Schema::hasColumn('procurement_apps', 'plan_phase'))->toBeTrue();

    $indexes = collect(Schema::getIndexes('procurement_apps'))->pluck('name');

    expect($indexes)->toContain('procurement_apps_year_version_unique')
        ->and($indexes)->toContain('procurement_apps_code_unique');
});

it('rejects a duplicate version within the same year at the database level', function () {
    makeApp(2026, 1, 'indicative');

    expect(fn () => makeApp(2026, 1, 'final', 'APP-2026-01-DUP'))
        ->toThrow(QueryException::class);
});

it('allows the same version number in different years', function () {
    makeApp(2026, 1, 'indicative');
    makeApp(2027, 1, 'indicative', 'APP-2027-01');

    expect(ProcurementApp::query()->where('version', 1)->count())->toBe(2);
});

/*
| 2 — code generation
*/
it('generates a zero-padded APP code per year and version', function () {
    expect(appVersioningCall('generateAppCode', [2026, 1]))->toBe('APP-2026-01')
        ->and(appVersioningCall('generateAppCode', [2026, 12]))->toBe('APP-2026-12');
});

it('starts a year at version 1', function () {
    expect(appVersioningCall('nextAppVersionAndCode', [2026]))->toBe([1, 'APP-2026-01']);
});

it('advances to the next free version when a year already has APPs', function () {
    makeApp(2026, 1, 'indicative');

    expect(appVersioningCall('nextAppVersionAndCode', [2026]))->toBe([2, 'APP-2026-02']);
});

it('skips a version whose generated code is already taken by another row', function () {
    // A 2025 APP squatting on the 2026 code must not produce a duplicate-code insert.
    makeApp(2025, 7, 'final', 'APP-2026-01');

    expect(appVersioningCall('nextAppVersionAndCode', [2026]))->toBe([2, 'APP-2026-02']);
});

it('does not let another year advance this year version counter', function () {
    makeApp(2025, 9, 'final', 'APP-2025-09');

    expect(appVersioningCall('nextAppVersionAndCode', [2026]))->toBe([1, 'APP-2026-01']);
});

/*
| 3 — SPP revision codes
*/
it('suffixes the previous APP code when an SPP creates a revision', function () {
    $previous = makeApp(2026, 2, 'final');

    expect(appVersioningCall('generateAppVersionCode', [$previous, 3]))->toBe('APP-2026-02-V03');
});

it('replaces the suffix instead of stacking it on repeat revisions', function () {
    $previous = makeApp(2026, 3, 'final', 'APP-2026-02-V03');

    expect(appVersioningCall('generateAppVersionCode', [$previous, 4]))->toBe('APP-2026-02-V04');
});

/*
| 4 — the version counter is shared across phases
|
| These pin the CURRENT behaviour so a deliberate change is visible in the diff.
| Both are user-facing oddities, see the notes in the accompanying report.
*/
it('numbers the final APP as version 2 because the indicative APP consumed version 1', function () {
    makeApp(2026, 1, 'indicative');

    [$version, $code] = appVersioningCall('nextAppVersionAndCode', [2026]);

    // The Final APP is a distinct document, not the 2nd revision of the Indicative,
    // yet it is labelled V2 and its SPP revisions then start at V3.
    expect($version)->toBe(2)
        ->and($code)->toBe('APP-2026-02');
});

it('continues the shared counter when an SPP revises the final APP', function () {
    makeApp(2026, 1, 'indicative');
    $final = makeApp(2026, 2, 'final');

    $next = ((int) ProcurementApp::query()->where('year', $final->year)->max('version')) + 1;

    expect($next)->toBe(3)
        ->and(appVersioningCall('generateAppVersionCode', [$final, $next]))->toBe('APP-2026-02-V03');
});

/*
| 5 — which APP a new PR attaches to
|
| ProcurementClass::currentAppIdForRequest() orders by version desc, with no
| plan_phase filter. Phase creation order therefore decides the winner.
*/
it('treats the highest-versioned APP of the year as the effective plan', function () {
    $year = (int) now()->year;

    makeApp($year, 1, 'indicative', 'APP-'.$year.'-01');
    $final = makeApp($year, 2, 'final', 'APP-'.$year.'-02');

    $effective = ProcurementApp::query()
        ->where('year', $year)
        ->orderByDesc('version')
        ->orderByDesc('id')
        ->value('id');

    expect($effective)->toBe($final->id);
});

it('picks the indicative APP when it was registered after the final one', function () {
    $year = (int) now()->year;

    // ensureAppDoesNotExist() permits one APP per phase per year in either order.
    makeApp($year, 1, 'final', 'APP-'.$year.'-01');
    $indicative = makeApp($year, 2, 'indicative', 'APP-'.$year.'-02');

    $effective = ProcurementApp::query()
        ->where('year', $year)
        ->orderByDesc('version')
        ->orderByDesc('id')
        ->value('id');

    // A pre-budget Indicative APP outranking the post-GAA Final APP is wrong:
    // new PRs would be attached to the wrong plan.
    expect($effective)->toBe($indicative->id)
        ->and(ProcurementApp::find($effective)->plan_phase)->toBe('indicative');
});

/*
| 6 — soft-deleted APPs must not have their version or code reissued
*/
it('does not reissue the version of a soft-deleted APP', function () {
    $app = makeApp(2026, 1, 'indicative');
    $app->delete();

    // The unique index still covers the trashed row, so reusing version 1 would
    // fail on insert. nextAppVersionAndCode() must skip past it.
    expect(appVersioningCall('nextAppVersionAndCode', [2026]))->toBe([2, 'APP-2026-02']);
});

it('can actually insert the version it hands out after an APP was deleted', function () {
    makeApp(2026, 1, 'indicative')->delete();

    [$version, $code] = appVersioningCall('nextAppVersionAndCode', [2026]);

    // Guards the failure mode the case above describes: a reissued version is not a
    // cosmetic mismatch, it is a duplicate-key error on the very next create.
    expect(fn () => makeApp(2026, $version, 'final', $code))->not->toThrow(QueryException::class);
});

it('does not reissue a version already used by a soft-deleted SPP revision', function () {
    makeApp(2026, 1, 'indicative');
    $final = makeApp(2026, 2, 'final');
    makeApp(2026, 3, 'final', 'APP-2026-02-V03')->delete();

    $next = ((int) ProcurementApp::query()
        ->withTrashed()
        ->where('year', $final->year)
        ->max('version')) + 1;

    expect($next)->toBe(4);
});
