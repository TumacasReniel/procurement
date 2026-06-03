<?php

namespace App\Services\AI;

use App\Models\ChatbotModule;
use App\Models\InventoryItem;
use App\Models\InventoryStock;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

/**
 * Rule-based local AI engine.
 *
 * Falls back to this when no external AI key is configured.
 * When a key IS configured, ChatbotController uses AIProviderService instead.
 *
 * Pipeline per message:
 *   1. Analyse history → detect follow-up intent
 *   2. classifyIntent() on current message
 *   3. extractEntities()
 *   4. queryDatabase()
 *   5. formatResponse()  — always natural-language lead, then data
 */
class LocalChatEngine
{
    // ── Static intent constants ────────────────────────────────────
    private const INTENT_GREETING           = 'greeting';
    private const INTENT_THANKS             = 'thanks';
    private const INTENT_HELP               = 'help';
    private const INTENT_WHO_ARE_YOU        = 'who_are_you';
    private const INTENT_INVENTORY_SUMMARY  = 'inventory_summary';
    private const INTENT_ITEM_COUNT         = 'item_count';
    private const INTENT_LOW_STOCK          = 'low_stock';
    private const INTENT_OUT_OF_STOCK       = 'out_of_stock';
    private const INTENT_TOP_STOCKED        = 'top_stocked';
    private const INTENT_INVENTORY_VALUE    = 'inventory_value';
    private const INTENT_ITEM_SEARCH        = 'item_search';
    private const INTENT_STOCK_DETAIL       = 'stock_detail';
    private const INTENT_CATEGORY_BREAKDOWN = 'category_breakdown';
    private const INTENT_PROCUREMENT        = 'procurement';
    private const INTENT_UNKNOWN            = 'unknown';

    private const STATIC_RULES = [
        self::INTENT_GREETING => [
            'phrases' => ['hello', 'hi ', 'hey', 'good morning', 'good afternoon', 'good evening', 'howdy', 'greetings'],
            'weight'  => 3,
        ],
        self::INTENT_THANKS => [
            'phrases' => ['thank', 'thanks', 'salamat', 'appreciate', 'great job', 'perfect', 'awesome', 'nice'],
            'weight'  => 3,
        ],
        self::INTENT_HELP => [
            'phrases' => ['help', 'what can you', 'commands', 'capabilities', 'what do you do', 'menu', 'options', 'features', 'how do i', 'how do you'],
            'weight'  => 3,
        ],
        self::INTENT_WHO_ARE_YOU => [
            'phrases' => ['who are you', 'what are you', 'are you ai', 'introduce yourself', 'tell me about yourself'],
            'weight'  => 3,
        ],
        self::INTENT_INVENTORY_SUMMARY => [
            'phrases' => ['inventory summary', 'inventory overview', 'inventory stats', 'inventory status', 'inventory report', 'stock overview', 'summary', 'overview', 'dashboard', 'statistics', 'stats', 'snapshot', 'at a glance'],
            'weight'  => 2,
        ],
        self::INTENT_ITEM_COUNT => [
            'phrases' => ['how many item', 'count item', 'total item', 'number of item', 'how many product'],
            'weight'  => 3,
        ],
        self::INTENT_OUT_OF_STOCK => [
            'phrases' => ['out of stock', 'zero stock', 'no stock', 'empty stock', 'depleted', 'zero quantity', 'zero qty'],
            'weight'  => 3,
        ],
        self::INTENT_LOW_STOCK => [
            'phrases' => ['low stock', 'low qty', 'low quantity', 'running low', 'restock', 'reorder', 'shortage', 'near empty', 'almost out', 'critical stock', 'need restocking'],
            'weight'  => 2,
        ],
        self::INTENT_TOP_STOCKED => [
            'phrases' => ['highest stock', 'most stock', 'top items', 'highest quantity', 'most stocked', 'well stocked', 'overstocked'],
            'weight'  => 3,
        ],
        self::INTENT_INVENTORY_VALUE => [
            'phrases' => ['total value', 'inventory value', 'worth', 'peso value', 'cost of inventory', 'monetary', 'financial value', 'valuation', 'how much is'],
            'weight'  => 3,
        ],
        self::INTENT_STOCK_DETAIL => [
            'phrases' => ['stock of', 'stock for', 'quantity of', 'qty of', 'available stock', 'on hand', 'units of', 'stock detail'],
            'weight'  => 2,
        ],
        self::INTENT_CATEGORY_BREAKDOWN => [
            'phrases' => ['by category', 'per category', 'category breakdown', 'categories', 'classify', 'group by', 'categorized', 'category list'],
            'weight'  => 2,
        ],
        self::INTENT_ITEM_SEARCH => [
            'phrases' => ['find item', 'search item', 'look for', 'locate', 'where is', 'do you have', 'item called', 'item named', 'show item', 'search for'],
            'weight'  => 2,
        ],
        self::INTENT_PROCUREMENT => [
            'phrases' => ['procurement summary', 'procurement overview', 'procurement statistics', 'procurement status', 'rfq', 'bidding', 'canvass', 'acquisition summary'],
            'weight'  => 2,
        ],
    ];

