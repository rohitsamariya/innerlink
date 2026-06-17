<?php

declare(strict_types=1);

namespace App\Domains\Health\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HealthSecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('Cache-Control', 'no-store');
        $response->headers->set('X-Robots-Tag', 'noindex');
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response;
    }
}
