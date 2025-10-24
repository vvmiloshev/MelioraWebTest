<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request class for validating and authorizing
 * the creation of a new AdScript task.
 *
 * BENEFITS:
 * - Keeps controllers clean (validation logic isolated).
 * - Ensures consistent validation rules across API and Livewire forms.
 * - Supports auto-generated documentation if using OpenAPI.
 */
class StoreAdScriptRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * SECURITY NOTE:
     * - For public APIs or service-to-service integrations, you might return true here.
     * - If you add authentication later (e.g., API tokens), check roles/permissions.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Define validation rules for creating a new AdScript task.
     * These rules ensure data integrity before storing in the database.
     *
     * PERFORMANCE:
     * - 'string' automatically casts to UTF-8 safe text.
     * - 'min:3' avoids meaningless very short inputs.
     */
    public function rules(): array
    {
        return [
            'reference_script'    => ['required', 'string', 'min:3'],
            'outcome_description' => ['required', 'string', 'min:3'],
        ];
    }

    /**
     * Optional: Custom validation messages for better API feedback.
     * Particularly useful when requests come from automated workflows like n8n.
     */
    public function messages(): array
    {
        return [
            'reference_script.required'    => 'The reference script field is required.',
            'reference_script.min'         => 'The reference script must be at least 3 characters.',
            'outcome_description.required' => 'The outcome description field is required.',
            'outcome_description.min'      => 'The outcome description must be at least 3 characters.',
        ];
    }

    /**
     * Optional: Data preparation hook before validation.
     * You can trim inputs or normalize whitespace here.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'reference_script'    => trim((string) $this->input('reference_script')),
            'outcome_description' => trim((string) $this->input('outcome_description')),
        ]);
    }
}
