<?php

namespace App\Http\Middleware\Api;

use App\Traits\ApiResponseTrait;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

/**
 * Valida los campos para el cambio de contraseña de un customer.
 */
class ValidateCustomerChangePasswordFields
{
    use ApiResponseTrait;

    public function handle(Request $request, Closure $next): Response
    {
        $validator = Validator::make($request->all(), [
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'different:current_password'],
        ]);

        if ($validator->fails()) {
            return $this->error('Validación fallida.', 422, $validator->errors());
        }

        return $next($request);
    }
}
