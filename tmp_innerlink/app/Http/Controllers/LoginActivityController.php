<?php

namespace App\Http\Controllers;

use App\Models\LoginActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;

class LoginActivityController extends Controller
{
    public function index($userId)
    {
        $activities = LoginActivity::where('user_id', $userId)
            ->orderBy('login_at', 'desc')
            ->get();

        if ($activities->isNotEmpty()) {
            return response()->json([
                'data' => $activities->map(function ($row) {
                    return [
                        'id' => $row->id,
                        'ip_address' => $row->ip_address,
                        'user_agent' => $row->user_agent,
                        'logged_in_at' => $row->login_at,
                        'last_used_at' => $row->last_activity_at,
                        'logged_out_at' => $row->logout_at,
                    ];
                }),
            ]);
        }

        $tokens = PersonalAccessToken::where('tokenable_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'data' => $tokens->map(function ($token) {
                return [
                    'id' => $token->id,
                    'ip_address' => null,
                    'user_agent' => null,
                    'logged_in_at' => $token->created_at,
                    'last_used_at' => $token->last_used_at,
                    'logged_out_at' => null,
                ];
            }),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|integer',
            'ip_address' => 'nullable|string|max:45',
            'user_agent' => 'nullable|string',
        ]);

        $id = DB::table('login_activities')->insertGetId([
            'user_id' => $data['user_id'],
            'ip_address' => $data['ip_address'] ?? $request->ip(),
            'user_agent' => $data['user_agent'] ?? $request->userAgent(),
            'login_at' => now(),
            'last_activity_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['data' => ['id' => $id]], 201);
    }

    public function recordLogout(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|integer',
        ]);

        DB::table('login_activities')
            ->where('user_id', $data['user_id'])
            ->whereNull('logout_at')
            ->latest('login_at')
            ->limit(1)
            ->update(['logout_at' => now(), 'updated_at' => now()]);

        return response()->json(['updated' => true]);
    }
}
