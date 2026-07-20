<?php

namespace App\Http\Middleware\Api;

use App\Models\AuthToken;
use App\Traits\ApiResponseTrait;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verifica el "key de autenticación vía middleware.
 */
class VerifyApiToken
{
    use ApiResponseTrait;

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! $token) {
            return $this->error('Token de autenticación no proporcionado.', 401);
        }

        // El where('expires_at', '>', now()) hace que un token vencido se trate igual
        // que uno inexistente: no debe dar acceso al servicio.
        $authToken = AuthToken::with('customer')
            ->where('token', $token)
            ->where('expires_at', '>', now())
            ->first();

        if (! $authToken) {
            return $this->error('Token de autenticación inválido o expirado.', 401);
        }

        $request->attributes->set('auth_customer', $authToken->customer);

        return $next($request);
    }
}
