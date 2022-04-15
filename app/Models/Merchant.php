<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class Merchant extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'id',
        'name',
    ];

    public function exchange_rates()
    {
        return $this->hasMany(ExchangeRate::class);
    }

    public function admin_actions()
    {
        return $this->morphMany(AdminAction::class, 'applicable');
    }
}
