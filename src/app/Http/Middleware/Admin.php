<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request; // ← これだけを使う
use Symfony\Component\HttpFoundation\Response;

class Admin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user  = $request->user();
        $attrs = $user?->getAttributes() ?? [];

        $isAdmin = (int)($attrs['is_admin'] ?? 0) === 1
                || (string)($attrs['role'] ?? '') === 'admin';

        if (! $isAdmin) {
            abort(403);
        }

        return $next($request);
    }
}
