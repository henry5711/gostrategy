<?php

namespace App\Http\Middleware\Api;

use App\Traits\ApiResponseTrait;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class ValidateCommuneStoreFields
{
    use ApiResponseTrait;

    public function handle(Request $request, Closure $next): Response
    {
        $validator = Validator::make($request->all(), [
            'region_id' => ['required', 'integer', 'exists:regions,id'],
            'name' => [
                'required', 'string', 'max:90',
                Rule::unique('communes', 'name')->where('region_id', $request->input('region_id')),
            ],
            'description' => ['nullable', 'string'],
        ], [
            'name.unique' => 'Ya existe una commune con ese nombre en la región indicada.',
        ]);

        if ($validator->fails()) {
            return $this->error('Validación fallida.', 422, $validator->errors());
        }

        return $next($request);
    }
}