    /** @var array<string, ChatbotModule> */
    private array $dynamicModules = [];

    public function __construct()
    {
        $this->dynamicModules = cache()->remember('chatbot_modules_enabled', 60, fn () =>
            ChatbotModule::enabled()->keyBy('module_key')->all()
        );
    }

    // ────────────────────────────────────────────────────────────
    // PUBLIC ENTRY POINT
    // ────────────────────────────────────────────────────────────

    public function respond(string $message, $user, array $history = []): string
    {
        $normalized = mb_strtolower(trim($message));
        $userName   = $user->profile?->fullname ?? $user->username ?? 'there';

        // Check if this is a follow-up on a previous topic
        $followUp = $this->detectFollowUp($normalized, $history);
        if ($followUp) {
            return $this->handleFollowUp($followUp, $normalized, $userName);
        }

        $intent   = $this->classifyIntent($normalized);
        $entities = $this->extractEntities($normalized, $intent);
        $data     = $this->queryDatabase($intent, $entities);

        return $this->formatResponse($intent, $entities, $data, $userName, $normalized);
    }

    // ────────────────────────────────────────────────────────────
    // HISTORY / FOLLOW-UP DETECTION
    // ────────────────────────────────────────────────────────────

    private function detectFollowUp(string $msg, array $history): ?array
    {
        if (empty($history)) return null;

        $followUpTriggers = [
            'more', 'show more', 'give me more', 'list more', 'show all', 'see all',
            'all of them', 'load more', 'next', 'more records', 'more results',
        ];

        $isVagueFollowUp = false;
        foreach ($followUpTriggers as $t) {
            if ($msg === $t || str_starts_with($msg, $t . ' ') || str_ends_with($msg, ' ' . $t)) {
                $isVagueFollowUp = true;
                break;
            }
        }
        if (!$isVagueFollowUp) return null;

        // Find the last user message that had a real intent
        foreach (array_reverse($history) as $entry) {
            if (($entry['role'] ?? '') === 'user' && is_string($entry['content'] ?? null)) {
                $prev = mb_strtolower(trim($entry['content']));
                if (!in_array($prev, $followUpTriggers)) {
                    return ['repeat_query' => $prev, 'boost_limit' => 20];
                }
            }
        }

        return null;
    }

    private function handleFollowUp(array $ctx, string $msg, string $name): string
    {
        $prev     = $ctx['repeat_query'];
        $intent   = $this->classifyIntent($prev);
        $entities = $this->extractEntities($prev, $intent);
        $entities['limit'] = $ctx['boost_limit'];

        $data = $this->queryDatabase($intent, $entities);
        $base = $this->formatResponse($intent, $entities, $data, $name, $prev);

        return "Sure! Here's an expanded view:\n\n" . $base;
    }

    // ────────────────────────────────────────────────────────────
    // STEP 1 — INTENT CLASSIFICATION
    // ────────────────────────────────────────────────────────────

    private function classifyIntent(string $msg): string
    {
        $scores = [];

        foreach (self::STATIC_RULES as $intent => $rule) {
            $score = 0;
            foreach ($rule['phrases'] as $phrase) {
                if (str_contains($msg, $phrase)) {
                    $score += $rule['weight'];
                    if ($msg === $phrase || str_starts_with($msg, $phrase)) {
                        $score += 2;
                    }
                }
            }
            if ($score > 0) $scores[$intent] = $score;
        }

        // Dynamic modules — prefixed 'module:' to prevent collision
        foreach ($this->dynamicModules as $key => $module) {
            $score = 0;
            foreach ($module->intent_phrases as $phrase) {
                if (str_contains($msg, $phrase)) {
                    $score += $module->intent_weight;
                    if ($msg === $phrase || str_starts_with($msg, $phrase)) {
                        $score += 2;
                    }
                }
            }
            if ($score > 0) $scores['module:' . $key] = $score;
        }

        if (empty($scores)) {
            if (str_word_count($msg) <= 4 && !$this->hasQuestionWord($msg)) {
                return self::INTENT_ITEM_SEARCH;
            }
            return self::INTENT_UNKNOWN;
        }

        arsort($scores);
        return array_key_first($scores);
    }

    private function hasQuestionWord(string $msg): bool
    {
        foreach (['how', 'what', 'when', 'where', 'who', 'which', 'why', 'show', 'list', 'give'] as $w) {
            if (str_starts_with($msg, $w)) return true;
        }
        return false;
    }

    // ────────────────────────────────────────────────────────────
    // STEP 2 — ENTITY EXTRACTION
    // ────────────────────────────────────────────────────────────

