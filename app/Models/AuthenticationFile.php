<?php

namespace App\Models;

use Storage;

class AuthenticationFile extends UuidModel
{
    protected $fillable = [
        'user_id',
        'authentication_id',
        'url',
    ];

    public function authentication()
    {
        return $this->belongsTo(Authentication::class);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getLinkAttribute()
    {
        $pre_path = config('core')['aws_cloud_storage']['user_authentication']['pre_path_name'];
        $path = $pre_path.'/'.$this->url;
        return Storage::disk('s3')->temporaryUrl($path, now()->addMinutes(5));
    }
}
