<?php

use Illuminate\Support\Facades\Route;
use App\Domains\Identity\Http\Controllers\AuthController;
use App\Domains\Communication\Http\Controllers\MessageController;
use App\Domains\Communication\Http\Controllers\TypingController;
use App\Domains\Admin\Http\Controllers\ExportController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Auth Routes
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    
    Route::post('logout', [AuthController::class, 'logout'])
        ->middleware(['auth:sanctum', 'active.user', 'track.last.seen']);

    Route::get('me', [AuthController::class, 'me'])
        ->middleware(['auth:sanctum', 'active.user', 'track.last.seen']);
});

// Groups Routes
Route::prefix('groups')->middleware(['auth:sanctum', 'active.user', 'track.last.seen'])->group(function () {
    Route::get('/', [\App\Domains\Communication\Http\Controllers\GroupController::class, 'index']);
    Route::post('/', [\App\Domains\Communication\Http\Controllers\GroupController::class, 'store']);
    Route::get('{group}', [\App\Domains\Communication\Http\Controllers\GroupController::class, 'show']);
    Route::get('{group}/messages/search', [MessageController::class, 'search']);
    Route::get('{group}/messages', [MessageController::class, 'index']);
    Route::post('{group}/messages', [MessageController::class, 'store']);
    Route::post('{group}/messages/{message}/deliver', [MessageController::class, 'deliver']);
    Route::post('{group}/messages/read', [MessageController::class, 'markRead']);
    Route::get('{group}/messages/{message}/readers', [MessageController::class, 'readers']);
    Route::patch('{group}/settings', [\App\Domains\Communication\Http\Controllers\GroupController::class, 'update']);
    Route::post('{group}/typing', [TypingController::class, 'typing'])
        ->middleware(['throttle:typing']);
});

// Admin Routes
Route::prefix('admin')->middleware(['auth:sanctum', 'active.user', 'admin.only', 'track.last.seen'])->group(function () {
    Route::prefix('exports')->group(function () {
        Route::get('/', [ExportController::class, 'index']);
        Route::post('/', [ExportController::class, 'store']);
        Route::get('{id}', [ExportController::class, 'show']);
    });

    Route::get('users', [\App\Domains\Admin\Http\Controllers\UserController::class, 'index']);

    Route::prefix('groups')->group(function () {
        Route::get('{group}/members', [\App\Domains\Admin\Http\Controllers\GroupController::class, 'members']);
        Route::post('{group}/members', [\App\Domains\Admin\Http\Controllers\GroupController::class, 'addMember']);
        Route::delete('{group}/members/{user}', [\App\Domains\Admin\Http\Controllers\GroupController::class, 'removeMember']);
        Route::get('{group}/messages/download', [\App\Domains\Admin\Http\Controllers\GroupController::class, 'downloadMessages']);
    });
});

// Users management (admin only, frontend URL /users)
Route::prefix('users')->middleware(['auth:sanctum', 'active.user', 'admin.only', 'track.last.seen'])->group(function () {
    Route::get('/', [\App\Domains\Admin\Http\Controllers\UserController::class, 'index']);
    Route::post('/', [\App\Domains\Admin\Http\Controllers\UserController::class, 'store']);
    Route::patch('{user}/toggle-status', [\App\Domains\Admin\Http\Controllers\UserController::class, 'toggleStatus']);
    Route::patch('{user}', [\App\Domains\Admin\Http\Controllers\UserController::class, 'update']);
});

// Private 1-on-1 messaging
Route::prefix('private-messages')->middleware(['auth:sanctum', 'active.user', 'track.last.seen'])->group(function () {
    Route::get('conversations', [\App\Domains\Communication\Http\Controllers\PrivateMessageController::class, 'conversations']);
    Route::get('contacts', [\App\Domains\Communication\Http\Controllers\PrivateMessageController::class, 'contacts']);
    Route::get('contact/{user}', [\App\Domains\Communication\Http\Controllers\PrivateMessageController::class, 'profile']);
    Route::get('{otherUser}', [\App\Domains\Communication\Http\Controllers\PrivateMessageController::class, 'index']);
    Route::post('{otherUser}', [\App\Domains\Communication\Http\Controllers\PrivateMessageController::class, 'store']);
    Route::post('{otherUser}/read', [\App\Domains\Communication\Http\Controllers\PrivateMessageController::class, 'markRead']);
});

// Login activity routes
Route::post('/auth/record-login', [\App\Http\Controllers\LoginActivityController::class, 'store'])
    ->middleware(['auth:sanctum']);
Route::post('/auth/record-logout', [\App\Http\Controllers\LoginActivityController::class, 'recordLogout'])
    ->middleware(['auth:sanctum']);
Route::get('/users/{userId}/login-activity', [\App\Http\Controllers\LoginActivityController::class, 'index'])
    ->middleware(['auth:sanctum', 'active.user', 'admin.only']);

// Stats route
Route::get('/stats', [\App\Domains\Admin\Http\Controllers\StatsController::class, 'index'])
    ->middleware(['auth:sanctum', 'active.user', 'track.last.seen']);

// Health Check Routes (no auth — required by load balancers and orchestration probes)
Route::get('/health/live', [\App\Domains\Health\Http\Controllers\HealthCheckController::class, 'live'])
    ->middleware(['throttle:health', 'health.headers']);

Route::get('/health/ready', [\App\Domains\Health\Http\Controllers\HealthCheckController::class, 'ready'])
    ->middleware(['throttle:health', 'health.headers']);
