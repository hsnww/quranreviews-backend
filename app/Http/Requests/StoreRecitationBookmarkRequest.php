<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRecitationBookmarkRequest extends FormRequest
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
            'start_surah' => ['required', 'integer', 'min:1', 'max:114'],
            'start_ayah' => ['required', 'integer', 'min:1'],
            'end_surah' => ['required', 'integer', 'min:1', 'max:114'],
            'end_ayah' => ['required', 'integer', 'min:1'],
        ];
    }
}
