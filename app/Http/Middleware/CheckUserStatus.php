<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserStatus
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && ! $request->user()->isActive()) {
            auth()->logout();

            return redirect()->route('login')
                ->withErrors(['username' => 'Akun Anda telah dinonaktifkan. Hubungi administrator.']);
        }

        return $next($request);
    }
}