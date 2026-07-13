<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('procurement_ppmps', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->unsignedBigInteger('request_id')->nullable();
            $table->string('code')->unique();
            $table->string('title')->nullable();
            $table->date('date');
            $table->text('purpose');
            $table->unsignedSmallInteger('division_id')->nullable()->index();
            $table->unsignedSmallInteger('unit_id')->nullable()->index();
            $table->unsignedSmallInteger('fund_cluster_id')->nullable()->index();
            $table->unsignedTinyInteger('classification_id')->nullable()->index();
            $table->unsignedSmallInteger('reference_app_id')->nullable()->index();
            $table->unsignedInteger('procurement_app_id')->nullable()->index();
            $table->unsignedInteger('created_by_id')->nullable()->index();
            $table->unsignedInteger('requested_by_id')->nullable()->index();
            $table->unsignedInteger('approved_by_id')->nullable()->index();
            $table->unsignedTinyInteger('status_id')->nullable()->index();
            $table->unsignedTinyInteger('sub_status_id')->nullable()->index();
            $table->unsignedInteger('source_procurement_id')->nullable()->unique();
            $table->timestamps();

            $table->foreign('procurement_app_id')
                ->references('id')
                ->on('procurement_apps')
                ->nullOnDelete();
        });

        Schema::create('procurement_ppmp_items', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('item_no');
            $table->unsignedInteger('procurement_ppmp_id')->index();
            $table->unsignedTinyInteger('item_unit_type_id')->nullable()->index();
            $table->string('item_name');
            $table->text('item_description')->nullable();
            $table->string('item_quantity');
            $table->decimal('item_unit_cost', 15, 2)->default(0);
            $table->decimal('total_cost', 15, 2)->default(0);
            $table->unsignedTinyInteger('status_id')->nullable()->index();
            $table->unsignedInteger('source_procurement_item_id')->nullable()->unique();
            $table->timestamps();

            $table->foreign('procurement_ppmp_id')
                ->references('id')
                ->on('procurement_ppmps')
                ->onDelete('cascade');
        });

        Schema::create('procurement_ppmp_code_groups', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->unsignedInteger('procurement_code_id')->index();
            $table->unsignedInteger('procurement_ppmp_id')->index();
            $table->timestamps();

            $table->foreign('procurement_code_id')
                ->references('id')
                ->on('procurement_codes')
                ->onDelete('cascade');
            $table->foreign('procurement_ppmp_id')
                ->references('id')
                ->on('procurement_ppmps')
                ->onDelete('cascade');
        });

        if (Schema::hasTable('procurements')) {
            $this->copyExistingPpmps();
        }

        if (Schema::hasTable('procurement_items') && !Schema::hasColumn('procurement_items', 'ppmp_item_id')) {
            Schema::table('procurement_items', function (Blueprint $table) {
                $table->unsignedInteger('ppmp_item_id')->nullable()->after('procurement_id')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('procurement_items') && Schema::hasColumn('procurement_items', 'ppmp_item_id')) {
            Schema::table('procurement_items', function (Blueprint $table) {
                $table->dropIndex(['ppmp_item_id']);
                $table->dropColumn('ppmp_item_id');
            });
        }

        Schema::dropIfExists('procurement_ppmp_code_groups');
        Schema::dropIfExists('procurement_ppmp_items');
        Schema::dropIfExists('procurement_ppmps');
    }

    private function copyExistingPpmps(): void
    {
        $appTypeIds = DB::table('list_dropdowns')
            ->whereIn('name', ['Annual Procurement Plan', 'Supplemental Procurement Plan'])
            ->pluck('id');

        $sourcePpmps = DB::table('procurements')
            ->where(function ($query) use ($appTypeIds) {
                $query->where('code', 'LIKE', 'PPMP-%')
                    ->orWhere('code', 'LIKE', 'SPP-%')
                    ->orWhereIn('reference_app_id', $appTypeIds);
            })
            ->get();

        foreach ($sourcePpmps as $source) {
            $ppmpId = DB::table('procurement_ppmps')->insertGetId([
                'request_id' => $source->request_id ?? null,
                'code' => $source->code,
                'title' => $source->title,
                'date' => $source->date,
                'purpose' => $source->purpose,
                'division_id' => $source->division_id,
                'unit_id' => $source->unit_id,
                'fund_cluster_id' => $source->fund_cluster_id,
                'classification_id' => $source->classification_id ?? null,
                'reference_app_id' => $source->reference_app_id ?? null,
                'procurement_app_id' => $source->procurement_app_id ?? null,
                'created_by_id' => $source->created_by_id,
                'requested_by_id' => $source->requested_by_id,
                'approved_by_id' => $source->approved_by_id,
                'status_id' => $source->status_id,
                'sub_status_id' => $source->sub_status_id ?? null,
                'source_procurement_id' => $source->id,
                'created_at' => $source->created_at,
                'updated_at' => $source->updated_at,
            ]);

            DB::table('procurement_items')
                ->where('procurement_id', $source->id)
                ->orderBy('id')
                ->get()
                ->each(function ($item) use ($ppmpId) {
                    DB::table('procurement_ppmp_items')->insert([
                        'item_no' => $item->item_no,
                        'procurement_ppmp_id' => $ppmpId,
                        'item_unit_type_id' => $item->item_unit_type_id,
                        'item_name' => $item->item_name,
                        'item_description' => $item->item_description,
                        'item_quantity' => $item->item_quantity,
                        'item_unit_cost' => $item->item_unit_cost,
                        'total_cost' => $item->total_cost,
                        'status_id' => $item->status_id,
                        'source_procurement_item_id' => $item->id,
                        'created_at' => $item->created_at,
                        'updated_at' => $item->updated_at,
                    ]);
                });

            if (Schema::hasTable('procurement_code_groups')) {
                DB::table('procurement_code_groups')
                    ->where('procurement_id', $source->id)
                    ->get()
                    ->each(function ($codeGroup) use ($ppmpId) {
                        DB::table('procurement_ppmp_code_groups')->insert([
                            'procurement_code_id' => $codeGroup->procurement_code_id,
                            'procurement_ppmp_id' => $ppmpId,
                            'created_at' => $codeGroup->created_at,
                            'updated_at' => $codeGroup->updated_at,
                        ]);
                    });
            }
        }
    }
};
