<?php

declare(strict_types=1);

namespace App\Domains\Admin\Http\Controllers;

use App\Domains\Communication\Models\Group;
use App\Domains\Communication\Models\Message;
use App\Domains\Identity\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class StatsController
{
    public function index(): JsonResponse
    {
        $stats = Cache::remember('admin.stats', 5, function () {
            return [
                'total_users' => User::count(),
                'online_users' => User::where('presence_status', 'ONLINE')->count(),
                'online_user_names' => User::where('presence_status', 'ONLINE')->orderBy('full_name')->pluck('full_name'),
                'active_groups' => Group::where('is_enabled', true)->count(),
                'new_messages' => Message::whereDate('sent_at', today())->count(),
            ];
        });

        return response()->json(['data' => $stats]);
    }
}
