<?php

namespace App\Models;

abstract class UuidModel extends Model
{
    use Traits\UuidAsPrimaryTrait;

    public $incrementing = false;
    protected $dataFormat = Model::DATE_FORMAT;
    protected $keyType = 'string';
}
