<?php

namespace App\Events;

use App\Models\AdScriptTask;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event triggered whenever an AdScriptTask is created, updated, or deleted.
 * Used to notify Livewire or front-end clients to refresh the task list in real time.
 *
 * NOTE:
 * - This event implements ShouldBroadcast for Echo / Pusher integration.
 * - Keep payload small — send only what the UI needs to update efficiently.
 * - If the app will later support multiple users, use PrivateChannel for per-user isolation.
 */
class AdScriptTaskUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /** @var \App\Models\AdScriptTask */
    public AdScriptTask $task;

    /**
     * Create a new event instance.
     *
     * @param AdScriptTask $task
     */
    public function __construct(AdScriptTask $task)
    {
        // Broadcast a fresh copy to ensure serialization works cleanly
        // and does not include unneeded relations.
        $this->task = $task->fresh();
    }

    /**
     * The channel on which the event is broadcast.
     * - Public channels are fine for demo environments.
     * - Switch to PrivateChannel('tasks') if you add authentication later.
     */
    public function broadcastOn(): Channel
    {
        // TIP: Use consistent naming for frontend listeners.
        // Example in JS: Echo.channel('ad-script-tasks').listen('AdScriptTaskUpdated', ...)
        return new Channel('ad-script-tasks');
    }

    /**
     * The event name that will be used on the frontend.
     * Optional — Laravel defaults to the class name.
     */
    public function broadcastAs(): string
    {
        return 'AdScriptTaskUpdated';
    }

    /**
     * Define which data gets broadcasted.
     * Keeping the payload lightweight improves performance and security.
     */
    public function broadcastWith(): array
    {
        return [
            'id'       => $this->task->id,
            'status'   => $this->task->status,
            'updated'  => $this->task->updated_at?->toISOString(),
        ];
    }
}
