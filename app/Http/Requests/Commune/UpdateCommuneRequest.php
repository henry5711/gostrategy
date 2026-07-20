<?php

namespace App\Http\Requests\Commune;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCommuneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'region_id' => ['sometimes', 'integer', 'exists:regions,id'],
            'name' => ['sometimes', 'string', 'max:90'],
            'description' => ['nullable', 'string'],
            'status' => ['sometimes'],
        ];
    }
}
