<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CommuneController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\RegionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('auth/login', [AuthController::class, 'login'])
    ->middleware(['api.log', 'validate.login']);

Route::prefix('customers')->middleware(['api.log', 'api.token'])->group(function () {
    Route::post('', [CustomerController::class, 'store'])
        ->middleware('validate.customer.store');
    Route::get('', [CustomerController::class, 'index'])
        ->middleware('validate.customer.query');
    Route::delete('{id}', [CustomerController::class, 'destroy']);

    Route::post('{id}/update', [CustomerController::class, 'update'])
        ->middleware('validate.customer.update');

    Route::post('{id}/restore', [CustomerController::class, 'restore']);

    Route::post('{id}/change-password', [CustomerController::class, 'changePassword'])
        ->middleware('validate.customer.change-password');
});

Route::prefix('regions')->middleware(['api.log', 'api.token'])->group(function () {
    Route::post('', [RegionController::class, 'store'])
        ->middleware('validate.region.store');
    Route::get('', [RegionController::class, 'index']);
    Route::get('{id}', [RegionController::class, 'show']);
    Route::delete('{id}', [RegionController::class, 'destroy']);

    Route::post('{id}/update', [RegionController::class, 'update'])
        ->middleware('validate.region.update');

    Route::post('{id}/restore', [RegionController::class, 'restore']);
});

Route::prefix('communes')->middleware(['api.log', 'api.token'])->group(function () {
    Route::post('', [CommuneController::class, 'store'])
        ->middleware('validate.commune.store');
    Route::get('', [CommuneController::class, 'index']);
    Route::get('{id}', [CommuneController::class, 'show']);
    Route::delete('{id}', [CommuneController::class, 'destroy']);

    Route::post('{id}/update', [CommuneController::class, 'update'])
        ->middleware('validate.commune.update');

    Route::post('{id}/restore', [CommuneController::class, 'restore']);
});
