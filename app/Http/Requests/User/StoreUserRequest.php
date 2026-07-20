<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;


class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            'dni' => ['required', 'string', 'max:45', 'unique:users,dni'],
            'email' => ['required', 'email', 'max:120', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'name' => ['required', 'string', 'max:45'],
            'last_name' => ['required', 'string', 'max:45'],
            'address' => ['nullable', 'string', 'max:255'],
            'region_id' => ['required', 'integer', 'exists:regions,id'],
            // Rule::exists()->where(...) valida en una sola pasada que la commune exista
            // Y que su region_id coincida con la región enviada (no basta con que exista).
            'commune_id' => [
                'required',
                'integer',
                Rule::exists('communes', 'id')->where('region_id', $this->input('region_id')),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'commune_id.exists' => 'La commune indicada no existe o no pertenece a la región indicada.',
        ];
    }
}
