<?php

declare(strict_types=1);

namespace App\Domains\Identity\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class TrackLastSeenMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && !$request->routeIs('health.*')) {
            try {
                DB::table('users')
                    ->where('id', $user->id)
                    ->update([
                        'last_seen_at' => now()->toIso8601String(),
                        'presence_status' => 'ONLINE',
                    ]);
            } catch (\Throwable $e) {
                Log::error('TrackLastSeenMiddleware failed', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $next($request);
    }
}