    private function extractEntities(string $msg, string $intent): array
    {
        return [
            'keyword'   => $this->extractKeyword($msg, $intent),
            'limit'     => $this->extractLimit($msg),
            'threshold' => $this->extractThreshold($msg, $intent),
            'date_hint' => $this->extractDateHint($msg),
            'status'    => $this->extractStatus($msg),
        ];
    }

    private function extractKeyword(string $msg, string $intent): string
    {
        $base = [
            'show', 'me', 'the', 'a', 'an', 'all', 'list', 'find', 'search', 'get',
            'give', 'what', 'is', 'are', 'tell', 'recent', 'latest', 'last', 'how',
            'many', 'much', 'total', 'can', 'you', 'please', 'with', 'and',
            'in', 'at', 'to', 'that', 'this', 'by', 'for', 'of', 'from', 'about',
        ];

        // Module-name words safe to strip only for static analytics intents
        $moduleWords = str_starts_with($intent, 'module:') ? [] : [
            'item', 'items', 'stock', 'stocks', 'inventory', 'record', 'records',
            'low', 'high', 'current', 'today', 'receiving', 'receivings',
            'withdrawal', 'withdrawals', 'ris', 'procurement', 'ppmp',
            'supplier', 'finance', 'vendor',
        ];

        // Status words stripped only in dynamic-module context (already captured by extractStatus)
        $statusWords = str_starts_with($intent, 'module:') ? [
            'pending', 'approved', 'returned', 'submitted', 'completed',
            'cancelled', 'rejected', 'reviewed',
        ] : [];

        $stopWords = array_merge($base, $moduleWords, $statusWords);

        $cleaned = $msg;
        foreach ($stopWords as $w) {
            $cleaned = preg_replace('/\b' . preg_quote($w, '/') . '\b/i', ' ', $cleaned);
        }
        $cleaned = trim(preg_replace('/\s+/', ' ', $cleaned));

        return strlen($cleaned) > 1 ? $cleaned : '';
    }

    private function extractLimit(string $msg): int
    {
        if (preg_match('/\b(\d+)\b/', $msg, $m)) {
            $n = (int) $m[1];
            if ($n >= 1 && $n <= 100) return $n;
        }
        $map = ['five' => 5, 'ten' => 10, 'twenty' => 20, 'thirty' => 30, 'fifty' => 50];
        foreach ($map as $word => $val) {
            if (str_contains($msg, $word)) return $val;
        }
        return 10;
    }

    private function extractThreshold(string $msg, string $intent): float
    {
        if ($intent === self::INTENT_OUT_OF_STOCK) return 0;
        if (preg_match('/\b(below|under|less than|at most)\s+(\d+(?:\.\d+)?)/i', $msg, $m)) return (float) $m[2];
        if (preg_match('/\b(\d+(?:\.\d+)?)\s*(or\s*less|or\s*below)/i', $msg, $m)) return (float) $m[1];
        return 5;
    }

    private function extractDateHint(string $msg): string
    {
        foreach (['today', 'yesterday', 'this week', 'this month', 'this year', 'last week', 'last month'] as $h) {
            if (str_contains($msg, $h)) return $h;
        }
        return '';
    }

    private function extractStatus(string $msg): string
    {
        $map = [
            'pending'   => 'Pending',
            'approved'  => 'Approved',
            'returned'  => 'Returned',
            'submitted' => 'Submitted',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'rejected'  => 'Rejected',
            'reviewed'  => 'Reviewed',
            'on-hold'   => 'On-hold',
            'on hold'   => 'On-hold',
        ];
        foreach ($map as $phrase => $status) {
            if (str_contains($msg, $phrase)) return $status;
        }
        return '';
    }

    // ────────────────────────────────────────────────────────────
    // STEP 3 — DATABASE QUERIES
    // ────────────────────────────────────────────────────────────

    private function queryDatabase(string $intent, array $e): array
    {
        if (str_starts_with($intent, 'module:')) {
            $key = substr($intent, 7);
            if (isset($this->dynamicModules[$key])) {
                return $this->dbGenericModule($this->dynamicModules[$key], $e);
            }
        }

        return match ($intent) {
            self::INTENT_INVENTORY_SUMMARY  => $this->dbInventorySummary(),
            self::INTENT_ITEM_COUNT         => $this->dbItemCount(),
            self::INTENT_LOW_STOCK          => $this->dbLowStock($e['threshold']),
            self::INTENT_OUT_OF_STOCK       => $this->dbLowStock(0),
            self::INTENT_TOP_STOCKED        => $this->dbTopStocked((int) min($e['limit'], 15)),
            self::INTENT_INVENTORY_VALUE    => $this->dbInventoryValue(),
            self::INTENT_ITEM_SEARCH        => $this->dbSearchItems($e['keyword'], (int) min($e['limit'], 20)),
            self::INTENT_STOCK_DETAIL       => $this->dbStockDetail($e['keyword']),
            self::INTENT_CATEGORY_BREAKDOWN => $this->dbCategoryBreakdown(),
            self::INTENT_PROCUREMENT        => $this->dbProcurementSummary(),
            default                         => [],
        };
    }

