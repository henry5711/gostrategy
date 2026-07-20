<?php

namespace App\Http\Middleware\Api;

use App\Traits\ApiResponseTrait;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class ValidateCustomerQueryFields
{
    use ApiResponseTrait;

    public function handle(Request $request, Closure $next): Response
    {
        $validator = Validator::make($request->all(), [
            'dni' => ['required_without:email', 'nullable', 'string', 'max:45'],
            'email' => ['required_without:dni', 'nullable', 'email', 'max:120'],
        ]);

        if ($validator->fails()) {
            return $this->error('Debe indicar el dni o el email del customer a consultar.', 422, $validator->errors());
        }

        return $next($request);
    }
}
