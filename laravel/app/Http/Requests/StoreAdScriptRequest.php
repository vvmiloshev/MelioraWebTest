<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAdScriptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reference_script' => ['required', 'string', 'min:20'],
            'outcome_description' => ['required', 'string', 'min:5', 'max:2000'],
        ];
    }
}