    private function dbGenericModule(ChatbotModule $module, array $e): array
    {
        if (!Schema::hasTable($module->table_name)) return ['error' => 'not_setup', 'label' => $module->label];

        $modelClass = $module->model_class;
        if (!class_exists($modelClass) || !is_subclass_of($modelClass, \Illuminate\Database\Eloquent\Model::class)) {
            return ['error' => 'not_setup', 'label' => $module->label];
        }

        $q = $modelClass::query();

        foreach ($module->eager_loads ?? [] as $rel) {
            $q->with($rel);
        }

        if ($e['status'] && $module->status_relation) {
            $q->whereHas($module->status_relation, fn ($s) => $s->where('name', $e['status']));
        }

        $keyword    = $e['keyword'];
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
        $rows     = $q->orderByDesc($orderCol)->limit(min($e['limit'], 20))->get();

        $mapped = $rows->map(function ($row) use ($module) {
            $result = [];
            foreach ($module->display_columns as $def) {
                $key = $def['key'] ?? ($def['col'] ?? null);
                if (!$key) continue;

                $format = $def['format'] ?? null;

                if (isset($def['relation'])) {
                    $raw = $row->{$def['relation']}?->{$def['col']} ?? null;
                } else {
                    $raw = $row->{$def['col'] ?? $key} ?? null;
                }

                $result[$key] = $this->formatCellValue($raw, $format);
            }
            return $result;
        });

        return [
            'module'  => $module,
            'status'  => $e['status'],
            'keyword' => $keyword,
            'total'   => $total,
            'rows'    => $mapped->toArray(),
        ];
    }

    private function formatCellValue(mixed $value, ?string $format): string
    {
        if ($value === null || $value === '') return '—';
        return match ($format) {
            'date'  => $this->tryFormatDate($value),
            'money' => '₱' . number_format((float) $value, 2),
            default => (string) $value,
        };
    }

    private function tryFormatDate(mixed $value): string
    {
        try { return Carbon::parse($value)->format('M d, Y'); }
        catch (\Throwable) { return (string) $value; }
    }

    private function dbInventorySummary(): array
    {
        if (!Schema::hasTable('inventory_items')) return ['error' => 'not_setup'];
        $items = InventoryItem::withSum('stocks', 'quantity')->get();
        $value = Schema::hasTable('inventory_stocks')
            ? (float) InventoryStock::selectRaw('SUM(quantity * unit_cost) as total')->value('total')
            : 0;
        return [
            'total_items'   => $items->count(),
            'total_qty'     => round($items->sum('stocks_sum_quantity'), 2),
            'total_value'   => $value,
            'low_stock'     => $items->filter(fn ($i) => ($i->stocks_sum_quantity ?? 0) > 0 && ($i->stocks_sum_quantity ?? 0) <= 5)->count(),
            'out_of_stock'  => $items->filter(fn ($i) => ($i->stocks_sum_quantity ?? 0) <= 0)->count(),
            'stock_entries' => Schema::hasTable('inventory_stocks') ? InventoryStock::count() : 0,
        ];
    }

    private function dbItemCount(): array
    {
        return ['count' => Schema::hasTable('inventory_items') ? InventoryItem::count() : 0];
    }

    private function dbLowStock(float $threshold): array
    {
        if (!Schema::hasTable('inventory_items')) return ['error' => 'not_setup', 'rows' => []];
        $rows = InventoryItem::with('category')->withSum('stocks', 'quantity')->get()
            ->filter(fn ($i) => ($i->stocks_sum_quantity ?? 0) <= $threshold)
            ->sortBy('stocks_sum_quantity')->values()
            ->map(fn ($i) => [
                'code'     => $i->code,
                'name'     => $i->name,
                'category' => $i->category?->name ?? '—',
                'qty'      => round($i->stocks_sum_quantity ?? 0, 2),
            ]);
        return ['threshold' => $threshold, 'rows' => $rows->toArray()];
    }

