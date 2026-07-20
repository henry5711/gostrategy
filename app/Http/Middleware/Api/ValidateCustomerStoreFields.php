<?php

namespace App\Http\Middleware\Api;

use App\Traits\ApiResponseTrait;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class ValidateCustomerStoreFields
{
    use ApiResponseTrait;

    public function handle(Request $request, Closure $next): Response
    {
        $validator = Validator::make($request->all(), [
            'dni' => ['required', 'string', 'max:45', 'unique:customers,dni'],
            'email' => ['required', 'email', 'max:120', 'unique:customers,email'],
            'password' => ['required', 'string', 'min:8'],
            'name' => ['required', 'string', 'max:45'],
            'last_name' => ['required', 'string', 'max:45'],
            'address' => ['nullable', 'string', 'max:255'],
            'region_id' => ['required', 'integer', 'exists:regions,id'],
            'commune_id' => [
                'required',
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
