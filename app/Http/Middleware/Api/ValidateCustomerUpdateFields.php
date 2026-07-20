<?php

namespace App\Http\Middleware\Api;

use App\Traits\ApiResponseTrait;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

/**
 * Valida los campos del update de un customer .
 */
class ValidateCustomerUpdateFields
{
    use ApiResponseTrait;

    public function handle(Request $request, Closure $next): Response
    {
        $validator = Validator::make($request->all(), [
            'email' => [
                'sometimes', 'email', 'max:120',
                // ignore($id) para no rechazar el mismo email que ya tiene el customer.
                Rule::unique('customers', 'email')->ignore($request->route('id')),
            ],
            'name' => ['sometimes', 'string', 'max:45'],
            'last_name' => ['sometimes', 'string', 'max:45'],
            'address' => ['nullable', 'string', 'max:255'],
            'region_id' => ['sometimes', 'required_with:commune_id', 'integer', 'exists:regions,id'],
            'commune_id' => [
                'sometimes',
                'required_with:region_id',
                'integer',
                Rule::exists('communes', 'id')->where('region_id', $request->input('region_id')),
            ],
        ], [
            'commune_id.exists' => 'La commune indicada no existe o no pertenece a la región indicada.',
        ]);

        if ($validator->fails()) {
            return $this->error('Validación fallida.', 422, $validator->errors());
        }

        return $next($request);
    }
}
