<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Registra la entrada y salida de cada request
 */
class LogApiRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        Log::info('input', [
            'method' => $request->method(),
            'path' => $request->path(),
            'ip' => $request->ip(),
            'payload' => $request->except(['password']),
        ]);

        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        // En producción solo se guardan los logs de entrada (requisito explícito del punto 9).
        if (config('app.env') === 'production') {
            return;
        }

        Log::info('output', [
            'method' => $request->method(),
            'path' => $request->path(),
            'ip' => $request->ip(),
            'status' => $response->getStatusCode(),
            'payload' => $response->getContent(),
        ]);
    }
}
