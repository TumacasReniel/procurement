<?php

namespace App\Services\AI;

use App\Models\ChatbotModule;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class FastApiChatbotClient
{
    public function chat(array $messages, User $user): array
    {
        $this->ensureServiceIsRunning();

        return $this->post('/chat', [
            'messages' => $messages,
            'user' => $this->userContext($user),
            'modules' => $this->moduleContext(),
        ]);
    }

    public function health(): array
    {
        $this->ensureServiceIsRunning();

        try {
            $response = $this->http()->get($this->url('/health'));

            return $response->successful()
                ? ['ok' => true, 'message' => $response->json('message', 'FastAPI service is reachable.')]
                : ['ok' => false, 'message' => $response->body()];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    private function ensureServiceIsRunning(): void
    {
        if (!config('services.procurement_ai.auto_start', true)) {
            return;
        }

        if ($this->isHealthy()) {
            return;
        }

        $this->startService();

        for ($i = 0; $i < 10; $i++) {
            usleep(300000);

            if ($this->isHealthy()) {
                return;
            }
        }
    }

    private function isHealthy(): bool
    {
        try {
            return $this->http()
                ->timeout(2)
                ->get($this->url('/health'))
                ->successful();
        } catch (\Throwable) {
            return false;
        }
    }

    private function startService(): void
    {
        $command = trim((string) config('services.procurement_ai.start_command'));
        $workingDir = (string) config('services.procurement_ai.working_dir');

        if ($command === '') {
            return;
        }

        if (str_starts_with(strtolower(PHP_OS_FAMILY), 'windows')) {
            $this->startWindowsProcess($command, $workingDir);
            return;
        }

        $this->startUnixProcess($command, $workingDir);
    }

    private function startWindowsProcess(string $command, string $workingDir): void
    {
        $commandPath = $this->resolveWindowsCommand($command, $workingDir);
        $escapedCommand = str_replace("'", "''", $commandPath);
        $escapedWorkingDir = str_replace("'", "''", $workingDir);
        $powershell = "Start-Process -FilePath 'cmd.exe' -ArgumentList '/c \"{$escapedCommand}\"' -WorkingDirectory '{$escapedWorkingDir}' -WindowStyle Hidden";

        $this->runBackgroundCommand('powershell -NoProfile -ExecutionPolicy Bypass -Command ' . escapeshellarg($powershell));
    }

    private function resolveWindowsCommand(string $command, string $workingDir): string
    {
        if (preg_match('/^[A-Za-z]:[\\\\\\/]/', $command)) {
            return $command;
        }

        $candidate = rtrim($workingDir, '\\/') . DIRECTORY_SEPARATOR . $command;

        return file_exists($candidate) ? $candidate : $command;
    }

    private function startUnixProcess(string $command, string $workingDir): void
    {
        $shell = 'cd ' . escapeshellarg($workingDir) . ' && nohup ' . $command . ' > /dev/null 2>&1 &';

        $this->runBackgroundCommand($shell);
    }

    private function runBackgroundCommand(string $command): void
    {
        try {
            $descriptors = [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ];

            $process = proc_open($command, $descriptors, $pipes);

            if (is_resource($process)) {
                foreach ($pipes as $pipe) {
                    fclose($pipe);
                }

                proc_close($process);
            }
        } catch (\Throwable $e) {
            Log::warning('Unable to auto-start Procurement AI service.', [
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function post(string $path, array $payload): array
    {
        $response = $this->http()->post($this->url($path), $payload);

        if (!$response->successful()) {
            $detail = $response->json('detail') ?? $response->json('message') ?? $response->body();
            $message = is_array($detail)
                ? ($detail['message'] ?? json_encode($detail, JSON_UNESCAPED_SLASHES))
                : (string) $detail;
            throw new \RuntimeException('Procurement AI service error: ' . $message);
        }

        return $response->json();
    }

    private function http(): \Illuminate\Http\Client\PendingRequest
    {
        $request = Http::timeout((int) config('services.procurement_ai.timeout', 90))
            ->acceptJson()
            ->asJson();

        if ($key = config('services.procurement_ai.api_key')) {
            $request->withToken($key);
        }

        return $request;
    }

    private function url(string $path): string
    {
        return rtrim((string) config('services.procurement_ai.base_url'), '/') . '/' . ltrim($path, '/');
    }

    private function userContext(User $user): array
    {
        $user->loadMissing(['profile', 'organization', 'roles']);

        return [
            'id' => $user->id,
            'user_id' => $user->id,
            'name' => $user->profile?->fullname ?? $user->username,
            'full_name' => $user->profile?->fullname ?? $user->username,
            'role' => $this->primaryRole($user),
            'roles' => $user->roles
                ->pluck('name')
                ->filter()
                ->values()
                ->all(),
            'permissions' => $this->permissions($user),
            'office_id' => $user->organization?->unit_id ?? $user->organization?->division_id,
            'office_name' => $user->organization?->name,
            'division_id' => $user->organization?->division_id,
            'unit_id' => $user->organization?->unit_id,
        ];
    }

    private function permissions(User $user): array
    {
        if (method_exists($user, 'getAllPermissions')) {
            return $user->getAllPermissions()
                ->pluck('name')
                ->filter()
                ->values()
                ->all();
        }

        return $user->roles
            ->pluck('name')
            ->filter()
            ->values()
            ->all();
    }

    private function primaryRole(User $user): ?string
    {
        return $user->roles
            ->pluck('name')
            ->first(fn (?string $role) => filled($role));
    }

    private function moduleContext(): array
    {
        if (!Schema::hasTable('chatbot_modules')) {
            return [];
        }

        return ChatbotModule::enabled()
            ->map(fn (ChatbotModule $module) => [
                'module_key' => $module->module_key,
                'label' => $module->label,
                'description' => $module->description,
                'table_name' => $this->aiViewName($module),
                'intent_phrases' => $module->intent_phrases ?? [],
                'intent_weight' => $module->intent_weight ?? 1,
                'display_columns' => $module->display_columns ?? [],
                'searchable_columns' => $module->searchable_columns ?? [],
                'order_column' => $module->order_column,
            ])
            ->values()
            ->all();
    }

    private function aiViewName(ChatbotModule $module): string
    {
        return match ($module->module_key) {
            'ppmp' => 'ai_ppmp_view',
            'procurement_app' => 'ai_app_view',
            'procurement_requests' => 'ai_procurement_request_view',
            'procurement_po' => 'ai_po_view',
            'procurement_bac' => 'ai_bac_view',
            'inventory_receivings' => 'ai_receivings_view',
            'inventory_withdrawals' => 'ai_withdrawals_view',
            'inventory_ris' => 'ai_withdrawals_view',
            default => str_starts_with($module->table_name, 'ai_')
                ? $module->table_name
                : 'ai_' . preg_replace('/^(procurement_|inventory_)/', '', $module->table_name) . '_view',
        };
    }
}
