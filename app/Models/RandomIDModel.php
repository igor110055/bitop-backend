<?php

namespace App\Models;

abstract class RandomIDModel extends Model
{
    const ID_SIZE = 14;
    use Traits\RandomIDTrait;

    public $incrementing = false;
    protected $dataFormat = Model::DATE_FORMAT;
    protected $keyType = 'string';
}
