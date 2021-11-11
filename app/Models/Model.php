<?php

namespace App\Models;

class Model extends \Illuminate\Database\Eloquent\Model
{
    const TYPE_SIZE = 32;
    const POLYMORPHIC_TYPE_SIZE = 256;
    const DATE_FORMAT = 'Uv';

    use Traits\DateTimeTrait;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $dateFormat = self::DATE_FORMAT;
}