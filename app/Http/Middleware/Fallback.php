<?php

namespace App\Http\Middleware;

use Closure;

use Illuminate\Http\Request;

class Fallback
{
    public function handle($request, Closure $next)
    {
        if ($this->rewrite($request)) {
            return response(view('index', [
                'config' => [
                    'hostname' => config('app.url'),
                    'siteName' => config('app.name'),
                    'supportedCoins' => array_keys(config('coin')),
                    'supportedCurrencies' => config('core.currency.all'),
                    'supportedIcons' => array_map(function ($c) {
                        return $c['icon'];
                    }, config('coin')),
                    'walletNetwork' => config('services.wallet.env'),
                    'hCaptchaKey' => config('services.captcha.key'),
                ]
            ]));
        }
        return $next($request);
    }

    protected function rewrite(Request $request)
    {
        $path = $request->path();
        if (!$request->isMethod('get') or
            $request->is('api/*') or
            $request->is('admin') or
            $request->is('admin/*') or
            strpos($path, '.') !== false) {
            return false;
        }

        # TODO check for the accept header
        # if ($request->headers->get('accept'))

        return true;
    }
}
