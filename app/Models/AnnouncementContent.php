<?php

namespace App\Models;

class AnnouncementContent extends UuidModel
{
    const LOCALE_EN = 'en';
    const LOCALE_ZHTW = 'zh-tw';
    const LOCALE_ZHCN = 'zh-cn';

    protected $fillable = [
        'announcement_id',
        'locale',
        'title',
        'content',
    ];

    public function announcement()
    {
        return $this->belongsTo(Announcement::class);
    }
}
