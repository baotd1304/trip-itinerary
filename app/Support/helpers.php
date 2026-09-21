<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;

if (! function_exists('authorizationMessage')) {
    /**
     * Lấy message thật từ Response::deny('...') trong Policy.
     * Laravel bọc AuthorizationException trong AccessDeniedHttpException,
     * nên phải soi cả getPrevious().
     */
    function authorizationMessage(Throwable $e, string $fallback = 'Bạn không có quyền thực hiện thao tác này.'): string
    {
        $authException = $e instanceof AuthorizationException
            ? $e
            : ($e->getPrevious() instanceof AuthorizationException ? $e->getPrevious() : null);

        $message = $authException?->getMessage() ?? $e->getMessage();

        // Bỏ qua message mặc định vô nghĩa của framework
        $generic = ['This action is unauthorized.', 'Forbidden', '', 'Unauthorized'];

        return in_array(trim($message), $generic, true) ? $fallback : $message;
    }
}

if (! function_exists('backSafely')) {
    /**
     * redirect()->back() an toàn:
     *  - trả 303 cho Inertia (bắt buộc với PUT/PATCH/DELETE)
     *  - tránh redirect loop khi chính trang hiện tại bị 403
     */
    function backSafely(Request $request, string $level, string $message): Illuminate\Http\RedirectResponse
    {
        $previous = url()->previous();
        $fallback = route('home');

        // Nếu trang trước cũng chính là URL vừa bị chặn -> về trang chủ
        $target = ($previous === $request->fullUrl() || ! $previous) ? $fallback : $previous;

        return redirect()->to($target, 303)->with($level, $message);
    }
}