    private function dbTopStocked(int $limit): array
    {
        if (!Schema::hasTable('inventory_items')) return ['error' => 'not_setup', 'rows' => []];
        $rows = InventoryItem::with('category')->withSum('stocks', 'quantity')
            ->get()->sortByDesc('stocks_sum_quantity')->take($limit)
            ->map(fn ($i) => [
                'code'     => $i->code,
                'name'     => $i->name,
                'category' => $i->category?->name ?? '—',
                'qty'      => round($i->stocks_sum_quantity ?? 0, 2),
            ])->values();
        return ['rows' => $rows->toArray()];
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

    private function dbSearchItems(string $keyword, int $limit): array
    {
        if (!Schema::hasTable('inventory_items')) return ['error' => 'not_setup', 'rows' => []];
        $q = InventoryItem::with('category')->withSum('stocks', 'quantity')->withCount('stocks');
        if ($keyword) {
            $q->where(fn ($query) =>
                $query->where('name', 'like', "%{$keyword}%")
                      ->orWhere('code', 'like', "%{$keyword}%")
                      ->orWhereHas('category', fn ($c) => $c->where('name', 'like', "%{$keyword}%"))
            );
        }
        $rows = $q->orderByDesc('id')->limit($limit)->get()->map(fn ($i) => [
            'code'    => $i->code,
            'name'    => $i->name,
            'category'=> $i->category?->name ?? '—',
            'qty'     => round($i->stocks_sum_quantity ?? 0, 2),
            'entries' => $i->stocks_count ?? 0,
        ]);
        return ['keyword' => $keyword, 'rows' => $rows->toArray()];
    }

    private function dbStockDetail(string $keyword): array
    {
        if (!Schema::hasTable('inventory_items') || !$keyword) return $this->dbSearchItems($keyword, 10);
        $items = InventoryItem::with(['category', 'stocks.unit'])->withSum('stocks', 'quantity')
            ->where(fn ($q) => $q->where('name', 'like', "%{$keyword}%")->orWhere('code', 'like', "%{$keyword}%"))
            ->limit(3)->get();
        $result = $items->map(function ($item) {
            $stocks = $item->stocks->map(fn ($s) => [
                'qty'       => round($s->quantity, 2),
                'unit'      => $s->unit?->name_short ?? '—',
                'unit_cost' => round($s->unit_cost, 2),
                'total_val' => round($s->quantity * $s->unit_cost, 2),
                'date'      => optional($s->created_at)->format('M d, Y') ?? '—',
                'desc'      => $s->description ?? '',
            ])->toArray();
            return [
                'code'      => $item->code,
                'name'      => $item->name,
                'category'  => $item->category?->name ?? '—',
                'total_qty' => round($item->stocks_sum_quantity ?? 0, 2),
                'stocks'    => $stocks,
            ];
        });
        return ['keyword' => $keyword, 'items' => $result->toArray()];
    }

    private function dbCategoryBreakdown(): array
    {
        if (!Schema::hasTable('inventory_items')) return ['error' => 'not_setup', 'rows' => []];
        $rows = InventoryItem::with('category')->withSum('stocks', 'quantity')->get()
            ->groupBy(fn ($i) => $i->category?->name ?? 'Uncategorized')
            ->map(fn ($g, $cat) => ['category' => $cat, 'count' => $g->count(), 'qty' => round($g->sum('stocks_sum_quantity'), 2)])
            ->sortByDesc('count')->values();
        return ['rows' => $rows->toArray()];
    }

    private function dbProcurementSummary(): array
    {
        if (!Schema::hasTable('procurements')) return ['error' => 'not_setup'];
        $total    = \App\Models\Procurement::count();
        $byStatus = \App\Models\Procurement::with('status')->get()
            ->groupBy(fn ($p) => $p->status?->name ?? 'Unknown')
            ->map->count()->sortDesc()->toArray();
        return ['total' => $total, 'by_status' => $byStatus];
    }

    // ────────────────────────────────────────────────────────────
    // STEP 4 — RESPONSE FORMATTING
    // ────────────────────────────────────────────────────────────

    private function formatResponse(string $intent, array $e, array $data, string $name, string $original): string
    {
        if (isset($data['error']) && $data['error'] === 'not_setup') {
            $label = isset($data['label']) ? " ({$data['label']})" : '';
            return "That module{$label} doesn't appear to be set up yet. Let me know if you need help with something else.";
        }

        if (str_starts_with($intent, 'module:')) {
            return $this->fmtGenericModule($data);
        }

        return match ($intent) {
            self::INTENT_GREETING           => $this->fmtGreeting($name),
            self::INTENT_THANKS             => $this->fmtThanks($name),
            self::INTENT_HELP               => $this->fmtHelp(),
            self::INTENT_WHO_ARE_YOU        => $this->fmtIdentity($name),
            self::INTENT_INVENTORY_SUMMARY  => $this->fmtInventorySummary($data),
            self::INTENT_ITEM_COUNT         => $this->fmtItemCount($data),
            self::INTENT_LOW_STOCK          => $this->fmtLowStock($data),
            self::INTENT_OUT_OF_STOCK       => $this->fmtLowStock($data),
            self::INTENT_TOP_STOCKED        => $this->fmtTopStocked($data),
            self::INTENT_INVENTORY_VALUE    => $this->fmtInventoryValue($data),
            self::INTENT_ITEM_SEARCH        => $this->fmtItemSearch($data, $original),
            self::INTENT_STOCK_DETAIL       => $this->fmtStockDetail($data),
            self::INTENT_CATEGORY_BREAKDOWN => $this->fmtCategoryBreakdown($data),
            self::INTENT_PROCUREMENT        => $this->fmtProcurementSummary($data),
            default                         => $this->fmtUnknown($original),
        };
    }

    // ── Generic module formatter ──────────────────────────────────

    private function fmtGenericModule(array $d): string
    {
        $module  = $d['module'];
        $label   = $module->label;
        $icon    = $module->icon ?? '📋';
        $status  = $d['status']  ?? '';
        $keyword = $d['keyword'] ?? '';
        $total   = $d['total']   ?? 0;
        $rows    = $d['rows']    ?? [];
        $cols    = $module->display_columns;

        if (empty($rows)) {
            $desc = $status ? "{$status} {$label}" : $label;
            if ($keyword) return "I couldn't find any {$desc} records matching **\"{$keyword}\"**. Try a different keyword or check the spelling.";
            return "There are no {$desc} records in the database right now.";
        }

        $shown  = count($rows);
        $title  = $status ? "{$status} {$label}" : $label;
        $lead   = $this->naturalLead($total, $shown, $title, $status, $keyword);

        $headers = array_column($cols, 'label');
        $tbl  = '| # | ' . implode(' | ', $headers) . " |\n";
        $tbl .= '|---|' . str_repeat('---|', count($headers)) . "\n";
        foreach ($rows as $i => $row) {
            $vals = array_map(fn ($col) => $row[$col['key']] ?? '—', $cols);
            $tbl .= '| ' . ($i + 1) . ' | ' . implode(' | ', $vals) . " |\n";
        }

        $suffix = $total > $shown ? "\n_Showing {$shown} of {$total}. Say **\"show more\"** to load more._" : '';

        return "{$lead}\n\n{$icon} **{$title}**\n\n{$tbl}{$suffix}";
    }

    private function naturalLead(int $total, int $shown, string $label, string $status, string $keyword): string
    {
        if ($keyword) return "Here's what I found for **\"{$keyword}\"** in {$label} — **{$total}** matching record(s):";
        if ($status)  return "Found **{$total}** {$status} {$label} record(s) in the system:";
        return "Here are the most recent **{$label}** records — **{$total}** total in the system:";
    }

    // ── Static formatters ─────────────────────────────────────────

    private function fmtGreeting(string $name): string
    {
        $hour = (int) now()->format('G');
        $tod  = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');

        // Live DB snapshot
        $snapshot = '';
        if (Schema::hasTable('inventory_items')) {
            $items    = InventoryItem::withSum('stocks', 'quantity')->get();
            $total    = $items->count();
            $lowStock = $items->filter(fn ($i) => ($i->stocks_sum_quantity ?? 0) > 0 && ($i->stocks_sum_quantity ?? 0) <= 5)->count();
            $outStock = $items->filter(fn ($i) => ($i->stocks_sum_quantity ?? 0) <= 0)->count();

            $snapshot = "\n\nRight now your inventory has **{$total}** items";
            if ($outStock > 0) $snapshot .= ", with **{$outStock}** out of stock";
            if ($lowStock > 0) $snapshot .= " and **{$lowStock}** running low";
            $snapshot .= ".";
        }

        return "{$tod}, **{$name}**! 👋 I'm your Procurement & Inventory assistant, connected to your live database.{$snapshot}\n\n"
             . "You can ask me anything — here are some things I can help with:\n\n"
             . "- *\"What's running low in inventory?\"*\n"
             . "- *\"Show me pending PPMP records\"*\n"
             . "- *\"How much is our total inventory worth?\"*\n"
             . "- *\"Give me the latest procurement requests\"*\n\n"
             . "What would you like to know?";
    }

    private function fmtThanks(string $name): string
    {
        $responses = [
            "You're welcome, **{$name}**! Let me know if you need anything else.",
            "Happy to help! Is there anything else you'd like to check?",
            "Glad I could help! Feel free to ask anything about your inventory or procurement.",
            "Of course! Anything else I can look up for you?",
        ];
        return $responses[array_rand($responses)];
    }

    private function fmtHelp(): string
    {
        $help = "Here's everything I can help you with:\n\n"
              . "**📦 Inventory**\n"
              . "- *\"Inventory summary\"* — full overview with totals\n"
              . "- *\"Low stock items\"* — items running low\n"
              . "- *\"Out of stock\"* — items with zero quantity\n"
              . "- *\"Top stocked items\"* — highest quantity items\n"
              . "- *\"Total inventory value\"* — peso valuation\n"
              . "- *\"Items by category\"* — grouped breakdown\n"
              . "- *\"Find [item name]\"* — search by name or code\n"
              . "- *\"Stock for [item name]\"* — detailed stock entries\n\n"
              . "**🛒 Procurement**\n"
              . "- *\"Procurement summary\"* — totals by status\n\n";

        $enabled = collect($this->dynamicModules)->values();
        if ($enabled->isNotEmpty()) {
            $help .= "**Connected modules** (toggle in Settings ⚙️)\n";
            foreach ($enabled as $module) {
                $eg   = $module->intent_phrases[0] ?? strtolower($module->label);
                $desc = $module->description ?? $module->label;
                $help .= "- *\"{$eg}\"* — {$desc}\n";
                if ($module->status_relation) {
                    $help .= "  - Add *\"pending\"*, *\"approved\"*, etc. to filter by status\n";
                }
            }
            $help .= "\n";
        }

        $help .= "💡 **Tip:** You can ask in plain language — *\"What's the value of our inventory?\"* works just as well as *\"inventory value\"*.";
        return $help;
    }

    private function fmtIdentity(string $name): string
    {
        $count = count($this->dynamicModules);
        return "I'm the **Procurement & Inventory AI Assistant** built for this system.\n\n"
             . "I'm connected directly to your live database, so every answer I give reflects your real data — no guessing.\n\n"
             . "**What makes me useful:**\n"
             . "- 🔒 All data stays on your server — nothing is sent externally\n"
             . "- ⚡ Instant answers about inventory, procurement, finance, and more\n"
             . "- 🔧 **{$count}** data module(s) connected and ready\n"
             . "- 💬 Ask in plain language — no commands needed\n\n"
             . "What can I help you with, **{$name}**?";
    }

    private function fmtInventorySummary(array $d): string
    {
        $health = $d['out_of_stock'] > 5 ? '🔴 Needs attention' : ($d['low_stock'] > 0 ? '🟡 Some items need attention' : '🟢 All good');
        $lead   = $d['out_of_stock'] > 0
            ? "Here's your current inventory status — note that **{$d['out_of_stock']}** item(s) are out of stock."
            : "Here's a snapshot of your current inventory:";

        return "{$lead}\n\n"
             . "| Metric | Value |\n|---|---|\n"
             . "| Total Items | **{$d['total_items']}** |\n"
             . "| Total Qty on Hand | **" . number_format($d['total_qty'], 2) . "** |\n"
             . "| Stock Entries | **{$d['stock_entries']}** |\n"
             . "| Total Value | **₱" . number_format($d['total_value'], 2) . "** |\n"
             . "| 🟡 Low Stock | **{$d['low_stock']}** items |\n"
             . "| 🔴 Out of Stock | **{$d['out_of_stock']}** items |\n\n"
             . "**Overall health:** {$health}";
    }

    private function fmtItemCount(array $d): string
    {
        $n = $d['count'];
        return "You currently have **{$n} inventory item" . ($n !== 1 ? 's' : '') . "** tracked in the system.";
    }

    private function fmtLowStock(array $d): string
    {
        $threshold = $d['threshold'];
        $rows      = $d['rows'] ?? [];

        if (empty($rows)) {
            return $threshold <= 0
                ? "Great news — no items are completely out of stock right now. ✅"
                : "All items currently have more than **{$threshold}** units on hand. Stock levels look healthy! ✅";
        }

        $count  = count($rows);
        $header = $threshold <= 0 ? '🔴 Out-of-Stock Items' : "🟡 Low Stock Items (qty ≤ {$threshold})";
        $lead   = $threshold <= 0
            ? "I found **{$count}** item(s) that are completely out of stock and need restocking:"
            : "I found **{$count}** item(s) running low (≤ {$threshold} units). Consider restocking these:";

        $tbl = "| Code | Item Name | Category | Qty |\n|---|---|---|---|\n";
        foreach ($rows as $r) {
            $tbl .= '| `' . $r['code'] . '` | ' . $r['name'] . ' | ' . $r['category'] . ' | **' . $r['qty'] . "** |\n";
        }

        return "{$lead}\n\n{$header}\n\n{$tbl}";
    }

    private function fmtTopStocked(array $d): string
    {
        $rows = $d['rows'] ?? [];
        if (empty($rows)) return "No stock data found in the database.";
        $lead = "Here are your **" . count($rows) . " most-stocked items**:";
        $tbl  = "| Code | Item Name | Category | Qty |\n|---|---|---|---|\n";
        foreach ($rows as $r) {
            $tbl .= '| `' . $r['code'] . '` | ' . $r['name'] . ' | ' . $r['category'] . ' | **' . $r['qty'] . "** |\n";
        }
        return "{$lead}\n\n{$tbl}";
    }

    private function fmtInventoryValue(array $d): string
    {
        $formatted = '₱' . number_format($d['total_value'], 2);
        return "Your total inventory is currently valued at **{$formatted}**.\n\n"
             . "| Metric | Value |\n|---|---|\n"
             . "| **Total Value** | **{$formatted}** |\n"
             . "| Total Units | " . number_format($d['total_qty'], 2) . " |\n"
             . "| Stock Entries | {$d['stock_count']} |\n\n"
             . "_Calculated as quantity × unit cost across all stock entries._";
    }

    private function fmtItemSearch(array $d, string $original): string
    {
        $rows    = $d['rows'] ?? [];
        $keyword = $d['keyword'] ?? '';

        if (empty($rows)) {
            if ($keyword) return "I couldn't find any items matching **\"{$keyword}\"**. Try a different keyword, or ask for the *inventory summary* to see what's available.";
            return "No inventory items found in the database.";
        }

        $count = count($rows);
        $lead  = $keyword
            ? "Found **{$count}** item(s) matching **\"{$keyword}\"**:"
            : "Here are the most recent inventory items:";

        $tbl = "| Code | Item Name | Category | Qty on Hand | Entries |\n|---|---|---|---|---|\n";
        foreach ($rows as $r) {
            $tbl .= '| `' . $r['code'] . '` | ' . $r['name'] . ' | ' . $r['category'] . ' | ' . $r['qty'] . ' | ' . $r['entries'] . " |\n";
        }

        return "{$lead}\n\n{$tbl}";
    }

    private function fmtStockDetail(array $d): string
    {
        if (isset($d['rows'])) return $this->fmtItemSearch($d, '');

        $items   = $d['items'] ?? [];
        $keyword = $d['keyword'] ?? '';

        if (empty($items)) {
            return "I couldn't find an item named **\"{$keyword}\"**. Try *\"find {$keyword}\"* to search more broadly.";
        }

        $out = "Here's the detailed stock breakdown" . ($keyword ? " for **\"{$keyword}\"**" : '') . ":\n\n";

        foreach ($items as $item) {
            $out .= "### " . $item['name'] . " `" . $item['code'] . "`\n";
            $out .= "**Category:** " . $item['category'] . " · **Total on hand: " . $item['total_qty'] . "**\n\n";

            if (empty($item['stocks'])) {
                $out .= "_No stock entries yet._\n\n";
            } else {
                $out .= "| # | Qty | Unit | Unit Cost | Total Value | Date |\n|---|---|---|---|---|---|\n";
                foreach ($item['stocks'] as $i => $s) {
                    $out .= '| ' . ($i + 1) . ' | **' . $s['qty'] . '** | ' . $s['unit']
                          . ' | ₱' . number_format($s['unit_cost'], 2)
                          . ' | ₱' . number_format($s['total_val'], 2)
                          . ' | ' . $s['date'] . " |\n";
                }
                $out .= "\n\n";
            }
        }

        return rtrim($out);
    }

    private function fmtCategoryBreakdown(array $d): string
    {
        $rows = $d['rows'] ?? [];
        if (empty($rows)) return "No categorized items found in the database.";

        $total = array_sum(array_column($rows, 'count'));
        $lead  = "Your inventory is spread across **" . count($rows) . " categories** (total: **{$total}** items):";
        $tbl   = "| Category | Items | Total Qty |\n|---|---|---|\n";
        foreach ($rows as $r) {
            $pct  = $total > 0 ? round($r['count'] / $total * 100) : 0;
            $tbl .= '| ' . $r['category'] . ' | ' . $r['count'] . " ({$pct}%) | " . number_format($r['qty'], 2) . " |\n";
        }
        return "{$lead}\n\n{$tbl}";
    }

    private function fmtProcurementSummary(array $d): string
    {
        if (empty($d['total'])) return "No procurement records found in the database yet.";
        $total = $d['total'];
        $lead  = "There are **{$total}** procurement record(s) in the system, broken down by status:";
        $tbl   = "| Status | Count |\n|---|---|\n";
        foreach (($d['by_status'] ?? []) as $status => $count) {
            $tbl .= "| {$status} | **{$count}** |\n";
        }
        return "{$lead}\n\n{$tbl}";
    }

    private function fmtUnknown(string $original): string
    {
        // Last-resort: try a broad item search
        if (strlen($original) > 2 && str_word_count($original) <= 5) {
            $data = $this->dbSearchItems($original, 10);
            if (!empty($data['rows'])) {
                return "I found some inventory items that might be relevant:\n\n" . $this->fmtItemSearch($data, $original);
            }
        }

        return "I'm not sure I understood that. Here are some things you can ask:\n\n"
             . "- *\"Inventory summary\"* — full overview\n"
             . "- *\"Low stock items\"* — items running low\n"
             . "- *\"Show pending PPMP\"* — PPMP by status\n"
             . "- *\"Find [item name]\"* — search for a specific item\n"
             . "- *\"Help\"* — see everything I can do\n\n"
             . "What were you looking for?";
    }
}
