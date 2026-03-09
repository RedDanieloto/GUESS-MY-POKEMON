<?php

use App\Http\Controllers\Api\PokemonController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\RoomController;
use Illuminate\Support\Facades\Route;

Route::get('/pokemon', [PokemonController::class, 'index']);
Route::get('/pokemon/questions', [PokemonController::class, 'questions']);
Route::get('/pokemon/{pokemon}', [PokemonController::class, 'show']);
Route::post('/pokemon/sync', [PokemonController::class, 'sync']);
Route::post('/pokemon/evaluate', [PokemonController::class, 'evaluate']);
Route::post('/profile/upsert', [ProfileController::class, 'upsert']);
Route::get('/profile', [ProfileController::class, 'show']);

Route::post('/rooms/create', [RoomController::class, 'create']);
Route::post('/rooms/join', [RoomController::class, 'join']);
Route::get('/rooms/public', [RoomController::class, 'publicRooms']);
Route::get('/rooms/{code}', [RoomController::class, 'show']);
Route::post('/rooms/{code}/select-hidden', [RoomController::class, 'selectHidden']);
Route::post('/rooms/{code}/ask', [RoomController::class, 'ask']);
Route::post('/rooms/{code}/answer', [RoomController::class, 'answer']);
Route::post('/rooms/{code}/guess', [RoomController::class, 'guess']);
