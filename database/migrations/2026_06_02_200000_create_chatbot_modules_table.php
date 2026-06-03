<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chatbot_modules', function (Blueprint $table) {
            $table->id();
            $table->string('module_key', 60)->unique();
            $table->string('label', 100);
            $table->string('description', 255)->nullable();
            $table->string('icon', 10)->nullable();
            $table->string('table_name', 100);
            $table->string('model_class', 150);
            $table->boolean('is_enabled')->default(true);
            $table->json('intent_phrases');
            $table->unsignedTinyInteger('intent_weight')->default(3);
            $table->json('eager_loads')->nullable();
            $table->json('display_columns');
            $table->json('searchable_columns')->nullable();
            $table->json('searchable_relations')->nullable();
            $table->string('status_relation', 50)->nullable();
            $table->string('order_column', 50)->default('created_at');
            $table->timestamps();
        });

        $this->seed();
    }

    public function down(): void
    {
        Schema::dropIfExists('chatbot_modules');
    }

    private function seed(): void
    {
        $now = now();

        $modules = [
            [
                'module_key'    => 'ppmp',
                'label'         => 'PPMP',
                'description'   => 'Project Procurement Management Plan records',
                'icon'          => '📑',
                'table_name'    => 'procurement_ppmps',
                'model_class'   => 'App\Models\ProcurementPpmp',
                'is_enabled'    => true,
                'intent_phrases' => ['ppmp', 'procurement plan', 'project procurement', 'management plan'],
                'intent_weight' => 4,
                'eager_loads'   => ['status', 'division'],
                'display_columns' => [
                    ['key' => 'code',     'label' => 'Code',     'col' => 'code'],
                    ['key' => 'title',    'label' => 'Title',    'col' => 'title'],
                    ['key' => 'division', 'label' => 'Division', 'col' => 'name', 'relation' => 'division'],
                    ['key' => 'status',   'label' => 'Status',   'col' => 'name', 'relation' => 'status'],
                    ['key' => 'date',     'label' => 'Date',     'col' => 'date'],
                ],
                'searchable_columns' => ['title', 'code', 'purpose'],
                'searchable_relations' => null,
                'status_relation' => 'status',
                'order_column'  => 'created_at',
            ],
            [
                'module_key'    => 'procurement_requests',
                'label'         => 'Procurement Requests',
                'description'   => 'Individual procurement/purchase request records',
                'icon'          => '🛒',
                'table_name'    => 'procurements',
                'model_class'   => 'App\Models\Procurement',
                'is_enabled'    => true,
                'intent_phrases' => ['list procurement', 'show procurement', 'procurement list', 'procurement records', 'all procurement', 'purchase request list'],
                'intent_weight' => 3,
                'eager_loads'   => ['status', 'division'],
                'display_columns' => [
                    ['key' => 'code',     'label' => 'Code',     'col' => 'code'],
                    ['key' => 'title',    'label' => 'Title',    'col' => 'title'],
                    ['key' => 'division', 'label' => 'Division', 'col' => 'name', 'relation' => 'division'],
                    ['key' => 'status',   'label' => 'Status',   'col' => 'name', 'relation' => 'status'],
                    ['key' => 'date',     'label' => 'Date',     'col' => 'date'],
                ],
                'searchable_columns' => ['title', 'code', 'purpose'],
                'searchable_relations' => null,
                'status_relation' => 'status',
                'order_column'  => 'created_at',
            ],
            [
                'module_key'    => 'procurement_app',
                'label'         => 'Annual Procurement Plan',
                'description'   => 'Annual Procurement Plan (APP) records',
                'icon'          => '📅',
                'table_name'    => 'procurement_apps',
                'model_class'   => 'App\Models\ProcurementApp',
                'is_enabled'    => true,
                'intent_phrases' => ['annual procurement plan', 'procurement app', 'app plan', 'annual plan'],
                'intent_weight' => 4,
                'eager_loads'   => ['status', 'app_type'],
                'display_columns' => [
                    ['key' => 'code',     'label' => 'Code',     'col' => 'code'],
                    ['key' => 'title',    'label' => 'Title',    'col' => 'title'],
                    ['key' => 'year',     'label' => 'Year',     'col' => 'year'],
                    ['key' => 'app_type', 'label' => 'Type',     'col' => 'name', 'relation' => 'app_type'],
                    ['key' => 'status',   'label' => 'Status',   'col' => 'name', 'relation' => 'status'],
                ],
                'searchable_columns' => ['title', 'code'],
                'searchable_relations' => null,
                'status_relation' => 'status',
                'order_column'  => 'created_at',
            ],
            [
                'module_key'    => 'procurement_bac',
                'label'         => 'BAC Resolutions',
                'description'   => 'Bids and Awards Committee resolution records',
                'icon'          => '⚖️',
                'table_name'    => 'procurement_bacs',
                'model_class'   => 'App\Models\ProcurementBac',
                'is_enabled'    => true,
                'intent_phrases' => ['bac resolution', 'bids and awards', 'bac committee', 'bac record'],
                'intent_weight' => 4,
                'eager_loads'   => ['status'],
                'display_columns' => [
                    ['key' => 'code',        'label' => 'Code',        'col' => 'code'],
                    ['key' => 'type',        'label' => 'Type',        'col' => 'type'],
                    ['key' => 'status',      'label' => 'Status',      'col' => 'name', 'relation' => 'status'],
                    ['key' => 'approved_at', 'label' => 'Approved At', 'col' => 'approved_at', 'format' => 'date'],
                ],
                'searchable_columns' => ['code', 'type'],
                'searchable_relations' => null,
                'status_relation' => 'status',
                'order_column'  => 'created_at',
            ],
            [
                'module_key'    => 'procurement_po',
                'label'         => 'Purchase Orders',
                'description'   => 'Purchase Order records',
                'icon'          => '📦',
                'table_name'    => 'procurement_noa_pos',
                'model_class'   => 'App\Models\ProcurementNoaPo',
                'is_enabled'    => true,
                'intent_phrases' => ['purchase order', 'po list', 'show purchase order', 'purchase orders'],
                'intent_weight' => 4,
                'eager_loads'   => ['status'],
                'display_columns' => [
                    ['key' => 'code',          'label' => 'PO Code',    'col' => 'code'],
                    ['key' => 'po_date',        'label' => 'PO Date',    'col' => 'po_date', 'format' => 'date'],
                    ['key' => 'delivery_term',  'label' => 'Delivery',   'col' => 'delivery_term'],
                    ['key' => 'status',         'label' => 'Status',     'col' => 'name', 'relation' => 'status'],
                ],
                'searchable_columns' => ['code'],
                'searchable_relations' => null,
                'status_relation' => 'status',
                'order_column'  => 'po_date',
            ],
            [
                'module_key'    => 'finance_requests',
                'label'         => 'Finance Requests',
                'description'   => 'Finance and budget request records',
                'icon'          => '💰',
                'table_name'    => 'finance_requests',
                'model_class'   => 'App\Models\FinanceRequest',
                'is_enabled'    => true,
                'intent_phrases' => ['finance request', 'financial request', 'fund request', 'finance records', 'budget request'],
                'intent_weight' => 4,
                'eager_loads'   => ['status', 'division', 'request_type'],
                'display_columns' => [
                    ['key' => 'code',         'label' => 'Code',        'col' => 'code'],
                    ['key' => 'particulars',  'label' => 'Particulars', 'col' => 'particulars'],
                    ['key' => 'amount',       'label' => 'Amount',      'col' => 'amount', 'format' => 'money'],
                    ['key' => 'division',     'label' => 'Division',    'col' => 'name', 'relation' => 'division'],
                    ['key' => 'status',       'label' => 'Status',      'col' => 'name', 'relation' => 'status'],
                    ['key' => 'date',         'label' => 'Date',        'col' => 'date'],
                ],
                'searchable_columns' => ['code', 'particulars'],
                'searchable_relations' => null,
                'status_relation' => 'status',
                'order_column'  => 'date',
            ],
            [
                'module_key'    => 'inventory_receivings',
                'label'         => 'Inventory Receivings',
                'description'   => 'Goods received/incoming delivery records',
                'icon'          => '📥',
                'table_name'    => 'inventory_receivings',
                'model_class'   => 'App\Models\InventoryReceiving',
                'is_enabled'    => true,
                'intent_phrases' => ['receiving', 'receivings', 'received', 'incoming', 'delivery', 'delivered', 'arrived', 'inbound'],
                'intent_weight' => 2,
                'eager_loads'   => ['item', 'status'],
                'display_columns' => [
                    ['key' => 'item_name',    'label' => 'Item',        'col' => 'name', 'relation' => 'item'],
                    ['key' => 'item_code',    'label' => 'Code',        'col' => 'code', 'relation' => 'item'],
                    ['key' => 'status',       'label' => 'Status',      'col' => 'name', 'relation' => 'status'],
                    ['key' => 'received_at',  'label' => 'Received',    'col' => 'received_at', 'format' => 'date'],
                ],
                'searchable_columns' => ['remarks'],
                'searchable_relations' => ['item' => ['name', 'code']],
                'status_relation' => 'status',
                'order_column'  => 'received_at',
            ],
            [
                'module_key'    => 'inventory_withdrawals',
                'label'         => 'Inventory Withdrawals',
                'description'   => 'Stock withdrawal and issuance records',
                'icon'          => '📤',
                'table_name'    => 'inventory_withdrawals',
                'model_class'   => 'App\Models\InventoryWithdrawal',
                'is_enabled'    => true,
                'intent_phrases' => ['withdrawal', 'withdrawals', 'withdrawn', 'issued', 'released', 'taken out', 'outgoing', 'dispensed'],
                'intent_weight' => 2,
                'eager_loads'   => ['item', 'status'],
                'display_columns' => [
                    ['key' => 'item_name',   'label' => 'Item',      'col' => 'name', 'relation' => 'item'],
                    ['key' => 'item_code',   'label' => 'Code',      'col' => 'code', 'relation' => 'item'],
                    ['key' => 'status',      'label' => 'Status',    'col' => 'name', 'relation' => 'status'],
                    ['key' => 'released_at', 'label' => 'Released',  'col' => 'released_at', 'format' => 'date'],
                ],
                'searchable_columns' => [],
                'searchable_relations' => ['item' => ['name', 'code']],
                'status_relation' => 'status',
                'order_column'  => 'released_at',
            ],
            [
                'module_key'    => 'inventory_ris',
                'label'         => 'RIS',
                'description'   => 'Requisition and Issue Slips',
                'icon'          => '📋',
                'table_name'    => 'inventory_ris',
                'model_class'   => 'App\Models\InventoryRis',
                'is_enabled'    => true,
                'intent_phrases' => ['ris', 'requisition', 'issue slip', 'request slip', 'issuance slip'],
                'intent_weight' => 2,
                'eager_loads'   => ['status'],
                'display_columns' => [
                    ['key' => 'ris_no',   'label' => 'RIS No.',   'col' => 'ris_no'],
                    ['key' => 'division', 'label' => 'Division',  'col' => 'division'],
                    ['key' => 'purpose',  'label' => 'Purpose',   'col' => 'purpose'],
                    ['key' => 'status',   'label' => 'Status',    'col' => 'name', 'relation' => 'status'],
                    ['key' => 'ris_date', 'label' => 'Date',      'col' => 'ris_date'],
                ],
                'searchable_columns' => ['ris_no', 'division', 'purpose'],
                'searchable_relations' => null,
                'status_relation' => 'status',
                'order_column'  => 'ris_date',
            ],
            [
                'module_key'    => 'suppliers',
                'label'         => 'Suppliers',
                'description'   => 'Accredited supplier / vendor records',
                'icon'          => '🏭',
                'table_name'    => 'suppliers',
                'model_class'   => 'App\Models\Supplier',
                'is_enabled'    => true,
                'intent_phrases' => ['supplier', 'vendor', 'supplier list', 'accredited supplier', 'accredited vendor'],
                'intent_weight' => 3,
                'eager_loads'   => [],
                'display_columns' => [
                    ['key' => 'name',            'label' => 'Name',             'col' => 'name'],
                    ['key' => 'code',            'label' => 'Code',             'col' => 'code'],
                    ['key' => 'tin',             'label' => 'TIN',              'col' => 'tin'],
                    ['key' => 'approval_status', 'label' => 'Approval Status',  'col' => 'approval_status'],
                ],
                'searchable_columns' => ['name', 'code', 'tin'],
                'searchable_relations' => null,
                'status_relation' => null,
                'order_column'  => 'created_at',
            ],
        ];

        foreach ($modules as $module) {
            DB::table('chatbot_modules')->insert([
                'module_key'           => $module['module_key'],
                'label'                => $module['label'],
                'description'          => $module['description'],
                'icon'                 => $module['icon'],
                'table_name'           => $module['table_name'],
                'model_class'          => $module['model_class'],
                'is_enabled'           => $module['is_enabled'],
                'intent_phrases'       => json_encode($module['intent_phrases']),
                'intent_weight'        => $module['intent_weight'],
                'eager_loads'          => json_encode($module['eager_loads']),
                'display_columns'      => json_encode($module['display_columns']),
                'searchable_columns'   => json_encode($module['searchable_columns'] ?? []),
                'searchable_relations' => $module['searchable_relations'] ? json_encode($module['searchable_relations']) : null,
                'status_relation'      => $module['status_relation'],
                'order_column'         => $module['order_column'],
                'created_at'           => $now,
                'updated_at'           => $now,
            ]);
        }
    }
};
