<?php

namespace App\Http\Requests\Region;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRegionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'sometimes', 'string', 'max:90',
                Rule::unique('regions', 'name')->ignore($this->route('id')),
            ],
            'description' => ['nullable', 'string'],
            'status' => ['sometimes'],
        ];
    }
}
