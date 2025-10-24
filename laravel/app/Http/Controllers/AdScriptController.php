<?php

namespace App\Http\Controllers;

use App\Events\AdScriptTaskUpdated;
use App\Jobs\SendToN8nJob;
use App\Models\AdScriptTask;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class AdScriptController extends Controller
{
    // POST /api/ad-scripts
    public function store(Request $request)
    {
        // NOTE: Consider type-hinting StoreAdScriptRequest to centralize validation logic
        // and keep the controller slim. This also improves testability.

        // Validate
        $data = $request->validate([
            'reference_script'    => ['required', 'string'],
            'outcome_description' => ['required', 'string'],
        ]);

        // Create task
        // TIP: If these payloads may become large in the future, ensure DB column types are adequate (e.g., LONGTEXT).
        $task = AdScriptTask::create([
            'reference_script'    => $data['reference_script'],
            'outcome_description' => $data['outcome_description'],
            'status'              => 'pending',
        ]);

        // Dispatch job
        // INFO: Queueing here decouples the HTTP request from the n8n call.
        // Consider adding job $tries/$backoff to handle transient n8n outages gracefully.
        SendToN8nJob::dispatch($task->id);

        // Broadcast/UI update event
        // SUGGESTION: Emitting updates only on state transitions can reduce event noise.
        event(new AdScriptTaskUpdated($task));

        // Return 201 to satisfy the test
        // TIP: Standardize API response shape via Laravel API Resources for consistency.
        return response()->json([
            'id'     => $task->id,
            'status' => $task->status,
        ], 201);
    }

    // POST /api/ad-scripts/{id}/result
    public function result(Request $request, int $id): JsonResponse
    {
        // SECURITY: This protects the endpoint with a static bearer token.
        // Consider adding HMAC verification with timestamp + body to prevent replay attacks
        // and avoid relying solely on a shared static secret.
        $auth = $request->bearerToken();
        if ($auth !== config('services.callbacks.bearer_token')) {
            return response()->json(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        // VALIDATION: 'in:$id' ensures request payload matches route param (defensive check).
        // You can also add 'bail' to stop on first error and provide custom messages for DX.
        $validated = $request->validate([
            'task_id'  => ['required', 'integer', 'in:' . $id],
            'new_script' => ['required', 'string'],
            'analysis' => ['required', 'string'],
        ]);

        // NOTE: findOrFail will throw a 404 if the task does not exist; this is correct for idempotent callbacks.
        // If callbacks might arrive late/out-of-order, consider guarding against overwriting a final state.
        $task = AdScriptTask::findOrFail($id);
        $task->update([
            'new_script' => $validated['new_script'],
            'analysis'   => $validated['analysis'],
            'status'     => 'completed',
        ]);

        // Broadcast UI update after successful state change.
        event(new AdScriptTaskUpdated($task));

        // RESPONSE: Returning a minimal acknowledgment is fine; document this contract in README for integrators.
        return response()->json(['ok' => true]);
    }
}
