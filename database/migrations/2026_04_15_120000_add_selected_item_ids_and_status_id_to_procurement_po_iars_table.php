<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $hasSelectedItemIds = Schema::hasColumn('procurement_po_iars', 'selected_item_ids');
        $hasStatusId = Schema::hasColumn('procurement_po_iars', 'status_id');

        if (!$hasSelectedItemIds || !$hasStatusId) {
            Schema::table('procurement_po_iars', function (Blueprint $table) use ($hasSelectedItemIds, $hasStatusId) {
                if (!$hasSelectedItemIds) {
                    $table->json('selected_item_ids')->nullable()->after('code');
                }

                if (!$hasStatusId) {
                    $table->unsignedInteger('status_id')->nullable()->after('selected_item_ids');
                }
            });
        }

        $generatedStatusId = DB::table('list_statuses')
            ->where('classification', 'Procurement')
            ->where('name', 'Generated')
            ->value('id');


        if ($generatedStatusId && Schema::hasColumn('procurement_po_iars', 'status_id')) {
            DB::table('procurement_po_iars')
                ->whereNull('status_id')
                ->update(['status_id' => $generatedStatusId]);

            if ($pendingStatusId) {
                DB::table('procurement_po_iars')
                    ->where('status_id', $pendingStatusId)
                    ->update(['status_id' => $generatedStatusId]);
            }
        }
    }

    public function down(): void
    {
        $hasSelectedItemIds = Schema::hasColumn('procurement_po_iars', 'selected_item_ids');
        $hasStatusId = Schema::hasColumn('procurement_po_iars', 'status_id');

        if (!$hasSelectedItemIds && !$hasStatusId) {
            return;
        }

        Schema::table('procurement_po_iars', function (Blueprint $table) use ($hasSelectedItemIds, $hasStatusId) {
            if ($hasStatusId) {
                $table->dropColumn('status_id');
            }

            if ($hasSelectedItemIds) {
                $table->dropColumn('selected_item_ids');
            }
        });
    }
};
