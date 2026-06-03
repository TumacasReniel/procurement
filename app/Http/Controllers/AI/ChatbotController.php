<?php

namespace App\Http\Controllers\AI;

use App\Http\Controllers\Controller;
use App\Models\ChatbotModule;
use App\Models\ChatbotSetting;
use App\Models\InventoryItem;
use App\Models\InventoryStock;
use App\Services\AI\AIProviderService;
use App\Services\AI\DatabaseQueryService;
use App\Services\AI\LocalChatEngine;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class ChatbotController extends Controller
{
    public function __construct(private DatabaseQueryService $db) {}

    public function chat(Request $request): JsonResponse
    {
        $request->validate([
            'messages'           => ['required', 'array', 'max:40'],
            'messages.*.role'    => ['required', 'in:user,assistant'],
            'messages.*.content' => ['required'],
        ]);

        $messages = $request->input('messages');
        $user     = auth()->user();

        try {
            $reply = $this->runConversation($messages, $user);
            return response()->json(['reply' => $reply]);
        } catch (\Throwable $e) {
            return response()->json(['reply' => '⚠️ ' . $e->getMessage()], 200);
        }
    }

    private function runConversation(array $messages, $user): string
    {
        $setting = ChatbotSetting::current();

        // Use external AI when an API key is configured
        if ($setting->api_key && $setting->is_active) {
            return $this->runWithAI($messages, $user, $setting);
        }

        // No key configured — fall back to local rule-based engine
        $last     = collect($messages)->where('role', 'user')->last();
        $question = is_string($last['content'] ?? null) ? $last['content'] : '';
        $history  = array_slice($messages, 0, -1);

        return (new LocalChatEngine)->respond($question, $user, $history);
    }

    // ────────────────────────────────────────────────────────────
    // EXTERNAL AI PATH (OpenAI / Groq / OpenRouter)
    // ────────────────────────────────────────────────────────────

    private function runWithAI(array $messages, $user, ChatbotSetting $setting): string
    {
        $provider = new AIProviderService($setting);
        $system   = $this->buildSystemPrompt($user);
        $tools    = $this->buildTools();

        $response = $provider->chat($system, $messages, $tools);

        // Tool-use loop (max 5 rounds to prevent runaway calls)
        $rounds = 0;
        while (($response['stop_reason'] ?? '') === 'tool_use' && $rounds < 5) {
            $rounds++;
            $toolBlocks = collect($response['content'])->where('type', 'tool_use');
            $messages[] = ['role' => 'assistant', 'content' => $response['content']];

            $toolResults = $toolBlocks->map(fn ($block) => [
                'type'        => 'tool_result',
                'tool_use_id' => $block['id'],
                'content'     => json_encode(
                    $this->executeTool($block['name'], $block['input'] ?? []),
                    JSON_UNESCAPED_UNICODE
                ),
            ])->values()->toArray();

            $messages[] = ['role' => 'user', 'content' => $toolResults];
            $response   = $provider->chat($system, $messages, $tools);
        }

        return collect($response['content'] ?? [])
            ->where('type', 'text')
            ->pluck('text')
            ->first() ?? 'I was unable to generate a response.';
    }

    // ────────────────────────────────────────────────────────────
    // SYSTEM PROMPT
    // ────────────────────────────────────────────────────────────

    private function buildSystemPrompt($user): string
    {
        $name    = $user->profile?->fullname ?? $user->username ?? 'User';
        $date    = now()->format('F j, Y \a\t g:i A');
        $modules = ChatbotModule::enabled()->map(fn ($m) =>
            "  - {$m->module_key}: {$m->label} — {$m->description}"
        )->implode("\n");

        return <<<PROMPT
You are an intelligent assistant for the Procurement and Inventory Management System.
You help staff quickly find information, generate summaries, and answer questions.

Current user: {$name}
Current date/time: {$date}

## How to respond
1. When the user asks for data, ALWAYS call the appropriate tool first.
2. Present results as clean markdown: **bold** for emphasis, tables for record lists.
3. Use ₱ (Philippine Peso) for all monetary values.
4. Be concise. Do not repeat back what the user said.
5. If a tool returns no results, say so clearly and suggest what else you can do.

## Available data modules (use with query_module tool)
{$modules}

## Available analytics tools
- get_inventory_summary — complete inventory overview with totals and health status
- search_inventory_items — find items by name/code, returns qty on hand
- get_low_stock_items — items below a quantity threshold
- get_inventory_value — total peso valuation of all stock
- get_category_breakdown — items grouped by category
- get_procurement_summary — procurement records totalled by status
PROMPT;
    }

    // ────────────────────────────────────────────────────────────
    // TOOL DEFINITIONS
    // ────────────────────────────────────────────────────────────

    private function buildTools(): array
    {
        $moduleKeys = ChatbotModule::enabled()->pluck('module_key')->implode(', ');
        $emptyObj   = new \stdClass();

        return [
            [
                'name'         => 'query_module',
                'description'  => 'Query any configured data module to list or search records. Use this for PPMP, procurement requests, finance requests, purchase orders, RIS, receivings, withdrawals, suppliers, and any other module.',
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'module_key' => [
                            'type'        => 'string',
                            'description' => "Which module to query. Enabled keys: {$moduleKeys}",
                        ],
                        'status'  => ['type' => 'string',  'description' => 'Optional status filter e.g. Pending, Approved, Returned'],
                        'keyword' => ['type' => 'string',  'description' => 'Optional search keyword (title, code, name, etc.)'],
                        'limit'   => ['type' => 'integer', 'description' => 'Max records to return (default 10, max 20)'],
                    ],
                    'required' => ['module_key'],
                ],
            ],
            [
                'name'         => 'get_inventory_summary',
                'description'  => 'Get a complete inventory overview: total items, total qty, total value, low-stock count, out-of-stock count, and transaction totals.',
                'input_schema' => ['type' => 'object', 'properties' => $emptyObj, 'required' => []],
            ],
            [
                'name'         => 'search_inventory_items',
                'description'  => 'Search inventory items by name, code, or category. Returns code, name, category, qty on hand, and stock entry count.',
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'keyword' => ['type' => 'string',  'description' => 'Name, code, or category to search. Leave blank for most recent.'],
                        'limit'   => ['type' => 'integer', 'description' => 'Max results (default 10)'],
                    ],
                    'required' => [],
                ],
            ],
            [
                'name'         => 'get_low_stock_items',
                'description'  => 'Return all inventory items at or below a quantity threshold. Use threshold=0 for out-of-stock only.',
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'threshold' => ['type' => 'number', 'description' => 'Qty threshold (default 5)'],
                    ],
                    'required' => [],
                ],
            ],
            [
                'name'         => 'get_inventory_value',
                'description'  => 'Return the total peso valuation of all inventory stock (quantity × unit cost).',
                'input_schema' => ['type' => 'object', 'properties' => $emptyObj, 'required' => []],
            ],
            [
                'name'         => 'get_category_breakdown',
                'description'  => 'Return inventory items grouped by category, with item count and total qty per category.',
                'input_schema' => ['type' => 'object', 'properties' => $emptyObj, 'required' => []],
            ],
            [
                'name'         => 'get_procurement_summary',
                'description'  => 'Return total procurement record count and a breakdown by status.',
                'input_schema' => ['type' => 'object', 'properties' => $emptyObj, 'required' => []],
            ],
        ];
    }

    // ────────────────────────────────────────────────────────────
    // TOOL EXECUTION
    // ────────────────────────────────────────────────────────────

    private function executeTool(string $name, array $input): mixed
    {
        return match ($name) {
            'query_module'           => $this->executeModuleQuery($input),
            'get_inventory_summary'  => $this->db->inventorySummary(),
            'search_inventory_items' => $this->db->searchItems($input['keyword'] ?? '', (int) ($input['limit'] ?? 10)),
            'get_low_stock_items'    => $this->db->lowStockItems((float) ($input['threshold'] ?? 5)),
            'get_inventory_value'    => $this->dbInventoryValue(),
            'get_category_breakdown' => $this->dbCategoryBreakdown(),
            'get_procurement_summary'=> $this->db->procurementSummary(),
            default                  => ['error' => "Unknown tool: {$name}"],
        };
    }

    private function executeModuleQuery(array $input): array
    {
        $moduleKey = $input['module_key'] ?? '';
        $module    = ChatbotModule::where('module_key', $moduleKey)
                        ->where('is_enabled', true)
                        ->first();

        if (!$module) {
            return ['error' => "Module '{$moduleKey}' not found or is disabled."];
        }

        if (!Schema::hasTable($module->table_name)) {
            return ['error' => "Table '{$module->table_name}' does not exist yet."];
        }

        $modelClass = $module->model_class;
        if (!class_exists($modelClass) || !is_subclass_of($modelClass, Model::class)) {
            return ['error' => "Model for '{$moduleKey}' is not configured correctly."];
        }

        $status  = $input['status']  ?? '';
        $keyword = $input['keyword'] ?? '';
        $limit   = min((int) ($input['limit'] ?? 10), 20);

        $q = $modelClass::query();

        foreach ($module->eager_loads ?? [] as $rel) {
            $q->with($rel);
        }

        if ($status && $module->status_relation) {
            $q->whereHas($module->status_relation, fn ($s) => $s->where('name', $status));
        }

        $searchCols = $module->searchable_columns  ?? [];
        $searchRels = $module->searchable_relations ?? [];

        if ($keyword && ($searchCols || $searchRels)) {
            $q->where(function ($outer) use ($keyword, $searchCols, $searchRels) {
                $first = true;
                foreach ($searchCols as $col) {
                    if ($first) { $outer->where($col, 'like', "%{$keyword}%"); $first = false; }
                    else        { $outer->orWhere($col, 'like', "%{$keyword}%"); }
                }
                foreach ($searchRels as $rel => $cols) {
                    $method = $first ? 'whereHas' : 'orWhereHas';
                    $first  = false;
                    $outer->{$method}($rel, function ($rq) use ($keyword, $cols) {
                        $rq->where(function ($inner) use ($keyword, $cols) {
                            foreach ($cols as $i => $col) {
                                if ($i === 0) $inner->where($col, 'like', "%{$keyword}%");
                                else          $inner->orWhere($col, 'like', "%{$keyword}%");
                            }
                        });
                    });
                }
            });
        }

        $orderCol = $module->order_column ?: 'created_at';
        $total    = (clone $q)->count();
        $rows     = $q->orderByDesc($orderCol)->limit($limit)->get();

        $mapped = $rows->map(function ($row) use ($module) {
            $result = [];
            foreach ($module->display_columns as $def) {
                $key = $def['key'] ?? ($def['col'] ?? null);
                if (!$key) continue;

                if (isset($def['relation'])) {
                    $raw = $row->{$def['relation']}?->{$def['col']} ?? null;
                } else {
                    $raw = $row->{$def['col'] ?? $key} ?? null;
                }

                $result[$key] = $this->formatValue($raw, $def['format'] ?? null);
            }
            return $result;
        });

        return [
            'module'   => $module->label,
            'total'    => $total,
            'returned' => $rows->count(),
            'rows'     => $mapped->toArray(),
        ];
    }

    private function formatValue(mixed $value, ?string $format): string
    {
        if ($value === null || $value === '') return '—';
        return match ($format) {
            'date'  => $this->tryDate($value),
            'money' => '₱' . number_format((float) $value, 2),
            default => (string) $value,
        };
    }

    private function tryDate(mixed $value): string
    {
        try { return Carbon::parse($value)->format('M d, Y'); }
        catch (\Throwable) { return (string) $value; }
    }

    private function dbInventoryValue(): array
    {
        if (!Schema::hasTable('inventory_stocks')) return ['error' => 'not_setup'];
        return [
            'total_value' => (float) InventoryStock::selectRaw('SUM(quantity * unit_cost) as total')->value('total'),
            'total_qty'   => (float) InventoryStock::sum('quantity'),
            'stock_count' => InventoryStock::count(),
        ];
    }

    private function dbCategoryBreakdown(): array
    {
        if (!Schema::hasTable('inventory_items')) return ['error' => 'not_setup'];
        return InventoryItem::with('category')
            ->withSum('stocks', 'quantity')
            ->get()
            ->groupBy(fn ($i) => $i->category?->name ?? 'Uncategorized')
            ->map(fn ($g, $cat) => ['category' => $cat, 'count' => $g->count(), 'qty' => round($g->sum('stocks_sum_quantity'), 2)])
            ->sortByDesc('count')
            ->values()
            ->toArray();
    }
}
