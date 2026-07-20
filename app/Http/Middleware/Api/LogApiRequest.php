<?php

namespace App\Http\Middleware\Api;

use App\Models\RequestLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Registra en request_logs la entrada y salida de cada request
 */
class LogApiRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        // El log de entrada va primero en la cadena de middlewares (ver routes/api.php),
        // así queda registrado incluso si un middleware posterior (token, validación) corta la request.
        RequestLog::create([
            'type' => 'input',
            'method' => $request->method(),
            'path' => $request->path(),
            'ip' => $request->ip(),
            // password nunca se guarda en el log, ni en local ni en producción.
            'payload' => json_encode($request->except(['password'])),
            'created_at' => now(),
        ]);

        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        // En producción solo se guardan los logs de entrada.
        if (config('app.env') === 'production') {
            return;
        }

        RequestLog::create([
            'type' => 'output',
            'method' => $request->method(),
            'path' => $request->path(),
            'ip' => $request->ip(),
            'payload' => $response->getContent(),
            'status_code' => $response->getStatusCode(),
            'created_at' => now(),
        ]);
    }
}
