<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AchievementController;
use App\Http\Controllers\Api\GachaController;
use App\Http\Controllers\Api\PokemonController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\RoomController;
use Illuminate\Support\Facades\Route;

Route::get('/pokemon', [PokemonController::class, 'index']);
Route::get('/pokemon/questions', [PokemonController::class, 'questions']);
Route::get('/pokemon/{pokemon}', [PokemonController::class, 'show']);
Route::post('/pokemon/sync', [PokemonController::class, 'sync']);
Route::post('/pokemon/evaluate', [PokemonController::class, 'evaluate']);
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::get('/auth/me', [AuthController::class, 'me'])->middleware('auth:sanctum');
Route::post('/auth/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
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
