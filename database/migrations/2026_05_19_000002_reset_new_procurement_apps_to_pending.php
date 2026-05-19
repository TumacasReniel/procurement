<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('procurement_apps')) {
            return;
        }

        $pendingStatusId = DB::table('list_statuses')
            ->where('name', 'Pending')
            ->where('classification', 'Procurement')
            ->value('id');

        $approvedStatusId = DB::table('list_statuses')
            ->where('name', 'Approved')
            ->where('classification', 'Procurement')
            ->value('id');

        if (! $pendingStatusId || ! $approvedStatusId) {
            return;
        }

        DB::table('procurement_apps')
            ->where('status_id', $approvedStatusId)
            ->update([
                'status_id' => $pendingStatusId,
                'approved_by_id' => null,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        //
    }
};
