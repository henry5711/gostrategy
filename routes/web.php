<?php

use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    Route::inertia('regions', 'regions/Index')->name('regions.index');
    Route::inertia('communes', 'communes/Index')->name('communes.index');
    Route::inertia('users', 'users/Index')->name('users.index');
});

require __DIR__.'/settings.php';
