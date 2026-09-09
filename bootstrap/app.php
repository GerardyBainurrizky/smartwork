<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_FOR |
                Request::HEADER_X_FORWARDED_HOST |
                Request::HEADER_X_FORWARDED_PORT |
                Request::HEADER_X_FORWARDED_PROTO |
                Request::HEADER_X_FORWARDED_PREFIX |
                Request::HEADER_X_FORWARDED_AWS_ELB,
        );

        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
            'permission' => \App\Http\Middleware\CheckPermission::class,
            'user.status' => \App\Http\Middleware\CheckUserStatus::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\CheckUserStatus::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, Request $request) {
            if ($e->getStatusCode() === 419) {
                $isLogoutRequest = $request->is('logout')
                    || $request->is('*/logout')
                    || $request->routeIs('logout')
                    || strtolower(trim((string) $request->path(), '/')) === 'logout';

                if (\Illuminate\Support\Facades\Auth::guard('web')->check()) {
                    \Illuminate\Support\Facades\Auth::guard('web')->logout();
                }

                if ($request->hasSession()) {
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();
                }

                if ($isLogoutRequest) {
                    return redirect()->route('login');
                }

                if (! $request->expectsJson()) {
                    return redirect()->route('login')->with('status', 'Sesi Anda telah berakhir. Silakan masuk kembali.');
                }
            }
        });

        $exceptions->render(function (\Illuminate\Session\TokenMismatchException $e, Request $request) {
            $isLogoutRequest = $request->is('logout')
                || $request->is('*/logout')
                || $request->routeIs('logout')
                || strtolower(trim((string) $request->path(), '/')) === 'logout';

            if (\Illuminate\Support\Facades\Auth::guard('web')->check()) {
                \Illuminate\Support\Facades\Auth::guard('web')->logout();
            }

            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            if ($isLogoutRequest) {
                return redirect()->route('login');
            }

            if (! $request->expectsJson()) {
                return redirect()->route('login')->with('status', 'Sesi Anda telah berakhir. Silakan masuk kembali.');
            }
        });
    })->create();