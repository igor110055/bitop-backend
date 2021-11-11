<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnnouncementRead extends Model
{
    use Traits\DateTimeTrait;
    const DATE_FORMAT = 'Uv';
    const UPDATED_AT = null;

    protected $dateFormat = self::DATE_FORMAT;

    protected $fillable = [
        'user_id',
        'announcement_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
