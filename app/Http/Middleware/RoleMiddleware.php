<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = $request->user();

        if (!$user || !$user->isActive()) {
            return response()->json(['message' => 'Tài khoản bị khóa hoặc chưa kích hoạt'], 403);
        }

        foreach ($roles as $role) {
            if ($user->role === $role) {
                return $next($request);
            }
        }

        return response()->json(['message' => 'Không có quyền truy cập'], 403);
    }
}