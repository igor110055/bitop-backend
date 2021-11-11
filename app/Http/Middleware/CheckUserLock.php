<?php

namespace App\Http\Middleware;

use Closure;
use App\Repos\Interfaces\UserRepo;
use App\Exceptions\Auth\UserLoginLockError;

class CheckUserLock
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
        $user = \Auth::user();
        $repo = app()->make(UserRepo::class);
        if ($repo->checkUserLock($user)) {
            throw new UserLoginLockError;
        }
        return $next($request);
    }
}
