<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle($request, Closure $next, $permission)
    {
        $user = $request->user();

        // If user has permission -> allow
        if ($user && $user->hasPermission($permission)) {
            return $next($request);
        }

        // Allow self-update
        $routeId = $request->route('id');

        if ($routeId && $user && $user->id == $routeId) {
            return $next($request);
        }

        return response()->json([
            'status' => false,
            'message' => 'Unauthorized',
            'data' => null
        ], 403);
    }
}
