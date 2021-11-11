<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;

class Announcement extends UuidModel
{
    use SoftDeletes;

    const STATUS_ANNOUNCED = 'announced';
    const STATUS_PENDING = 'pending';
    const STATUS_CANCELED = 'canceled';

    protected $fillable = [
        'title',
        'released_at',
        'pin_end_at',
        'deleted_at',
    ];

    protected $dates = [
        'released_at',
        'pin_end_at',
    ];

    public function announcement_contents()
    {
        return $this->hasMany(AnnouncementContent::class);
    }

    public function getStatusAttribute()
    {
        if ($this->deleted_at) {
            return self::STATUS_CANCELED;
        } else {
            return (Carbon::now() > $this->released_at)
                ? self::STATUS_ANNOUNCED
                : self::STATUS_PENDING;
        }
    }
}
