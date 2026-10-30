<?php

use App\Http\Controllers\Admin\AccessControlController;
use App\Http\Controllers\Admin\CarController;
use App\Http\Controllers\Admin\CloudinaryUploadController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ExpenseController;
use App\Http\Controllers\Admin\TripController;
use App\Http\Controllers\Admin\TripExportController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Client\ClientCloudinaryUploadController;
use App\Http\Controllers\Client\ClientTripController;
use App\Http\Controllers\Client\HomeController;
use App\Http\Controllers\Client\TripReopenRequestController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\TripReopenReviewController;
use App\Http\Controllers\TripReviewController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:driver|advisor|admin|editor|manager'])->group(function () {
    Route::get('notifications/more', [NotificationController::class, 'more'])
        ->name('notifications.more');
    Route::patch('notifications/read-all', [NotificationController::class, 'markAllRead'])
        ->name('notifications.read-all');
    Route::patch('notifications/{notification}/read', [NotificationController::class, 'markRead'])
        ->whereUuid('notification')
        ->name('notifications.read');

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
    Route::post('trips/bulk-review', [TripReviewController::class, 'bulkReview'])
        ->name('client.trips.bulk-review');

});

Route::prefix('/admin')->middleware(['auth', 'role:admin|manager|editor'])->name('admin.')
    ->group(function () {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');

        /* ---------- Users CRUD ---------- */
        Route::get('users', [UserController::class, 'index'])
            ->middleware('can:viewAny,App\Models\User')
            ->name('users.index');
        Route::post('users', [UserController::class, 'store'])
            ->middleware('can:create,App\Models\User')
            ->name('users.store');
        Route::put('users/{user}', [UserController::class, 'update'])
            ->middleware('can:update,user')
            ->name('users.update');
        Route::delete('users/{user}', [UserController::class, 'destroy'])
            ->middleware('can:delete,user')
            ->name('users.destroy');
        /* ---------- Hành động nhanh ---------- */
        // ĐẶT TRƯỚC route có {user} nếu có thêm segment tĩnh sau này
        Route::post('users/bulk', [UserController::class, 'bulk'])
            ->middleware('throttle:20,1')
            ->name('users.bulk');

        Route::patch('users/{user}/toggle-active', [UserController::class, 'toggleActive'])
            ->middleware('can:toggleActive,user')
            ->name('users.toggle-active');

        Route::patch('users/{user}/verify-email', [UserController::class, 'verifyEmail'])
            ->middleware('can:verifyEmail,user')
            ->name('users.verify-email');

        Route::post('roles', [AccessControlController::class, 'storeRole'])
            ->middleware('can:manageAccessControl,App\Models\User')
            ->name('roles.store');
        Route::put('roles/{role}', [AccessControlController::class, 'updateRole'])
            ->middleware('can:manageAccessControl,App\Models\User')
            ->name('roles.update');
        Route::delete('roles/{role}', [AccessControlController::class, 'destroyRole'])
            ->middleware('can:manageAccessControl,App\Models\User')
            ->name('roles.destroy');

        Route::post('permissions', [AccessControlController::class, 'storePermission'])
            ->middleware('can:manageAccessControl,App\Models\User')
            ->name('permissions.store');
        Route::put('permissions/{permission}', [AccessControlController::class, 'updatePermission'])
            ->middleware('can:manageAccessControl,App\Models\User')
            ->name('permissions.update');
        Route::delete('permissions/{permission}', [AccessControlController::class, 'destroyPermission'])
            ->middleware('can:manageAccessControl,App\Models\User')
            ->name('permissions.destroy');

        /* ---------- Cars CRUD ---------- */
        Route::get('cars', [CarController::class, 'index'])->name('cars.index');
        Route::post('cars', [CarController::class, 'store'])->name('cars.store');
        Route::put('cars/{car}', [CarController::class, 'update'])->name('cars.update');
        Route::delete('cars/{car}', [CarController::class, 'destroy'])->name('cars.destroy');
        Route::post('cars/bulk', [CarController::class, 'bulk'])
            ->middleware('throttle:20,1')
            ->name('cars.bulk');
        Route::patch('cars/{car}/toggle-active', [CarController::class, 'toggleActive'])
            ->name('cars.toggle-active');

        /* ---------- Expenses CRUD ---------- */
        Route::get('expenses', [ExpenseController::class, 'index'])
            ->middleware('can:viewAny,App\Models\Expense')
            ->name('expenses.index');
        Route::post('expenses', [ExpenseController::class, 'store'])
            ->middleware('can:create,App\Models\Expense')
            ->name('expenses.store');
        Route::put('expenses/{expense}', [ExpenseController::class, 'update'])
            ->middleware('can:update,expense')
            ->name('expenses.update');
        Route::delete('expenses/{expense}', [ExpenseController::class, 'destroy'])
            ->middleware('can:delete,expense')
            ->name('expenses.destroy');
        Route::post('expenses/bulk', [ExpenseController::class, 'bulk'])
            ->middleware('throttle:20,1')
            ->name('expenses.bulk');
        Route::patch('expenses/{expense}/toggle-active', [ExpenseController::class, 'toggleActive'])
            ->middleware('can:toggleActive,expense')
            ->name('expenses.toggle-active');

        // trip routes
        Route::get('trips/cloudinary-signature', [CloudinaryUploadController::class, 'signature'])
            ->name('trips.cloudinary-signature');
        Route::delete('trips/uploaded-image', [CloudinaryUploadController::class, 'discard'])
            ->name('trips.uploaded-image.discard');

        Route::get('/trips', [TripController::class, 'index'])->name('trips.index');
        Route::post('/trips', [TripController::class, 'store'])->name('trips.store');
        Route::put('/trips/{trip}', [TripController::class, 'update'])->name('trips.update');
        Route::delete('/trips/{trip}', [TripController::class, 'destroy'])->name('trips.destroy');
        Route::get('/trips/export', [TripExportController::class, 'index'])->name('trips.export.index');
        Route::get('/trips/export/download', [TripExportController::class, 'export'])->name('trips.export.download');

        Route::patch('/trips/{trip}/confirm', [TripReviewController::class, 'confirm'])->name('trips.confirm');
        Route::patch('/trips/{trip}/reject', [TripReviewController::class, 'reject'])->name('trips.reject');
        Route::post('/trips/bulk-review', [TripReviewController::class, 'bulkReview'])->name('trips.bulk-review');

        Route::patch('/reopen-requests/{reopenRequest}/approve', [TripReopenReviewController::class, 'approve'])
            ->name('reopen-requests.approve');
        Route::patch('/reopen-requests/{reopenRequest}/reject', [TripReopenReviewController::class, 'reject'])
            ->name('reopen-requests.reject');

    });

Route::post('/locale', function (Request $request) {
    $locale = $request->input('locale');
    $locale = in_array($locale, ['vi', 'en', 'th'], true) ? $locale : 'vi';

    session(['locale' => $locale]);

    return back();
})->name('locale.switch');

require __DIR__.'/settings.php';
