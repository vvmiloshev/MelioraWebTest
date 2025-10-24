<?php

namespace App\Livewire;

use App\Models\AdScriptTask;
use Livewire\Component;
use Livewire\WithPagination;
use App\Jobs\SendToN8nJob;

/**
 * Livewire component that lists AdScript tasks with search, filter by status,
 * create (modal form), row selection (details modal), and delete with confirmation.
 *
 * NOTE:
 * - Keep all comments in English (project convention).
 * - No functional changes have been made; comments only.
 */
class AdScriptTasksTable extends Component
{
    use WithPagination;

    /** ----------------------------------------------------------------
     *  Reactive filter state
     *  ---------------------------------------------------------------- */
    /** @var string Search term applied to several text columns. */
    public string $search = '';
    /** @var string Status filter: '', 'pending', 'completed', or 'failed'. */
    public string $status = '';

    /** ----------------------------------------------------------------
     *  Form & UI modal state
     *  ---------------------------------------------------------------- */
    /** @var string Input field: original/reference script text. */
    public string $reference_script = '';
    /** @var string Input field: desired outcome/description. */
    public string $outcome_description = '';
    /** @var bool Controls visibility of the "Add Script" modal form. */
    public bool $showForm = false;

    /** ----------------------------------------------------------------
     *  Selection & delete-confirmation state
     *  ---------------------------------------------------------------- */
    /** @var int|null Currently selected task id for viewing details. */
    public ?int $selectedTaskId = null;
    /** @var int|null Task id pending deletion (confirmation modal). */
    public ?int $confirmingDeleteId = null;

    /** Persist filters in the query string for shareable URLs and refresh safety. */
    protected $queryString = ['search', 'status'];

    /** Server-side validation rules for the create form. */
    protected $rules = [
        'reference_script'    => 'required|string|min:3',
        'outcome_description' => 'required|string|min:3',
    ];

    /**
     * Livewire lifecycle hook: when `search` changes, reset to page 1.
     * This prevents an empty page when the current page would no longer exist.
     */
    public function updatingSearch(): void { $this->resetPage(); }

    /**
     * Livewire lifecycle hook: when `status` changes, reset to page 1.
     */
    public function updatingStatus(): void { $this->resetPage(); }

    /**
     * Set the selected task id to open the details modal/panel.
     * @param int $taskId
     */
    public function selectTask(int $taskId): void { $this->selectedTaskId = $taskId; }

    /**
     * Close the details modal/panel.
     */
    public function closeModal(): void { $this->selectedTaskId = null; }

    /**
     * Create a new task and dispatch the background job to n8n.
     * - Validates inputs using $rules.
     * - Sets initial status to "pending".
     * - Does NOT mark as completed here; the callback endpoint will finalize.
     * - Shows a flash message and refreshes the table.
     *
     * UX TIP:
     * - In the Blade, disable the submit button while saving with `wire:loading.attr="disabled"`.
     * - Consider optimistic UI updates if needed.
     */
    public function createTask(): void
    {
        $this->validate();

        $task = AdScriptTask::create([
            'reference_script'    => $this->reference_script,
            'outcome_description' => $this->outcome_description,
            'status'              => 'pending',
        ]);

        // Fire-and-forget: actual processing is done by n8n; our API callback persists the result.
        SendToN8nJob::dispatch($task->id);

        // Reset only the form-related state; keep filters as they are.
        $this->reset(['reference_script', 'outcome_description', 'showForm']);

        // Show feedback to the user.
        session()->flash('message', 'Task created successfully!');

        // Ask Livewire to refresh the component state (re-run render()).
        $this->dispatch('$refresh');
    }

    /**
     * Begin deletion flow by setting the id that requires confirmation.
     * This typically opens a confirmation modal in the Blade.
     * @param int $taskId
     */
    public function confirmDelete(int $taskId): void { $this->confirmingDeleteId = $taskId; }

    /**
     * Cancel the delete flow and hide the confirmation modal.
     */
    public function cancelDelete(): void { $this->confirmingDeleteId = null; }

    /**
     * Finalize deletion after user confirmation.
     * - Safely handles missing records.
     * - Clears selection if the selected task was deleted.
     * - Resets pagination to avoid landing on an empty page.
     *
     * SECURITY NOTE:
     * - Ensure the Blade confirm UI is not triggerable by untrusted JS injections.
     * - Prefer server-side authorization checks if multi-user support is added.
     */
    public function deleteTaskConfirmed(): void
    {
        if (! $this->confirmingDeleteId) return;

        $task = AdScriptTask::find($this->confirmingDeleteId);
        if ($task) {
            $task->delete();
            session()->flash('message', "Task #{$this->confirmingDeleteId} deleted.");
        }

        // If the deleted task was selected in a modal, close it.
        if ($this->selectedTaskId === $this->confirmingDeleteId) {
            $this->selectedTaskId = null;
        }

        // Clear confirmation state and reset pagination.
        $this->confirmingDeleteId = null;
        $this->resetPage();
    }

    /**
     * Render the table with current filters and pagination.
     * - Uses conditional scopes via ->when() for clean filter application.
     * - `like` searches on large text columns can be slow; consider FULLTEXT if needed.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        $q = AdScriptTask::query()
            ->when($this->search, fn($qq) => $qq->where(function ($w) {
                $w->where('reference_script', 'like', "%{$this->search}%")
                    ->orWhere('outcome_description', 'like', "%{$this->search}%");
                // NOTE: If you want to search `new_script` and `analysis`, add them here too.
                // Beware of performance; prefer FULLTEXT indexes over leading-wildcard LIKE.
            }))
            ->when($this->status, fn($qq) => $qq->where('status', $this->status))
            ->latest();

        // Eager-load related data here if/when relationships are added to avoid N+1 queries.

        $selectedTask = $this->selectedTaskId
            ? AdScriptTask::find($this->selectedTaskId) // returns null if missing
            : null;

        return view('livewire.ad-script-tasks-table', [
            'tasks'        => $q->paginate(10),
            'selectedTask' => $selectedTask,
        ]);
    }
}
