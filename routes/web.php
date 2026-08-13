<?php

use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

Route::prefix('/admin')->middleware(['auth', 'adminAccess'])->group(function () {
    Route::inertia('/dashboard', 'admin/Dashboard')->name('admin.dashboard');
});

require __DIR__.'/settings.php';
