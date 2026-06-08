<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatbotSetting extends Model
{
    protected $fillable = ['provider', 'model', 'api_key', 'base_url', 'max_tokens', 'is_active'];

    protected $casts = [
        'max_tokens' => 'integer',
        'is_active'  => 'boolean',
        'api_key'    => 'encrypted',
    ];

    public static function current(): self
    {
        return static::firstOrCreate([], [
            'provider'   => 'fastapi',
            'model'      => 'procurement-db-assistant',
            'max_tokens' => 1500,
            'is_active'  => true,
        ]);
    }

    public static function providers(): array
    {
        return [
            'fastapi' => [
                'name'    => 'OneApp Chatbot',
                'api_url' => config('services.procurement_ai.base_url'),
                'key_url' => null,
                'models'  => [
                    'qwen3' => 'Qwen3 via local Ollama',
                ],
            ],
            'openai' => [
                'name'    => 'OpenAI',
                'api_url' => 'https://api.openai.com/v1/chat/completions',
                'key_url' => 'https://platform.openai.com/api-keys',
                'models'  => [
                    'gpt-4o-mini'    => 'GPT-4o Mini — fast & affordable (recommended)',
                    'gpt-4o'         => 'GPT-4o — most capable',
                    'gpt-3.5-turbo'  => 'GPT-3.5 Turbo — cheapest',
                ],
            ],
            'groq' => [
                'name'    => 'Groq (Free)',
                'api_url' => 'https://api.groq.com/openai/v1/chat/completions',
                'key_url' => 'https://console.groq.com/keys',
                'models'  => [
                    'llama-3.3-70b-versatile' => 'Llama 3.3 70B — smartest (GPT-4 level)',
                    'llama-3.1-8b-instant'    => 'Llama 3.1 8B — fastest',
                    'gemma2-9b-it'            => 'Gemma 2 9B — by Google',
                ],
            ],
            'openrouter' => [
                'name'    => 'OpenRouter (Free models available)',
                'api_url' => 'https://openrouter.ai/api/v1/chat/completions',
                'key_url' => 'https://openrouter.ai/keys',
                'models'  => [
                    'meta-llama/llama-3.3-70b-instruct:free' => 'Llama 3.3 70B — free tier',
                    'google/gemini-2.0-flash-exp:free'       => 'Gemini 2.0 Flash — free tier',
                    'mistralai/mistral-7b-instruct:free'     => 'Mistral 7B — free tier',
                ],
            ],
        ];
    }
}
