<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MergeRecitationPlansRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'plan_ids' => ['required', 'array', 'min:2'],
            'plan_ids.*' => ['integer', 'distinct'],
            'title' => ['nullable', 'string', 'max:255'],
            'delete_sources' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'plan_ids.required' => 'يجب اختيار خطتين على الأقل للدمج.',
            'plan_ids.min' => 'يجب اختيار خطتين على الأقل للدمج.',
            'plan_ids.*.distinct' => 'لا يمكن تكرار نفس الخطة في الدمج.',
        ];
    }
}
