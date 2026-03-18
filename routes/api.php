<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AchievementController;
use App\Http\Controllers\Api\GachaController;
use App\Http\Controllers\Api\PokemonController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\RoomController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/pokemon', [PokemonController::class, 'index']);
Route::get('/pokemon/questions', [PokemonController::class, 'questions']);
Route::get('/pokemon/{pokemon}', [PokemonController::class, 'show']);
Route::post('/pokemon/sync', [PokemonController::class, 'sync']);
Route::post('/pokemon/evaluate', [PokemonController::class, 'evaluate']);
// Auth routes (public)
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Protected API routes - require either Sanctum token or valid player_token
Route::middleware(['auth.token.or.profile', 'not.banned'])->group(function (): void {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/profile/upsert', [ProfileController::class, 'upsert']);
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::get('/achievements', [AchievementController::class, 'index']);
    Route::get('/gacha', [GachaController::class, 'index']);
    Route::post('/gacha/open', [GachaController::class, 'open']);

    Route::post('/rooms/create', [RoomController::class, 'create']);
    Route::post('/rooms/join', [RoomController::class, 'join']);
    Route::get('/rooms/public', [RoomController::class, 'publicRooms']);
    Route::get('/rooms/{code}', [RoomController::class, 'show']);
    Route::post('/rooms/{code}/select-hidden', [RoomController::class, 'selectHidden']);
    Route::post('/rooms/{code}/ask', [RoomController::class, 'ask']);
    Route::post('/rooms/{code}/answer', [RoomController::class, 'answer']);
    Route::post('/rooms/{code}/guess', [RoomController::class, 'guess']);
    Route::post('/rooms/{code}/surrender', [RoomController::class, 'surrender']);
    Route::post('/rooms/{code}/timer-propose', [RoomController::class, 'timerPropose']);
    Route::post('/rooms/{code}/timer-accept', [RoomController::class, 'timerAccept']);
});

Route::middleware(['auth:sanctum', 'not.banned', 'admin'])->prefix('admin')->group(function (): void {
    Route::get('/summary', [AdminController::class, 'summary']);
    Route::get('/users', [AdminController::class, 'users']);
    Route::post('/users/{userId}/set-admin', [AdminController::class, 'setAdmin']);
    Route::post('/users/{userId}/ban', [AdminController::class, 'banUser']);
    Route::post('/users/{userId}/unban', [AdminController::class, 'unbanUser']);
    Route::post('/users/{userId}/grant-capsules', [AdminController::class, 'grantCapsules']);

    Route::get('/rooms', [AdminController::class, 'listRooms']);
    Route::get('/rooms/{code}/spectate', [AdminController::class, 'spectateRoom']);
    Route::post('/rooms/{code}/close', [AdminController::class, 'closeRoom']);
});
