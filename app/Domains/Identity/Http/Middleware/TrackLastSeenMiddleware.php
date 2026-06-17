<?php

declare(strict_types=1);

namespace App\Domains\Identity\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class TrackLastSeenMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && !$request->routeIs('health.*')) {
            DB::table('users')
                ->where('id', $user->id)
                ->update(['last_seen_at' => now()->toIso8601String()]);
        }

        return $next($request);
    }
}
