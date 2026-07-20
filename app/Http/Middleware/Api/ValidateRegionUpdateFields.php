<?php

namespace App\Http\Middleware\Api;

use App\Traits\ApiResponseTrait;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

/**
 * Valida los campos del update de una region.
 */
class ValidateRegionUpdateFields
{
    use ApiResponseTrait;

    public function handle(Request $request, Closure $next): Response
    {
        $validator = Validator::make($request->all(), [
            'name' => [
                'sometimes', 'string', 'max:90',
                Rule::unique('regions', 'name')->ignore($request->route('id')),
            ],
            'description' => ['nullable', 'string'],
            'status' => ['sometimes', Rule::in(['A', 'I'])],
        ]);

        if ($validator->fails()) {
            return $this->error('Validación fallida.', 422, $validator->errors());
        }

        return $next($request);
    }
}
