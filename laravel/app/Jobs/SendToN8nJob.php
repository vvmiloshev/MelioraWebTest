<?php

namespace App\Jobs;

use App\Models\AdScriptTask;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendToN8nJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $taskId;

    public function __construct(int $taskId)
    {
        $this->taskId = $taskId;
    }

    public function handle(): void
    {
        $task = AdScriptTask::find($this->taskId);
        if (! $task) {
            return;
        }

        $base  = config('services.n8n.base_url') ?? env('N8N_BASE_URL', '');
        $path  = config('services.n8n.webhook_path') ?? env('N8N_WEBHOOK_PATH', 'ad-script-agent');
        $token = config('services.n8n.token') ?? env('N8N_API_TOKEN');

        $url = rtrim($base, '/').'/'.ltrim($path, '/');

        $payload = [
            'task_id'             => $task->id,
            'reference_script'    => $task->reference_script,
            'outcome_description' => $task->outcome_description,
        ];

        try {
            $request = Http::timeout(10);
            if ($token) {
                $request = $request->withToken($token);
            }
            Log::info('N8N URL', ['url' => $url]);
            $response = $request->post($url, $payload);

            Log::info('N8N RESPONSE', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);


            $status = $response->status();
            if ($status >= 400) {
                $this->markFailed($task, 'HTTP '.$status.': '.$response->body());
                return; // за да не паднем в LOG "done" с pending
            }


        } catch (\Throwable $e) {
            $this->markFailed($task, $e->getMessage());
            return;
        }

        Log::info('JOB DONE OK', ['task_id' => $task->id]);
    }

    private function markFailed(AdScriptTask $task, string $details): void
    {
        $task->update([
            'status'        => 'failed',
            'error_details' => $details,
        ]);
        Log::info('JOB MARKED FAILED', [
            'task_id' => $task->id,
            'status'  => 'failed',
        ]);
    }

    public function failed(\Throwable $e): void
    {
        if ($task = AdScriptTask::find($this->taskId)) {
            $task->update([
                'status'        => 'failed',
                'error_details' => $e->getMessage(),
            ]);
        }
    }
}
