<?php

namespace App\Http\Middleware;

use App\Enums\UserType;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsSuperAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next) : Response
    {
        if (!$request->user()->type->is(UserType::SUPER_ADMIN)) {
            return app(\App\Http\Controllers\Auth\LoginController::class)->logout($request);
        }

        return $next($request);
    }
}
