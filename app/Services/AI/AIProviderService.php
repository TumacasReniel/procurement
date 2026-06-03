<?php

namespace App\Services\AI;

use App\Models\ChatbotSetting;
use Illuminate\Support\Facades\Http;

class AIProviderService
{
    public function __construct(private ChatbotSetting $settings) {}

    public function chat(string $system, array $messages, array $tools): array
    {
        $key = $this->settings->api_key;
        if (!$key) {
            throw new \RuntimeException('No API key configured. Add your key in AI Settings.');
        }

        $payload = [['role' => 'system', 'content' => $system]];

        foreach ($messages as $m) {
            if (is_string($m['content'])) {
                $payload[] = ['role' => $m['role'], 'content' => $m['content']];
            } elseif (is_array($m['content'])) {
                foreach ($m['content'] as $block) {
                    if (($block['type'] ?? '') === 'tool_result') {
                        $payload[] = [
                            'role'         => 'tool',
                            'tool_call_id' => $block['tool_use_id'],
                            'content'      => $block['content'],
                        ];
                    }
                }
            }
        }

        $body = [
            'model'      => $this->settings->model ?: $this->defaultModel(),
            'max_tokens' => $this->settings->max_tokens ?: 1500,
            'messages'   => $payload,
        ];

        if ($tools) {
            $body['tools'] = array_map(fn ($t) => [
                'type'     => 'function',
                'function' => [
                    'name'        => $t['name'],
                    'description' => $t['description'],
                    'parameters'  => $t['input_schema'],
                ],
            ], $tools);
            $body['tool_choice'] = 'auto';
        }

        $resp = Http::timeout(60)
            ->withToken($key)
            ->post($this->apiUrl(), $body);

        if (!$resp->successful()) {
            $error = $resp->json('error.message') ?? $resp->body();
            throw new \RuntimeException(ucfirst($this->settings->provider) . ' error: ' . $error);
        }

        return $this->normalise($resp->json());
    }

    public function supportsTools(): bool
    {
        return true;
    }

    public function testConnection(): array
    {
        try {
            $result = $this->chat('Reply with exactly: Connection successful.', [
                ['role' => 'user', 'content' => 'ping'],
            ], []);
            $text = collect($result['content'])->where('type', 'text')->pluck('text')->first() ?? '';
            return ['ok' => true, 'message' => $text ?: 'Connection successful.'];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    private function apiUrl(): string
    {
        // Explicit base_url in settings overrides everything (useful for Ollama / proxies)
        if ($this->settings->base_url) {
            return rtrim($this->settings->base_url, '/');
        }

        $providers = ChatbotSetting::providers();
        return $providers[$this->settings->provider]['api_url']
            ?? 'https://api.openai.com/v1/chat/completions';
    }

    private function defaultModel(): string
    {
        $providers = ChatbotSetting::providers();
        $models    = $providers[$this->settings->provider]['models'] ?? [];
        return array_key_first($models) ?? 'gpt-4o-mini';
    }

    private function normalise(array $raw): array
    {
        $choice  = $raw['choices'][0] ?? [];
        $message = $choice['message'] ?? [];
        $content = [];

        if (!empty($message['content'])) {
            $content[] = ['type' => 'text', 'text' => $message['content']];
        }

        foreach ($message['tool_calls'] ?? [] as $tc) {
            $content[] = [
                'type'  => 'tool_use',
                'id'    => $tc['id'],
                'name'  => $tc['function']['name'],
                'input' => json_decode($tc['function']['arguments'], true) ?? [],
            ];
        }

        return [
            'stop_reason' => ($choice['finish_reason'] ?? '') === 'tool_calls' ? 'tool_use' : 'end_turn',
            'content'     => $content,
        ];
    }
}
