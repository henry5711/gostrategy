<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CommuneController;
use App\Http\Controllers\Api\RegionController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('auth/login', [AuthController::class, 'login'])
    ->middleware('api.log');

Route::prefix('users')->middleware(['api.log', 'auth:sanctum'])->group(function () {
    Route::get('', [UserController::class, 'index']);
    Route::post('', [UserController::class, 'store']);


    Route::get('{id}', [UserController::class, 'show']);

    Route::delete('{id}', [UserController::class, 'destroy']);

    Route::post('{id}/update', [UserController::class, 'update']);

    Route::post('{id}/restore', [UserController::class, 'restore']);
});

Route::prefix('regions')->middleware(['api.log', 'auth:sanctum'])->group(function () {
    Route::get('', [RegionController::class, 'index']);
    Route::post('', [RegionController::class, 'store']);

    Route::get('{id}', [RegionController::class, 'show']);

    Route::delete('{id}', [RegionController::class, 'destroy']);

    Route::post('{id}/update', [RegionController::class, 'update']);
});

Route::prefix('communes')->middleware(['api.log', 'auth:sanctum'])->group(function () {
    Route::get('', [CommuneController::class, 'index']);
    Route::post('', [CommuneController::class, 'store']);

    Route::get('{id}', [CommuneController::class, 'show']);

    Route::delete('{id}', [CommuneController::class, 'destroy']);

    Route::post('{id}/update', [CommuneController::class, 'update']);
});
