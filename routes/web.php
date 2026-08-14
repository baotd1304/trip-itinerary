<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\UserController;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

Route::prefix('/admin')->middleware(['auth', 'role:admin'])->group(function () {
    Route::inertia('/dashboard', 'admin/Dashboard')->name('admin.dashboard');
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
});

require __DIR__.'/settings.php';
