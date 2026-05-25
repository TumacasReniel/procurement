<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require __DIR__.'/vendor/autoload.php';

$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$execute = in_array('--execute', $argv, true);

$tableExists = fn (string $table): bool => Schema::hasTable($table);
$columnExists = fn (string $table, string $column): bool => $tableExists($table) && Schema::hasColumn($table, $column);
$count = fn (string $table): int => $tableExists($table) ? DB::table($table)->count() : 0;

$ppmpItemIds = $tableExists('procurement_ppmp_items')
    ? DB::table('procurement_ppmp_items')
        ->pluck('id')
        ->map(fn ($id) => (int) $id)
        ->unique()
        ->values()
    : collect();

$procurementIdsFromPpmpItems = $tableExists('procurement_items') && $columnExists('procurement_items', 'ppmp_item_id')
    ? DB::table('procurement_items')
        ->whereIn('ppmp_item_id', $ppmpItemIds)
        ->pluck('procurement_id')
        ->map(fn ($id) => (int) $id)
        ->unique()
        ->values()
    : collect();

$procurementIds = $tableExists('procurements')
    ? DB::table('procurements')
        ->when($columnExists('procurements', 'procurement_app_id'), fn ($query) => $query->orWhereNotNull('procurement_app_id'))
        ->when($columnExists('procurements', 'code'), fn ($query) => $query->orWhere('code', 'like', 'PPMP-%'))
        ->pluck('id')
        ->map(fn ($id) => (int) $id)
        ->merge($procurementIdsFromPpmpItems)
        ->unique()
        ->values()
    : collect();

$procurementRequestIds = $tableExists('procurements') && $columnExists('procurements', 'request_id')
    ? DB::table('procurements')->whereIn('id', $procurementIds)->pluck('request_id')
    : collect();

$ppmpRequestIds = $tableExists('procurement_ppmps') && $columnExists('procurement_ppmps', 'request_id')
    ? DB::table('procurement_ppmps')->pluck('request_id')
    : collect();

$requestIds = $procurementRequestIds
    ->merge($ppmpRequestIds)
    ->filter()
    ->map(fn ($id) => (int) $id)
    ->unique()
    ->values();

$commentTypes = [
    'App\\Models\\Procurement',
    'App\\Models\\ProcurementPpmp',
    'App\\Models\\ProcurementApp',
    'App\\Models\\ProcurementBac',
    'App\\Models\\ProcurementBacNoa',
    'App\\Models\\ProcurementNoaPo',
];

$notificationTypes = [
    'App\\Notifications\\ProcurementPlanCommentMentioned',
    'App\\Notifications\\ProcurementPlanForReviewNotification',
    'App\\Notifications\\ProcurementCommentMentioned',
];

$commentCountQuery = $tableExists('request_comments')
    ? DB::table('request_comments')->whereIn('commentable_type', $commentTypes)
    : null;

if ($commentCountQuery && $columnExists('request_comments', 'request_id')) {
    $commentCountQuery->orWhereIn('request_id', $requestIds);
}

$notificationCountQuery = $tableExists('notifications')
    ? DB::table('notifications')->whereIn('type', $notificationTypes)
    : null;

$planIds = $tableExists('procurement_ppmps')
    ? DB::table('procurement_ppmps')->pluck('id')->map(fn ($id) => (int) $id)->values()
    : collect();

$appIds = $tableExists('procurement_apps')
    ? DB::table('procurement_apps')->pluck('id')->map(fn ($id) => (int) $id)->values()
    : collect();

if ($notificationCountQuery && $columnExists('notifications', 'data')) {
    $notificationCountQuery->orWhere(function ($query) use ($planIds, $appIds, $procurementIds) {
        foreach ($planIds as $id) {
            $query->orWhere('data', 'like', '%"plan_id":'.$id.'%')
                ->orWhere('data', 'like', '%"plan_id":"'.$id.'"%');
        }

        foreach ($appIds as $id) {
            $query->orWhere('data', 'like', '%"app_id":'.$id.'%')
                ->orWhere('data', 'like', '%"app_id":"'.$id.'"%');
        }

        foreach ($procurementIds as $id) {
            $query->orWhere('data', 'like', '%"procurement_id":'.$id.'%')
                ->orWhere('data', 'like', '%"procurement_id":"'.$id.'"%');
        }
    });
}

