<?php

use Carbon\Carbon;
use Dec\Dec;
use Illuminate\Support\Facades\Log;
use App\Exceptions\Core\WrongRequestHeaderError;
use App\Models\{
    Verification,
    Invitation,
    DeviceToken,
};

if (!function_exists('generate_code')) {
    function generate_code(int $length = 6, $type = Verification::CODE_TYPE_DIGIT)
    {
        $map = [
            Verification::CODE_TYPE_DIGIT => '0123456789',
            Verification::CODE_TYPE_LOWER => 'abcdefghijklmnopqrstuvwxyz',
            Verification::CODE_TYPE_UPPER => 'ABCEFGHIJKLMNOPRSTUVWXYZ',
            Verification::CODE_TYPE_ALPHA => 'abcdefghijkmnopqrstuvwxyzABCEFGHJKLMNOPRSTUVWXYZ',
            Verification::CODE_TYPE_DIGIT_LOWER => '023456789abcdefghijkmnopqrstuvwxyz',
            Verification::CODE_TYPE_DIGIT_UPPER => '3479ACEFHJKLMNPRTUVWXY',
            Verification::CODE_TYPE_DIGIT_ALPHA => '3479abcdefghijkmnopqrstuvwxyzACEFHJKLMNPRTUVWXY',
            Invitation::CODE_TYPE_DIGIT_ALL => '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ',
        ];

        if ($length < 0 or $length > 64) {
            throw new \Exception("length {$length} out of range.");
        }
        if (!array_key_exists($type, $map)) {
            throw new \Exception("code type {$type} not supported.");
        }
        $chars = $map[$type];
        $code = '';
        $min = 0;
        $max = strlen($chars) - 1;
        for ($i = 0; $i < $length; ++$i) {
            $code .= $chars[random_int($min, $max)];
        }
        return $code;
    }
}

if (!function_exists('trim_zeros')) {
    function trim_zeros(string $str) {
        if (strpos($str, '.') !== false) {
            $str = rtrim($str, '0');
        }
        return rtrim($str, '.') ?: '0';
    }
}

if (!function_exists('hide_beta_coins')) {
    function hide_beta_coins($user, array $coins) {
        foreach ($coins as $coin => $values) {
            if (data_get($values, 'beta')) {
                if (optional($user)->is_tester !== true) {
                    unset($coins[$coin]);
                }
            }
        }
        return $coins;
    }
}

if (!function_exists('currency_trim_redundant_decimal')) {
    function currency_trim_redundant_decimal($amount, string $currency) {
        $default_decimal = config('core.currency.default_exp');
        $decimal = config("currency.{$currency}.decimal") ?? $default_decimal;
        return (string) Dec::create($amount)->floor($decimal);
    }
}

if (!function_exists('trim_redundant_decimal')) {
    function trim_redundant_decimal($amount, string $coin) {
        $default_decimal = config('core.coin.default_exp');
        $coin_decimal = config("coin.{$coin}.decimal") ?? $default_decimal;
        return (string) Dec::create($amount)->floor(min($coin_decimal, $default_decimal));
    }
}

if (!function_exists('formatted_coin_amount')) {
    function formatted_coin_amount(string $amount, $coin = null) {
        $decimal = $coin ? config("coin.{$coin}.decimal") : config('core.coin.default_exp');
        return (string)bcdiv($amount, 1, $decimal);
    }
}

if (!function_exists('formatted_price')) {
    function formatted_price(string $price) {
        $scale = config("core.currency.scale");
        return sprintf("%.{$scale}f", $price);      # Normalize the string to 2 decimal points
    }
}

if (!function_exists('clamp_query')) {
    function clamp_query($value, $min, $max)
    {
        if (!is_numeric($value)) {
            return $min;
        }
        return min(max((int)$value, $min), $max);
    }
}

if (! function_exists('user_log')) {
    function user_log($message, array $context = [], $request = null)
    {
        if ($request) {
            if ($request->headers->has('X-PLATFORM') and
                $request->headers->has('X-SERVICE') and
                $request->headers->has('X-DEVICE-TOKEN')
            ) {
                if (in_array($request->header('X-PLATFORM'), DeviceToken::PLATFORMS) and
                    in_array($request->header('X-SERVICE'), DeviceToken::SERVICES)
                ) {
                    $context['platform'] = $request->header('X-PLATFORM');
                    $context['service'] = $request->header('X-SERVICE');
                    $context['agent'] = $request->header('X-DEVICE-TOKEN');
                } else {
                    throw new WrongRequestHeaderError;
                }
            } else {
                $context['platform'] = DeviceToken::PLATFORM_WEB;
                $context['agent'] = $request->header('User-Agent');
            }
        }

        Log::channel('userlog')->info($message, $context);
    }
}

