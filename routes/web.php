<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\CarController;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

Route::prefix('/admin')->middleware(['auth', 'role:admin'])->group(function () {
    Route::inertia('/dashboard', 'admin/Dashboard')->name('admin.dashboard');

    //user routes
    Route::get('/users', [UserController::class, 'index'])->name('admin.users.index');
    Route::post('/users', [UserController::class, 'store'])->name('admin.users.store');
    Route::put('/users/{id}', [UserController::class, 'update'])->name('admin.users.update');
    Route::delete('/users/{id}', [UserController::class, 'destroy'])->name('admin.users.destroy');

    //car routes
    Route::get('/cars', [CarController::class, 'index'])->name('admin.cars.index');
    Route::post('/cars', [CarController::class, 'store'])->name('admin.cars.store');
    Route::put('/cars/{id}', [CarController::class, 'update'])->name('admin.cars.update');
    Route::delete('/cars/{id}', [CarController::class, 'destroy'])->name('admin.cars.destroy');

});

require __DIR__.'/settings.php';
