<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('procurements')) {
            return;
        }

        if (!Schema::hasColumn('procurements', 'request_id')) {
            Schema::table('procurements', function (Blueprint $table) {
                $table->unsignedBigInteger('request_id')->nullable()->after('id');
            });

            if (Schema::hasTable('requests')) {
                Schema::table('procurements', function (Blueprint $table) {
                    $table->foreign('request_id')
                        ->references('id')
                        ->on('requests')
                        ->nullOnDelete();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('procurements') || !Schema::hasColumn('procurements', 'request_id')) {
            return;
        }

        Schema::table('procurements', function (Blueprint $table) {
            try {
                $table->dropForeign(['request_id']);
            } catch (\Throwable $e) {
                // Ignore if foreign key is not present.
            }
        });

        Schema::table('procurements', function (Blueprint $table) {
            $table->dropColumn('request_id');
        });
    }
};
