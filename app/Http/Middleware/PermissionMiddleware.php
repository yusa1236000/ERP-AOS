<?php
// app/Http/Middleware/PermissionMiddleware.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $permissions
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ...$permissions)
    {
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Unauthenticated',
                'error' => 'You must be logged in to access this resource'
            ], 401);
        }

        $user = Auth::user();

        // Super admin bypasses all permission checks
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Check if user has any of the required permissions
        if (!$user->hasAnyPermission($permissions)) {
            return response()->json([
                'message' => 'Forbidden',
                'error' => 'You do not have the required permission to access this resource',
                'required_permissions' => $permissions,
                'user_permissions' => $user->getPermissionNames()
            ], 403);
        }

        return $next($request);
    }
}
