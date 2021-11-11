<?php

namespace App\Exceptions;

use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use App\Exceptions\{
    Core\InternalServerError,
    ServiceUnavailableError,
};

class ApiExceptionHandler
{
    const REPORTABLE_EXCEPTIONS = [
        ServiceUnavailableError::class,
    ];

    const STATUSES = [
        ValidationException::class     => 422,
        AuthenticationException::class => 401,
        ModelNotFoundException::class  => 404,
        AuthorizationException::class  => 403,
        QueryException::class          => 500,
    ];

    public function handle(\Throwable $e)
    {
        list($status, $body) = static::getStatusCodeAndBody($e);
        return response()->json($body, $status);
    }

    public static function getStatusCodeAndBody(\Throwable $e)
    {
        $res = [];
        $status = static::getStatusCode($e);
        $class = get_class($e);
        if (!config('app.debug') and ($status >= 500) and !in_array($class, self::REPORTABLE_EXCEPTIONS)) {
            $e = new InternalServerError;
            $status = static::getStatusCode($e);
            $class = get_class($e);
        }
        $res['status_code'] = $status;
        $res['class'] = static::getExceptionClass($class);
        $res['message'] = $res['class'];

        if (config('app.debug')) {
            $res['message'] = $e->getMessage() ?: $res['class'];

            if ($e instanceof ValidationException) {
                $res['errors'] = $e->errors();
            }

            if (($e instanceof \App\Exceptions\Error) or ($e instanceof \App\Exceptions\Exception)) {
                if (method_exists($e, 'errors') and !empty($e->errors())) {
                    $res['errors'] = $e->errors();
                }
            }

            $res['debug'] = [
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'class' => $class,
                'trace' => $e->getTrace(),
            ];
        }

        # TODO log the exceptions!
        return [$status, $res];
    }

    protected static function getStatusCode(\Throwable $e)
    {
        return ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface)
            ? $e->getStatusCode()
            : data_get(self::STATUSES, get_class($e), 500);
    }

    protected static function getExceptionClass(string $class): string
    {
        if (strpos($class, 'Illuminate') === 0) { # laravel class
            $parts = explode('\\', $class);
            return end($parts);
        }
        $prefixes = [
            'Symfony\\Component\\HttpKernel\\Exception\\',
            'App\\Exceptions\\',
        ];
        foreach ($prefixes as $prefix) {
            if (strpos($class, $prefix) === 0) {
                return substr($class, strlen($prefix));
            }
        }
        return $class;
    }
}