/*
*  Get arraySum of decimal numbers
*
* @param $inputs Array of numbers to be summed up
* @param $scale Scale of decimal value to be returned
* @param $returnDecimal Return a Decimal instance if true, return string otherwise
*
*/
if (! function_exists('dec_array_sum')) {
    function dec_array_sum(array $inputs): string
    {
        $sum = Dec::create(0);
        foreach ($inputs as $input) {
            $sum = $sum->add($input);
        }
        return (string)$sum;
    }
}

if (! function_exists('array_keys_exists')) {
    function array_keys_exists(array $keys, array $array) : bool
    {
        return !array_diff_key(array_flip($keys), $array);
    }
}

if (! function_exists('array_keys_not_null')) {
    function array_keys_not_null(array $keys, array $array) : bool
    {
        foreach ($keys as $key) {
            if (is_null(data_get($array, $key))) {
                return false;
            }
        }
        return true;
    }
}

if (!function_exists('today_and_tomorrow')) {
    function today_and_tomorrow(Carbon $now = null) : array
    {
        $timezone = config('core.timezone.default');
        if ($now) {
            $today = $now->copy();
            $today->setTimezone($timezone);
            $today->setTime(0, 0, 0);
        } else {
            $today = Carbon::today($timezone);
        }
        $tomorrow = $today->copy()->addDay();
        return [$today, $tomorrow];
    }
}

if (!function_exists('date_ticks')) {
    function date_ticks($from_date, $to_date) : array
    {
        $timezone = config('core.timezone.default');
        $date = Carbon::parse($from_date, $timezone);
        $to_date = Carbon::parse($to_date, $timezone);
        if ($to_date->lt($date)) {
            return [];
        }
        $dates = [];
        while ($date->lte($to_date)) {
            $dates[] = $date->toDateString();
            $date->addDay();
        }
        return $dates;
    }
}

if (!function_exists('date_ticks_for_chart')) {
    function date_ticks_for_chart($from_date, $to_date) : array
    {
        $date_ticks = date_ticks($from_date, $to_date);
        $result = [];
        foreach ($date_ticks as $index => $date) {
            $result[$index] = [$index, $date];
        }
        return $result;
    }
}

if (!function_exists('get_color_class')) {
    function get_color_class($index = null)
    {
        $classes = [
            'blue',
            'lime',
            'cyan',
            'blue-gray',
            'teal',
            'amber',
            'purple',
            'red',
            'orange',
            'brown',
            'grey',
            'indigo',
            'light-blue',
            'pink',
            'deep-purple',
            'deep-orange',
            'yellow',
            'green',
        ];

        $coins = config('core.coin.all');
        $currencies = config('core.currency.all');

        if (is_null($index)) {
            return $classes;
        }
        if (in_array($index, $coins)) {
            return $classes[array_search($index, $coins)];
        }
        if (in_array($index, $currencies)) {
            return $classes[array_search($index, $currencies)];
        }
        return $classes[0];
    }
}

if (!function_exists('millitime')) {
    /**
     * Return current Unix timestamp in milliseconds
     *
     * @return int
     */
    function millitime(DateTimeInterface $time = null): int
    {
        return intval(($time ?: Carbon::now())->format('Uv'));
    }
}

if (!function_exists('validate_amount')) {
    /**
     * Validate if value is valid amount such as 1, 1.23, 0.12345 ...
     *
     * @return bool
     */
    function validate_amount($value, $max_decimals = null): bool
    {
        if (is_null($max_decimals)) {
            return preg_match('/^[0-9]+(\.[0-9]+)?$/', $value);
        }
        return preg_match("/^[0-9]+(\.[0-9]{1,$max_decimals})?$/", $value);
    }
}

if (!function_exists('datetime')) {
    function datetime($dt, $set_timezone = true, $timezone = null, $method = 'toDateTimeString')
    {
        if (!$dt instanceof Carbon) {
            $dt = Carbon::parse($dt);
        }
        if ($set_timezone) {
            $tz = $timzone ?? config('core.timezone.default');
            $dt->timezone($tz);
        }
        return $dt->toDateTimeString();
    }
}

if (!function_exists('comma_format')) {
    function comma_format(string $currency)
    {
        if (preg_match('/^(?:[-+]?[1-9][0-9]{3,})$/', $currency)) {
            $start = strlen($currency) - 3;
        } elseif (preg_match('/^(?:[-+]?[1-9][0-9]{3,}\.[0-9]*)$/', $currency)) {
            $start = strpos($currency, '.') - 3;
        } else {
            return $currency;
        }
        for ($i = $start; $i > 0; $i -= 3) {
            $currency = substr_replace($currency, ',', $i, 0);
        }
        return $currency;
    }
}

if (!function_exists('request_ip')) {
    function request_ip()
    {
        return data_get($_SERVER, 'HTTP_X_FORWARDED_FOR', data_get($_SERVER, 'REMOTE_ADDR'));
    }
}
