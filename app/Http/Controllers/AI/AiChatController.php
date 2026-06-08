<?php

namespace App\Http\Controllers\AI;

use App\Http\Controllers\Controller;
use App\Services\AI\FastApiChatbotClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AiChatController extends Controller
{
    public function __construct(private FastApiChatbotClient $client) {}

    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'messages' => ['required', 'array', 'max:40'],
            'messages.*.role' => ['required', 'in:user,assistant'],
            'messages.*.content' => ['required', 'string'],
        ]);

        $message = $this->lastUserMessage($request->input('messages', []));
        if ($this->isGreeting($message)) {
            $answer = $this->greetingAnswer($message, $request->user());

            return response()->json([
                'answer' => $answer,
                'reply' => $answer,
                'sql' => null,
                'columns' => [],
                'rows' => [],
                'table' => [],
                'suggestions' => [
                    'What can you do?',
                    'Help me with OneApp',
                    'Help me write this',
                    'Explain something',
                    'Show pending PPMP',
                ],
                'meta' => ['source' => 'greeting'],
            ]);
        }

        try {
            $result = $this->client->chat($request->input('messages'), $request->user());
            $answer = $this->humanizeAnswer($result['answer'] ?? $result['reply'] ?? 'I could not generate a response.');
            $columns = $result['columns'] ?? [];
            $rows = $result['rows'] ?? ($result['table'] ?? []);
            $suggestions = $result['suggestions'] ?? [];

            return response()->json([
                'answer' => $answer,
                'reply' => trim($answer . "\n\n" . $this->markdownTable($columns, $rows)),
                'table' => $rows,
                'sql' => $result['sql'] ?? null,
                'columns' => $columns,
                'rows' => $rows,
                'suggestions' => $suggestions,
                'meta' => $result['meta'] ?? null,
            ]);
        } catch (\Throwable $e) {
            report($e);

            Log::warning('OneApp Chatbot request failed.', [
                'user_id' => $request->user()?->id,
                'message' => $e->getMessage(),
            ]);

            $message = $this->userFacingError($e);

            return response()->json([
                'answer' => $message,
                'reply' => $message,
                'sql' => null,
                'columns' => [],
                'rows' => [],
                'table' => [],
                'suggestions' => [],
            ]);
        }
    }

    private function lastUserMessage(array $messages): string
    {
        for ($index = count($messages) - 1; $index >= 0; $index--) {
            $message = $messages[$index] ?? [];
            if (($message['role'] ?? null) === 'user') {
                return trim((string) ($message['content'] ?? ''));
            }
        }

        return '';
    }

    private function isGreeting(string $message): bool
    {
        $normalized = strtolower(trim(preg_replace('/[^\pL\pN\s\']+/u', ' ', $message) ?? ''));
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? '';
        if ($normalized === '') {
            return false;
        }

        $matchesGreeting = preg_match('/^(hi|hello|hey|yo|sup|good morning|good afternoon|good evening|good day)\b/u', $normalized)
            || preg_match('/^(kamusta|kumusta|musta)\b/u', $normalized)
            || str_contains($normalized, 'how are you');

        if (! $matchesGreeting) {
            return false;
        }

        $words = array_filter(explode(' ', $normalized));
        if (count($words) > 8) {
            return false;
        }

        $questionWords = ['what', 'where', 'when', 'why', 'which', 'who', 'show', 'list', 'count', 'find', 'search', 'create', 'approve', 'explain'];

        return empty(array_intersect($words, $questionWords));
    }

    private function greetingAnswer(string $message, mixed $user): string
    {
        $normalized = strtolower($message);
        $name = $this->firstName($user?->profile?->fullname ?? $user?->name ?? $user?->username ?? null);
        $prefix = str_contains($normalized, 'morning')
            ? 'Good morning'
            : (str_contains($normalized, 'afternoon')
                ? 'Good afternoon'
                : (str_contains($normalized, 'evening') ? 'Good evening' : 'Hi'));

        if ($name) {
            $prefix .= ', ' . $name;
        }

        if (str_contains($normalized, 'how are you')) {
            return $prefix . ". I'm doing well. What are we working on today?";
        }

        return $prefix . '. What can I help you with today?';
    }

    private function firstName(?string $name): ?string
    {
        $name = trim((string) $name);
        if ($name === '') {
            return null;
        }

        return explode(' ', $name)[0] ?: null;
    }

    private function humanizeAnswer(string $answer): string
    {
        $answer = trim($answer);
        if ($answer === '') {
            return 'I could not generate a response.';
        }

        $replacements = [
            '/^\*\*(.*?)\*\* is the best match for that question\.\s*/mi' => '',
            '/^You’ll usually handle this in \*\*(.*?)\*\*\.\s*/mi' => '',
            '/^Tell me the exact record code, page, or status if you want me to narrow it down\.$/mi' => 'Send me the record code, page, or status if you want me to narrow it down.',
            '/^\*\*Page to check:\*\*\s*(.*?)\.\s*$/mi' => "You’ll find this under **$1**.",
            '/^Page to check:\s*(.*?)\.\s*$/mi' => "You’ll find this under **$1**.",
            '/^\*\*Typical workflow:\*\*\s*/mi' => "The usual flow is: ",
            '/^\*\*Useful actions:\*\*\s*/mi' => "You can ",
            '/^\*\*Important fields:\*\*\s*/mi' => "Fields to watch: ",
            '/^\*\*Related areas:\*\*.*$/mi' => '',
            '/authorized record\(s\)/i' => 'results',
            '/authorized records/i' => 'results',
            '/authorized record/i' => 'result',
            '/record\(s\)/i' => 'results',
            '/item\(s\)/i' => 'items',
            '/result\(s\)/i' => 'results',
            '/^The authorized total is \*\*(.*?)\*\*\.$/mi' => 'I found **$1**.',
            '/^I found \*\*(\d+)\*\* pending results?\.? Review the table below\.$/mi' => 'I found **$1** pending results. I placed the details in the table below.',
            '/^I found \*\*(\d+)\*\* approved results?\.? Review the table below\.$/mi' => 'I found **$1** approved results. I placed the details in the table below.',
            '/^I found \*\*(\d+)\*\* low-stock items?\.? Review the table below\.$/mi' => 'I found **$1** low-stock items. I placed the details in the table below.',
            '/^I found \*\*(\d+)\*\* results? matching your request\. Review the table below\.$/mi' => 'I found **$1** results. I placed the details in the table below.',
            '/^\*\*Status summary:\*\*\s*/mi' => 'Here’s the status breakdown: ',
        ];

        foreach ($replacements as $pattern => $replacement) {
            $answer = preg_replace($pattern, $replacement, $answer) ?? $answer;
        }

        $answer = preg_replace("/\n{3,}/", "\n\n", $answer) ?? $answer;

        return trim($answer);
    }

    private function markdownTable(array $columns, array $rows): string
    {
        if (!$columns || !$rows) {
            return '';
        }

        $table = '| ' . implode(' | ', $columns) . " |\n";
        $table .= '|' . str_repeat('---|', count($columns)) . "\n";

        foreach ($rows as $row) {
            $values = collect($columns)
                ->map(fn ($column) => str_replace('|', '\\|', (string) ($row[$column] ?? '-')))
                ->implode(' | ');

            $table .= '| ' . $values . " |\n";
        }

        return $table;
    }

    private function userFacingError(\Throwable $e): string
    {
        $message = $e->getMessage();

        if (str_contains($message, 'cURL error 7') || str_contains($message, 'Could not connect')) {
            return 'OneApp Chatbot is not running yet. Start the FastAPI service on http://127.0.0.1:8010, then try again.';
        }

        return 'OneApp Chatbot is online, but could not process that request yet. Please try again or contact support if the issue continues.';
    }
}
