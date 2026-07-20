<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Login con email/password. El token de vida limitada lo emite Sanctum.
     */
    public function login(LoginRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (! $user || ! $user->password || ! Hash::check($request->password, $user->password)) {
            return response()->json(['success' => false, 'message' => 'Credenciales inválidas.', 'data' => null], 401);
        }

        $token = $user->createToken(
            'api-token',
            ['*'],
            now()->addMinutes((int) config('sanctum.expiration', 60))
        );

        return response()->json([
            'success' => true,
            'message' => 'Inicio de sesión exitoso',
            'data' => [
                'token' => $token->plainTextToken,
                'expires_at' => $token->accessToken->expires_at->toDateTimeString(),
            ],
        ]);
    }
}
