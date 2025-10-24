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
    public function store(StoreAdScriptRequest $request): JsonResponse
    {
        $task = AdScriptTask::create([
            'reference_script' => $request->input('reference_script'),
            'outcome_description' => $request->input('outcome_description'),
            'status' => 'pending',
        ]);

        SendToN8nJob::dispatch($task->id);

        return response()->json([
            'id' => $task->id,
            'status' => $task->status,
        ], Response::HTTP_ACCEPTED);
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
