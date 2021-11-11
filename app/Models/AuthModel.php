<?php

namespace App\Models;

abstract class AuthModel extends \Illuminate\Foundation\Auth\User implements \Tymon\JWTAuth\Contracts\JWTSubject
{
    const DATE_FORMAT = 'Uv';
    const ID_SIZE = 14;

    use Traits\DateTimeTrait;
    use Traits\RandomIDTrait;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $dateFormat = self::DATE_FORMAT;

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }
}
