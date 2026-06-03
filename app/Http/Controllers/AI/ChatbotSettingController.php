<?php

namespace App\Http\Controllers\AI;

use App\Http\Controllers\Controller;
use App\Models\ChatbotModule;
use App\Models\ChatbotSetting;
use App\Services\AI\AIProviderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatbotSettingController extends Controller
{
    public function show(): JsonResponse
    {
        $setting   = ChatbotSetting::current();
        $providers = ChatbotSetting::providers();

        return response()->json([
            'provider'   => $setting->provider,
            'model'      => $setting->model,
            'has_key'    => !empty($setting->api_key),
            'base_url'   => $setting->base_url,
            'max_tokens' => $setting->max_tokens,
            'is_active'  => $setting->is_active,
            'providers'  => $providers,
        ]);
    }

    public function save(Request $request): JsonResponse
    {
        $request->validate([
            'provider'   => ['required', 'in:openai,groq,openrouter,ollama'],
            'model'      => ['required', 'string', 'max:100'],
            'api_key'    => ['nullable', 'string', 'max:500'],
            'base_url'   => ['nullable', 'url', 'max:255'],
            'max_tokens' => ['nullable', 'integer', 'min:256', 'max:8000'],
        ]);

        $setting = ChatbotSetting::current();

        $data = [
            'provider'   => $request->provider,
            'model'      => $request->model,
            'base_url'   => $request->base_url ?: null,
            'max_tokens' => $request->max_tokens ?? 1500,
        ];

        // Only update key if a new one was provided
        if ($request->filled('api_key')) {
            $data['api_key'] = $request->api_key;
        }

        $setting->update($data);

        return response()->json(['ok' => true, 'message' => 'Settings saved successfully.']);
    }

    public function test(): JsonResponse
    {
        $setting  = ChatbotSetting::current();
        $service  = new AIProviderService($setting);
        $result   = $service->testConnection();

        return response()->json($result);
    }

    public function clearKey(): JsonResponse
    {
        ChatbotSetting::current()->update(['api_key' => null]);
        return response()->json(['ok' => true]);
    }

    public function modules(): JsonResponse
    {
        $modules = ChatbotModule::all_modules()->map(fn ($m) => [
            'module_key'  => $m->module_key,
            'label'       => $m->label,
            'description' => $m->description,
            'icon'        => $m->icon,
            'table_name'  => $m->table_name,
            'is_enabled'  => $m->is_enabled,
        ]);

        return response()->json(['modules' => $modules]);
    }

    public function toggleModule(Request $request, string $key): JsonResponse
    {
        $module = ChatbotModule::where('module_key', $key)->first();

        if (!$module) {
            return response()->json(['ok' => false, 'message' => 'Module not found.'], 404);
        }

        $module->update(['is_enabled' => (bool) $request->boolean('is_enabled')]);

        cache()->forget('chatbot_modules_enabled');

        return response()->json([
            'ok'         => true,
            'module_key' => $module->module_key,
            'is_enabled' => $module->is_enabled,
        ]);
    }
}
