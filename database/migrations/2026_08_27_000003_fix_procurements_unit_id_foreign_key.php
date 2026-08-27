<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * procurements.unit_id has always stored a list_units id (that's what the "Unit"
 * dropdown on the PR form is sourced from — DropdownClass::units()), but the foreign
 * key was defined against list_dropdowns instead. It has "worked" only by coincidence:
 * every existing list_units id happens to also exist in list_dropdowns. Point it at the
 * table it actually stores ids from.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('procurements', function (Blueprint $table) {
            $table->dropForeign('procurements_unit_id_foreign');
        });

        Schema::table('procurements', function (Blueprint $table) {
            $table->foreign('unit_id')->references('id')->on('list_units');
        });
    }

    public function down(): void
    {
        Schema::table('procurements', function (Blueprint $table) {
            $table->dropForeign(['unit_id']);
        });

        Schema::table('procurements', function (Blueprint $table) {
            $table->foreign('unit_id')->references('id')->on('list_dropdowns');
        });
    }
};
