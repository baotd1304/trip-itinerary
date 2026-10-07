<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Middleware;
use Inertia\Inertia;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $flash = [
            'success' => $request->session()->get('success'),
            'error'   => $request->session()->get('error'),
            'warning' => $request->session()->get('warning'),
            'info'    => $request->session()->get('info'),
        ];

        $hasMessage = collect($flash)->filter()->isNotEmpty();  
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    // Ép về array để đảm bảo bên Vue nhận được mảng thuần
                    'roles'       => $request->user()->getRoleNames()->values()->toArray(),
                    'permissions' => $request->user()->getAllPermissions()->pluck('name')->values()->toArray(),
                    // Hoặc share cả hai dạng
                    'can' => $request->user()->getAllPermissions()->pluck('name')
                        ->mapWithKeys(fn($permission) => [$permission => true])
                        ->toArray(),
                ] : null,
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
             // always() => LUÔN có mặt trong mọi response, kể cả partial reload.
            // Khi không có message, flash.id = null => FE bỏ qua hoàn toàn.
            // 'flash' => Inertia::always([
            //     'id'      => $hasMessage ? (string) Str::uuid() : null,
            //     'success' => fn () => $request->session()->get('success'),
            //     'error'   => fn () => $request->session()->get('error'),
            //     'warning' => fn () => $request->session()->get('warning'),
            //     'info'    => fn () => $request->session()->get('info'),

            //     'type' => fn () => session('flash.type'),
            //     'key' => fn () => session('flash.key'),
            //     'params' => fn () => session('flash.params', []),
            // ])
            
            // LUÔN gửi (kể cả partial reload) nhưng sẽ là null nếu không có gì
            'flash' => Inertia::always(fn () => $this->flashPayload($request)),
        ];
    }

    /**
     * Trả về null khi không có message.
     * Khi có: { id, messages: [{ type, key, message, params }] }
     */
    private function flashPayload(Request $request): ?array
    {
        if (! $request->hasSession()) {
            return null;
        }

        $session  = $request->session();
        $messages = [];

        /* ----- 1) Dạng chuẩn mới: ->with('flash', Flash::success('key', [...])) ----- */
        foreach ((array) $session->get('flash', []) as $item) {
            if (! is_array($item)) {
                continue;
            }

            $messages[] = [
                'type'    => in_array($item['type'] ?? '', ['success', 'error', 'warning', 'info'], true)
                    ? $item['type']
                    : 'success',
                'key'     => $item['key']     ?? null,
                'message' => $item['message'] ?? null,   // text thô (nếu có)
                'params'  => (object) ($item['params'] ?? []),
            ];
        }

        /* ----- 2) Tương thích ngược: ->with('success', 'text thô') ----- */
        foreach (['success', 'error', 'warning', 'info'] as $type) {
            $raw = $session->get($type);

            if (filled($raw) && is_string($raw)) {
                $messages[] = [
                    'type'    => $type,
                    'key'     => null,
                    'message' => $raw,
                    'params'  => (object) [],
                ];
            }
        }

        if (empty($messages)) {
            return null;
        }

        /* ----- 3) QUAN TRỌNG: tiêu thụ ngay để partial reload kế tiếp không nhận lại ----- */
        $session->forget(['flash', 'success', 'error', 'warning', 'info']);

        return [
            'id'       => (string) Str::uuid(),
            'messages' => array_values($messages),
        ];
    }

}
