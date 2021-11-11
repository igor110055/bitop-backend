<?php

namespace App\Models;

class Authentication extends UuidModel
{
    const PASSED = 'passed';
    const PROCESSING = 'processing';
    const REJECTED = 'rejected';
    const UNAUTHENTICATED = 'unauthenticated';
    const STATUS = [
        self::PASSED,
        self::PROCESSING,
        self::REJECTED,
        self::UNAUTHENTICATED,
    ];

    const REASON_ID_FILE_MISSING = 'id_file_missing';
    const REASON_ID_FILE_INSUFFICIENT = 'id_file_insufficient';
    const REASON_ID_FILE_UNIDENTIFIABLE = 'id_file_unidentifiable';
    const REASON_ID_NOT_MATCHED = 'id_not_matched';
    const REASON_NAME_NOT_MATCHED = 'name_not_matched';
    const REASON_INVALID_NAMES = 'invalid_names';
    const REASON_INVALID_USERNAME = 'invalid_username';
    const REASON_USERNAME_EXISTED = 'username_existed';
    const REASONS = [
        self::REASON_ID_FILE_MISSING,
        self::REASON_ID_FILE_INSUFFICIENT,
        self::REASON_ID_FILE_UNIDENTIFIABLE,
        self::REASON_ID_NOT_MATCHED,
        self::REASON_NAME_NOT_MATCHED,
        self::REASON_INVALID_NAMES,
        self::REASON_INVALID_USERNAME,
        self::REASON_USERNAME_EXISTED,
    ];

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'username',
        'security_code',
        'id_number',
        'status',
        'verified_at',
    ];

    protected $dates = ['verified_at'];

    public function authentication_files()
    {
        return $this->hasMany(AuthenticationFile::class);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
