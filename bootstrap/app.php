<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

use Illuminate\Auth\Access\AuthorizationException;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;


return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
    })
    // ->withExceptions(function (Exceptions $exceptions): void {
    //     $exceptions->shouldRenderJsonWhen(
    //         fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
    //     );
    ->withExceptions(function (Exceptions $exceptions) {

        // Không log rác các lỗi phân quyền
        $exceptions->dontReport([
            AuthorizationException::class,
        ]);

        $exceptions->respond(function (Response $response, Throwable $e, Request $request) {

            $status = $response->getStatusCode();

            /* ---------- API / fetch thuần: trả JSON ---------- */
            if (! $request->header('X-Inertia') && $request->expectsJson()) {
                if ($status === 403) {
                    return response()->json([
                        'message' => authorizationMessage($e),
                    ], 403);
                }

                return $response;
            }

            /* ---------- Chỉ can thiệp request từ Inertia ---------- */
            if (! $request->header('X-Inertia')) {
                return $response;
            }

            /* ---------- 403: quay lại trang cũ + flash error ---------- */
            if ($status === 403) {
                return backSafely($request, 'error', authorizationMessage($e));
            }

            /* ---------- 419: CSRF / session hết hạn ---------- */
            if ($status === 419) {
                return backSafely(
                    $request,
                    'error',
                    'Phiên làm việc đã hết hạn. Vui lòng tải lại trang và thử lại.'
                );
            }

            /* ---------- 409: xung đột trạng thái (race condition) ---------- */
            if ($status === 409) {
                return backSafely(
                    $request,
                    'warning',
                    $e->getMessage() ?: 'Dữ liệu vừa thay đổi, vui lòng tải lại trang.'
                );
            }

            /* ---------- 429: quá nhiều request ---------- */
            if ($status === 429) {
                return backSafely($request, 'warning', 'Bạn thao tác quá nhanh. Vui lòng thử lại sau ít phút.');
            }

            /* ---------- 404 / 500 / 503: render trang lỗi Inertia ---------- */
            if (in_array($status, [404, 500, 503], true) && ! app()->environment('local')) {
                return Inertia::render('Error', [
                    'status'  => $status,
                    'message' => match ($status) {
                        404     => 'Không tìm thấy nội dung bạn yêu cầu.',
                        503     => 'Hệ thống đang bảo trì. Vui lòng quay lại sau.',
                        default => 'Đã có lỗi xảy ra. Vui lòng thử lại.',
                    },
                ])->toResponse($request)->setStatusCode($status);
            }

            return $response;
        });
    })
    ->create();
