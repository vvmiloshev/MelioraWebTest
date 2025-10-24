<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAdScriptRequest;
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
        // Validate
        $data = $request->validate([
            'reference_script'    => ['required', 'string'],
            'outcome_description' => ['required', 'string'],
        ]);

        // Create task
        $task = AdScriptTask::create([
            'reference_script'    => $data['reference_script'],
            'outcome_description' => $data['outcome_description'],
            'status'              => 'pending',
        ]);

        // Dispatch job
        SendToN8nJob::dispatch($task->id);

        // Return 201 to satisfy the test
        return response()->json([
            'id'     => $task->id,
            'status' => $task->status,
        ], 201);
    }

    // POST /api/ad-scripts/{id}/result
    public function result(Request $request, int $id): JsonResponse
    {
        $auth = $request->bearerToken();
        if ($auth !== config('services.callbacks.bearer_token')) {
            return response()->json(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $validated = $request->validate([
            'task_id' => ['required', 'integer', 'in:' . $id],
            'new_script' => ['required', 'string'],
            'analysis' => ['required', 'string'],
        ]);

        $task = AdScriptTask::findOrFail($id);
        $task->update([
            'new_script' => $validated['new_script'],
            'analysis' => $validated['analysis'],
            'status' => 'completed',
        ]);

        return response()->json(['ok' => true]);
    }
}
