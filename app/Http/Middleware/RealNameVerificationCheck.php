<?php

namespace App\Http\Middleware;

use Closure;
use App\Exceptions\Auth\RealNameVerificationError;

class RealNameVerificationCheck
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
        if (!\Auth::user()->is_verified) {
            throw new RealNameVerificationError;
        }
        return $next($request);
    }
}
