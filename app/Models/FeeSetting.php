<?php
namespace App\Models;

class FeeSetting extends UuidModel
{
    const TYPE_ORDER = 'order';
    const TYPE_WITHDRAWAL = 'withdrawal';

    const RANGE_TYPES = [
        self::TYPE_ORDER,
    ];
    const FIX_TYPES = [
        self::TYPE_WITHDRAWAL,
    ];
    const TYPES = [
        self::TYPE_WITHDRAWAL,
        self::TYPE_ORDER,
    ];

    protected $fillable = [
        'applicable_id',
        'applicable_type',
        'coin',
        'type',
        'range_start',
        'range_end',
        'value',
        'unit',
        'is_active'
    ];
    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function applicable()
    {
        return $this->morphTo();
    }
}
