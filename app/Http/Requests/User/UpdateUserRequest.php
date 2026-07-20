<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;


class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            'email' => [
                'sometimes', 'email', 'max:120',
                // ignore($id) para no rechazar el mismo email que ya tiene el customer.
                Rule::unique('users', 'email')->ignore($this->route('id')),
            ],
            'name' => ['sometimes', 'string', 'max:45'],
            'last_name' => ['sometimes', 'string', 'max:45'],
            'address' => ['nullable', 'string', 'max:255'],
            'region_id' => ['sometimes', 'required_with:commune_id', 'integer', 'exists:regions,id'],
            'commune_id' => [
                'sometimes',
                'required_with:region_id',
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
