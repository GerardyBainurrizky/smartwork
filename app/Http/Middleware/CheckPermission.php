<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if (! $request->user()) {
            abort(403, 'Unauthorized.');
        }

        if (! $request->user()->can($permission)) {
            abort(403, 'Anda tidak memiliki izin untuk aksi ini.');
        }

        return $next($request);
    }
}