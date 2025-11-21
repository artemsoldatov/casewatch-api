<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DispositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(['clear', 'escalate', 'assign'])],
            'note' => ['nullable', 'string', 'max:2000'],
            'assignee' => ['required_if:action,assign', 'nullable', 'string', 'max:120'],
        ];
    }
}
