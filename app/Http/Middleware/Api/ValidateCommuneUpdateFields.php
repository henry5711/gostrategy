<?php

namespace App\Http\Middleware\Api;

use App\Traits\ApiResponseTrait;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

/**
 * Valida los campos del update de una commune.
 */
class ValidateCommuneUpdateFields
{
    use ApiResponseTrait;

    public function handle(Request $request, Closure $next): Response
    {
        $validator = Validator::make($request->all(), [
            'region_id' => ['sometimes', 'integer', 'exists:regions,id'],
            'name' => [
                'sometimes', 'string', 'max:90',
                Rule::unique('communes', 'name')
                    ->where('region_id', $request->input('region_id'))
                    ->ignore($request->route('id')),
            ],
            'description' => ['nullable', 'string'],
            'status' => ['sometimes', Rule::in(['A', 'I'])],
        ], [
            'name.unique' => 'Ya existe una commune con ese nombre en la región indicada.',
        ]);

        if ($validator->fails()) {
            return $this->error('Validación fallida.', 422, $validator->errors());
        }

        return $next($request);
    }
}
