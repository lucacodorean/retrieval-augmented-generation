<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class WorkflowCheckRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'workflow_id' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'workflow_id.required' => 'The workflow_id is required.',
            'workflow_id.string'   => 'The workflow_id is not a string.',
        ];
    }
}
