<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\CarController;
use App\Http\Controllers\Admin\ExpenseController;
use App\Http\Controllers\Admin\TripController;
use App\Http\Controllers\Admin\TripExportController;
use App\Http\Controllers\Admin\CloudinaryUploadController;

use App\Http\Controllers\Client\HomeController;
use App\Http\Controllers\Client\ClientTripController;
use App\Http\Controllers\Client\ClientCloudinaryUploadController;
use App\Http\Controllers\TripReopenController;

use App\Http\Controllers\Client\TripReopenRequestController;
use App\Http\Controllers\TripReopenReviewController;
use App\Http\Controllers\TripReviewController;



Route::middleware(['auth', 'role:driver|advisor|admin|editor'])->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('home');
    
    Route::get('trips', [ClientTripController::class, 'index'])
        ->middleware('can:viewAny,App\Models\Trip')->name('client.trips.index');
    Route::post('trips', [ClientTripController::class, 'store'])
        ->middleware('can:create,App\Models\Trip')
        ->name('client.trips.store');
    Route::put('trips/{trip}', [ClientTripController::class, 'update'])
        ->middleware('can:update,trip')
        ->name('client.trips.update');
    Route::delete('trips/{trip}', [ClientTripController::class, 'destroy'])
        ->middleware('can:delete,trip')
        ->name('client.trips.destroy');

    Route::get('trips/cloudinary-signature', [ClientCloudinaryUploadController::class, 'signature'])
        ->name('client.trips.cloudinary-signature');
    Route::delete('trips/uploaded-image', [ClientCloudinaryUploadController::class, 'discard'])
        ->name('client.trips.uploaded-image.discard');

    /* ---------- Driver: gửi / huỷ yêu cầu mở khoá ---------- */
    Route::post('trips/{trip}/reopen-requests', [TripReopenRequestController::class, 'store'])
        ->middleware(['can:requestReopen,trip', 'throttle:10,1'])
        ->name('client.trips.reopen-requests.store');

    Route::delete('reopen-requests/{reopenRequest}', [TripReopenRequestController::class, 'destroy'])
        ->middleware('can:cancel,reopenRequest')
        ->name('client.reopen-requests.destroy');
    
    /* ---------- Advisor/Admin: duyệt yêu cầu mở khoá ---------- */
    Route::patch('reopen-requests/{reopenRequest}/approve', [TripReopenReviewController::class, 'approve'])
        ->middleware('can:review,reopenRequest')
        ->name('client.reopen-requests.approve');

    Route::patch('reopen-requests/{reopenRequest}/reject', [TripReopenReviewController::class, 'reject'])
        ->middleware('can:review,reopenRequest')
        ->name('client.reopen-requests.reject');

    /* ---------- Advisor/Admin: confirm / reject chuyến ---------- */
    Route::patch('trips/{trip}/confirm', [TripReviewController::class, 'confirm'])
        ->middleware('can:review,trip')
        ->name('client.trips.confirm');

    Route::patch('trips/{trip}/reject', [TripReviewController::class, 'reject'])
        ->middleware('can:review,trip')
        ->name('client.trips.reject');
    
});

// Route::middleware(['auth', 'role:driver|admin'])->group(function () {
//     Route::post('trips', [ClientTripController::class, 'store'])
//         ->middleware('can:create,App\Models\Trip')
//         ->name('client.trips.store');
// });

Route::prefix('/admin')->middleware(['auth', 'role:admin|editor'])->group(function () {
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
    
    Route::patch('/trips/{trip}/confirm', [TripReviewController::class, 'confirm'])->name('admin.trips.confirm');
    Route::patch('/trips/{trip}/reject', [TripReviewController::class, 'reject'])->name('admin.trips.reject');

    Route::patch('/reopen-requests/{reopenRequest}/approve', [TripReopenReviewController::class, 'approve'])
        ->name('admin.reopen-requests.approve');
    Route::patch('/reopen-requests/{reopenRequest}/reject', [TripReopenReviewController::class, 'reject'])
        ->name('admin.reopen-requests.reject');
    

});



    
require __DIR__.'/settings.php';
