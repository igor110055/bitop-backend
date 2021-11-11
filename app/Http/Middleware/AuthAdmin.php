<?php

namespace App\Http\Middleware;

use Closure;

use Illuminate\Auth\{
    Access\AuthorizationException,
    AuthenticationException,
};

class AuthAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (!\Auth::guard('web')->user()->is_admin) {
            throw new AuthorizationException;
        }
        return $next($request);
    }
}
