<?php
namespace App\Models;

class Bank extends UuidModel
{
    protected $dataFormat = Model::DATE_FORMAT;

    protected $fillable = [
        'nationality',
        'name',
        'phonetic_name',
        'foreign_name',
        'swift_id',
        'local_code',
        'is_active',
    ];

    protected $casts = [
        'foreign_name' => 'array',
        'is_active' => 'boolean',
    ];

    protected $hidden = [
        'is_active',
    ];
}
