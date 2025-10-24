<div class="relative" x-data>

    {{-- Header --}}
    <div class="flex justify-between items-center mb-4">
        <div class="flex gap-3 items-center">
            <input type="text"
                   wire:model.debounce.400ms="search"
                   placeholder="Search…"
                   class="border p-2 rounded w-64">

            <select wire:model="status" class="border p-2 rounded">
                <option value="">All statuses</option>
                <option value="pending">pending</option>
                <option value="completed">completed</option>
                <option value="failed">failed</option>
            </select>
        </div>

        <button type="button"
                wire:click="$toggle('showForm')"
                class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
            + Add Script
        </button>
    </div>

    {{-- Flash message --}}
    @if (session()->has('message'))
        <div class="bg-green-100 text-green-800 p-2 rounded mb-4">
            {{ session('message') }}
        </div>
    @endif

    {{-- Create Form --}}
    @if($showForm)
        <div class="border p-4 rounded mb-4 bg-gray-50">
            <h3 class="font-semibold text-lg mb-2">New Script Task</h3>

            <div class="mb-3">
                <label class="block text-sm font-medium text-gray-700 mb-1">Reference Script</label>
                <textarea wire:model.defer="reference_script"
                          class="w-full border rounded p-2"
                          rows="3"></textarea>
                @error('reference_script')
                <span class="text-red-600 text-sm">{{ $message }}</span>
                @enderror
            </div>

            <div class="mb-3">
                <label class="block text-sm font-medium text-gray-700 mb-1">Outcome Description</label>
                <textarea wire:model.defer="outcome_description"
                          class="w-full border rounded p-2"
                          rows="3"></textarea>
                @error('outcome_description')
                <span class="text-red-600 text-sm">{{ $message }}</span>
                @enderror
            </div>

            <div class="flex justify-end gap-2">
                <button type="button"
                        wire:click="$set('showForm', false)"
                        class="border px-4 py-2 rounded">
                    Cancel
                </button>
                <button type="button"
                        wire:click="createTask"
                        class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                    Save
                </button>
            </div>
        </div>
    @endif

    {{-- Tasks Table (poll only here) --}}
    <div wire:poll.5s>
        <table class="w-full border-collapse">
            <thead>
            <tr class="text-left border-b">
                <th class="p-2">ID</th>
                <th class="p-2">Status</th>
                {{-- CHANGED: split "Outcome" into two columns --}}
                <th class="p-2">Original Script</th>
                <th class="p-2">Revised Script</th>
                <th class="p-2">Created</th>
                <th class="p-2">Updated</th>
                <th class="p-2 text-right">Actions</th>
            </tr>
            </thead>
            <tbody>
            @forelse($tasks as $t)
                <tr class="border-b hover:bg-gray-50" wire:key="row-{{ $t->id }}">
                    <td class="p-2">{{ $t->id }}</td>
                    <td class="p-2">
                        <span class="px-2 py-1 rounded text-sm
                            @if($t->status === 'completed') bg-green-100 text-green-700
                            @elseif($t->status === 'failed') bg-red-100 text-red-700
                            @else bg-yellow-100 text-yellow-700 @endif">
                            {{ $t->status }}
                        </span>
                    </td>

                    {{-- NEW: Original Script (reference_script) --}}
                    <td class="p-2 truncate max-w-[300px]">
                        {{ $t->reference_script }}
                    </td>

                    {{-- NEW: Revised Script (new_script) --}}
                    <td class="p-2 truncate max-w-[300px]">
                        @if($t->new_script)
                            {{ $t->new_script }}
                        @else
                            <span class="text-gray-500 italic">— not available yet —</span>
                        @endif
                    </td>

                    <td class="p-2">{{ $t->created_at->format('Y-m-d H:i') }}</td>
                    <td class="p-2">{{ $t->updated_at->format('Y-m-d H:i') }}</td>
                    <td class="p-2 text-right">
                        <button type="button"
                                wire:click="selectTask({{ $t->id }})"
                                class="border px-2 py-1 rounded hover:bg-gray-100">
                            View
                        </button>

                        {{--<button type="button"
                                wire:click="confirmDelete({{ $t->id }})"
                                class="border px-2 py-1 rounded bg-red-600 text-white hover:bg-red-700 ml-2">
                            Delete
                        </button>--}}
                        <button type="button"
                                wire:click="$set('confirmingDeleteId', {{ $t->id }})"
                                class="border px-2 py-1 rounded bg-red-600 text-white hover:bg-red-700 ml-2">
                            Delete
                        </button>
                    </td>
                </tr>
            @empty
                {{-- CHANGED: colspan increased by 1 because we added an extra column --}}
                <tr>
                    <td class="p-4 text-gray-500 text-center" colspan="7">No tasks yet.</td>
                </tr>
            @endforelse
            </tbody>
        </table>

        <div class="mt-3">
            {{ $tasks->links() }}
        </div>
    </div>

    {{-- View Details Modal --}}
    @if($selectedTaskId && $selectedTask)
        <div wire:teleport="body">
            <div class="fixed inset-0 bg-black/60 z-[9999] flex justify-center items-center"
                 wire:click.self="closeModal"
                 wire:keydown.escape="closeModal"
                 tabindex="0" role="dialog" aria-modal="true">
                <div class="bg-white rounded-lg p-6 w-full max-w-3xl shadow-2xl relative">
                    <h3 class="text-xl font-semibold mb-2">Task #{{ $selectedTask->id }} details</h3>

                    <p class="text-sm mb-1 font-medium">Reference:</p>
                    <pre class="bg-gray-100 p-3 overflow-auto max-h-48">{{ $selectedTask->reference_script }}</pre>

                    <p class="text-sm mt-3 mb-1 font-medium">Outcome:</p>
                    <pre class="bg-gray-100 p-3 overflow-auto max-h-48">{{ $selectedTask->outcome_description }}</pre>

                    @if($selectedTask->new_script)
                        <p class="text-sm mt-3 mb-1 font-medium">New Script:</p>
                        <pre class="bg-green-50 p-3 overflow-auto max-h-56">{{ $selectedTask->new_script }}</pre>
                    @endif

                    @if($selectedTask->analysis)
                        <p class="text-sm mt-3 mb-1 font-medium">Analysis:</p>
                        <pre class="bg-blue-50 p-3 overflow-auto max-h-56">{{ $selectedTask->analysis }}</pre>
                    @endif

                    @if($selectedTask->error_details)
                        <p class="text-sm mt-3 mb-1 font-medium text-red-700">Error:</p>
                        <pre class="bg-red-50 p-3 overflow-auto max-h-48">{{ $selectedTask->error_details }}</pre>
                    @endif

                    <div class="mt-4 text-right">
                        <button type="button"
                                wire:click="closeModal"
                                class="border px-4 py-2 rounded hover:bg-gray-100">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
    {{-- Confirm Delete Modal --}}

    @if($confirmingDeleteId)
        <div wire:teleport="body">
            <div class="fixed inset-0 bg-black/60 z-[9999] flex justify-center items-center"
                 x-on:click.self="$wire.cancelDelete()"  {{-- stop background click --}}
                 tabindex="0" role="dialog" aria-modal="true">
                <div class="bg-white rounded-lg p-6 w-full max-w-md shadow-2xl text-center">
                    <h3 class="text-lg font-semibold mb-4">
                        Are you sure you want to delete task #{{ $confirmingDeleteId }}?
                    </h3>
                    <div class="flex justify-center gap-3">
                        <button type="button"
                                x-on:click.stop.prevent="$wire.cancelDelete()"
                                class="border px-4 py-2 rounded hover:bg-gray-100">
                            Cancel
                        </button>
                        <button type="button"
                                x-on:click.stop.prevent="$wire.deleteTaskConfirmed()"
                                class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
