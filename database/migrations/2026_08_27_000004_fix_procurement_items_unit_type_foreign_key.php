<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * procurement_items.item_unit_type_id stores a unit_types id (ProcurementItem::item_unit_type()
 * belongs to App\Models\UnitType, and the frontend's unit-type dropdown is sourced from the
 * unit_types table via DropdownClass). The FK was defined against list_dropdowns instead,
 * because the procurement_items migration (2026_01_30_084446) predates the unit_types table
 * migration (2026_01_30_094237). It only "worked" by coincidental id overlap between the tables.
 *
 * Some environments never actually got the original FK applied (only the index exists), so
 * the drop is guarded rather than assumed.
 */
return new class extends Migration
{
    public function up(): void
    {
        if ($this->foreignKeyExists('procurement_items', 'procurement_items_item_unit_type_id_foreign')) {
            Schema::table('procurement_items', function (Blueprint $table) {
                $table->dropForeign('procurement_items_item_unit_type_id_foreign');
            });
        }

        Schema::table('procurement_items', function (Blueprint $table) {
            $table->foreign('item_unit_type_id')->references('id')->on('unit_types')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('procurement_items', function (Blueprint $table) {
            $table->dropForeign(['item_unit_type_id']);
        });

        Schema::table('procurement_items', function (Blueprint $table) {
            $table->foreign('item_unit_type_id')->references('id')->on('list_dropdowns')->onDelete('cascade');
        });
    }

    private function foreignKeyExists(string $table, string $constraintName): bool
    {
        $result = \Illuminate\Support\Facades\DB::select(
            'SELECT 1 FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = \'FOREIGN KEY\'',
            [$table, $constraintName]
        );

        return count($result) > 0;
    }
};
