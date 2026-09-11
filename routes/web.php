<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\CarController;
use App\Http\Controllers\Admin\ExpenseController;
use App\Http\Controllers\Admin\TripController;
use App\Http\Controllers\Admin\TripExportController;

use App\Http\Controllers\Client\HomeController;
use App\Http\Controllers\Client\ClientTripController;
use App\Http\Controllers\Admin\CloudinaryUploadController;


Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('home');
    
    Route::get('/trips', [ClientTripController::class, 'index'])->name('client.trips.index');
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

    //expense routes
    Route::get('/expenses', [ExpenseController::class, 'index'])->name('admin.expenses.index');
    Route::post('/expenses', [ExpenseController::class, 'store'])->name('admin.expenses.store');
    Route::put('/expenses/{id}', [ExpenseController::class, 'update'])->name('admin.expenses.update');
    Route::delete('/expenses/{id}', [ExpenseController::class, 'destroy'])->name('admin.expenses.destroy');

    //trip routes
    Route::get('trips/cloudinary-signature', [CloudinaryUploadController::class, 'signature'])
        ->name('admin.trips.cloudinary-signature');

    Route::delete('trips/uploaded-image', [CloudinaryUploadController::class, 'discard'])
        ->name('admin.trips.uploaded-image.discard');

    Route::get('/trips', [TripController::class, 'index'])->name('admin.trips.index');
    Route::post('/trips', [TripController::class, 'store'])->name('admin.trips.store');
    Route::put('/trips/{id}', [TripController::class, 'update'])->name('admin.trips.update');
    Route::delete('/trips/{id}', [TripController::class, 'destroy'])->name('admin.trips.destroy');
    Route::get('/trips/export', [TripExportController::class, 'index'])->name('admin.trips.export.index');
    Route::get('/trips/export/download', [TripExportController::class, 'export'])->name('admin.trips.export.download');
    

});



    
require __DIR__.'/settings.php';