$counts = [
    'procurement_apps' => $count('procurement_apps'),
    'procurement_ppmps' => $count('procurement_ppmps'),
    'procurement_ppmp_items' => $count('procurement_ppmp_items'),
    'linked_prs' => $procurementIds->count(),
    'linked_requests' => $requestIds->count(),
    'linked_comments' => $commentCountQuery ? $commentCountQuery->count() : 0,
    'linked_notifications' => $notificationCountQuery ? $notificationCountQuery->count() : 0,
];

echo ($execute ? 'EXECUTE' : 'DRY RUN').PHP_EOL;
foreach ($counts as $label => $value) {
    echo $label.': '.$value.PHP_EOL;
}

if (! $execute) {
    echo PHP_EOL.'No data deleted. Run with --execute to delete these records.'.PHP_EOL;
    return;
}

DB::transaction(function () use ($procurementIds, $requestIds, $commentTypes, $notificationTypes, $planIds, $appIds, $tableExists, $columnExists) {
    DB::statement('SET FOREIGN_KEY_CHECKS=0');

    if ($tableExists('notifications')) {
        $notifications = DB::table('notifications')->whereIn('type', $notificationTypes);

        if ($columnExists('notifications', 'data')) {
            $notifications->orWhere(function ($query) use ($planIds, $appIds, $procurementIds) {
                foreach ($planIds as $id) {
                    $query->orWhere('data', 'like', '%"plan_id":'.$id.'%')
                        ->orWhere('data', 'like', '%"plan_id":"'.$id.'"%');
                }

                foreach ($appIds as $id) {
                    $query->orWhere('data', 'like', '%"app_id":'.$id.'%')
                        ->orWhere('data', 'like', '%"app_id":"'.$id.'"%');
                }

                foreach ($procurementIds as $id) {
                    $query->orWhere('data', 'like', '%"procurement_id":'.$id.'%')
                        ->orWhere('data', 'like', '%"procurement_id":"'.$id.'"%');
                }
            });
        }

        $notifications->delete();
    }

    if ($tableExists('request_comments')) {
        $comments = DB::table('request_comments')->whereIn('commentable_type', $commentTypes);

        if ($columnExists('request_comments', 'request_id')) {
            $comments->orWhereIn('request_id', $requestIds);
        }

        $comments->delete();
    }

    foreach ([
        ['procurement_bac_noa_items', 'procurement_bac_noa_id', 'procurement_bac_noas'],
        ['procurement_po_ntps', 'procurement_id', null],
        ['procurement_po_iars', 'procurement_id', null],
        ['procurement_po_deliveries', 'procurement_id', null],
        ['procurement_noa_pos', 'procurement_id', null],
        ['procurement_bac_noas', 'procurement_id', null],
        ['procurement_bacs', 'procurement_id', null],
        ['procurement_quotation_items', 'procurement_item_id', 'procurement_items'],
        ['procurement_quotations', 'procurement_id', null],
        ['procurement_items', 'procurement_id', null],
        ['procurement_code_groups', 'procurement_id', null],
        ['procurement_code_budget_logs', 'procurement_id', null],
        ['procurement_assignments', 'procurement_id', null],
    ] as [$table, $column, $sourceTable]) {
        if (! $columnExists($table, $column)) {
            continue;
        }

        if ($sourceTable && $tableExists($sourceTable)) {
            $ids = DB::table($sourceTable)->whereIn('procurement_id', $procurementIds)->pluck('id');
            DB::table($table)->whereIn($column, $ids)->delete();
            continue;
        }

        DB::table($table)->whereIn($column, $procurementIds)->delete();
    }

    if ($tableExists('procurements')) {
        DB::table('procurements')->whereIn('id', $procurementIds)->delete();
    }

    if ($tableExists('procurement_ppmp_items')) {
        DB::table('procurement_ppmp_items')->delete();
    }

    if ($tableExists('procurement_ppmp_code_groups')) {
        DB::table('procurement_ppmp_code_groups')->delete();
    }

    if ($tableExists('procurement_ppmps')) {
        DB::table('procurement_ppmps')->delete();
    }

    if ($tableExists('procurement_apps')) {
        DB::table('procurement_apps')->delete();
    }

    if ($tableExists('requests')) {
        DB::table('requests')->whereIn('id', $requestIds)->delete();
    }

    DB::statement('SET FOREIGN_KEY_CHECKS=1');
});

echo PHP_EOL.'Deleted PPMP, SPP, APP, linked PR, linked request, comment, and notification data.'.PHP_EOL;
