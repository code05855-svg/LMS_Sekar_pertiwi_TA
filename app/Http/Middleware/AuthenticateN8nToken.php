<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menjaga route /api/n8n/*: hanya n8n yang tahu token di .env (N8N_API_TOKEN).
 * Alias: n8n.token.
 */
class AuthenticateN8nToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
        $diharap = config('services.n8n.token');

        if (! $diharap || ! $token || ! hash_equals($diharap, $token)) {
            return response()->json(['pesan' => 'Token tidak valid'], 401);
        }

        return $next($request);
    }
}
