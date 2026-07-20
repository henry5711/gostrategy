<?php

namespace App\Http\Controllers\Api;

use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Models\AuthToken;
use App\Models\Customer;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    use ApiResponseTrait;

    public function login(Request $request)
    {
        try {
            $customer = Customer::where('email', $request->email)->first();

            if (! $customer || $customer->status !== Status::Active || ! Hash::check($request->password, $customer->password)) {
                return $this->error('Credenciales inválidas.', 401);
            }

            $loginAt = now();

            // Token = email + fecha/hora del login + random(200-500), todo en SHA1.
            $token = sha1($customer->email.$loginAt->format('YmdHis').random_int(200, 500));

            $authToken = AuthToken::create([
                'customer_id' => $customer->id,
                'token' => $token,
                'expires_at' => $loginAt->clone()->addMinutes((int) config('api.token_ttl_minutes')),
            ]);
        } catch (\Exception $e) {
            return $this->error('Error interno al iniciar sesión.', 500, $e->getMessage());
        }

        return $this->success([
            'token' => $authToken->token,
            'expires_at' => $authToken->expires_at->toDateTimeString(),
        ], 'Inicio de sesión exitoso');
    }
}
