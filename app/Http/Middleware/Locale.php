<?php

namespace App\Http\Middleware;

use Closure;
use App;
use Illuminate\Support\Arr;

class Locale
{
    public function handle($request, Closure $next)
    {
        $locales = config('core.locale.all');
        if ($locale = $request->header('X-LOCALE')) {
            $locale = strtolower($locale);
            $this->setLocale($locales, [$locale]);
        } elseif ($al = $request->header('accept-language')) {
            $this->setLocale($locales, $this->parse($al));
        }
        return $next($request);
    }

    protected function setLocale(array $locales, array $langs)
    {
        foreach ($langs as $lang) {
            if (Arr::has($locales, $lang)) {
                App::setLocale($locales[$lang]);
                return;
            }
            list($lang) = explode('-', $lang);
            if (Arr::has($locales, $lang)) {
                App::setLocale($locales[$lang]);
                return;
            }
        }
        App::setLocale('en');
    }

    protected function parse(string $str = null)
    {
        $prefers = [];
        $weights = [];
        foreach (explode(',', preg_replace('/ /', '', $str)) as $lang) {
            # add suffix ';' to shut up list() for E_NOTICE
            list($lang, $weight) = explode(';', strtolower($lang).';');
            if ($lang and !$weight) {
                $prefers[] = $lang;
            } elseif ($lang and $weight) {
                # add suffix '=' to shut up list() for E_NOTICE
                list($q, $score) = explode('=', $weight.'=');
                if ($q === 'q' and is_numeric($score)) {
                    $weights[] = [$lang, (float)$score];
                } else {
                    # unknown format
                }
            } else {
                # unknown format
            }
        }
        usort($weights, function ($a, $b) {
            return $b[1] <=> $a[1];
        });
        return array_merge($prefers, array_map(function ($item) {
            return $item[0];
        }, $weights));
    }
}
