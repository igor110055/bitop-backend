<?php

namespace App\Models;

use Carbon\Carbon;

class Order extends RandomIDModel
{
    const ID_SIZE = 14;
    const STATUS_PROCESSING = 'processing';
    const STATUS_CLAIMED = 'claimed';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELED = 'canceled';
    const STATUS = [
        self::STATUS_PROCESSING,
        self::STATUS_CLAIMED,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELED,
    ];

    const PAYABLE_BANK_ACCOUNT = 'bank_account';
    const PAYABLE_WFPAYMENT = 'wfpayment';
    const PAYMENT_TYPES_MAP = [
        'BankAccount' => self::PAYABLE_BANK_ACCOUNT,
        'Wfpayment' => self::PAYABLE_WFPAYMENT,
    ];

    const OP_USER = 'user';
    const OP_ADMIN = 'admin';
    const OP_SYSTEM = 'system';

    const TIMELINE_CREATED = 'created';
    const TIMELINE_PAYMENT_AWAITING = 'payment-awaiting';
    const TIMELINE_CLAIMED = 'claimed';
    const TIMELINE_COMPLETED = 'completed';
    const TIMELINE_CANCELED = 'canceled';

    const FRONTEND_DETAIL_PATH = '/order/detail/';

    public static $types = [
        self::STATUS_PROCESSING,
        self::STATUS_CLAIMED,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELED,
    ];

    protected $casts = [
        'is_express' => 'boolean',
    ];

    protected $fillable = [
        'id',
        'is_express',
        'src_user_id',
        'dst_user_id',
        'status',
        'coin',
        'amount',
        'fee',
        'currency',
        'total',
        'unit_price',
        'profit',
        'coin_unit_price',
        'currency_unit_price',
        'payment_src_type',
        'payment_src_id',
        'payment_dst_type',
        'payment_dst_id',
        'advertisement_id',
        'fee_setting_id',
        'expired_at',
        'claimed_at',
        'completed_at',
        'canceled_at',
    ];

    protected $dates = [
        'expired_at',
        'claimed_at',
        'revoked_at',
        'completed_at',
        'canceled_at',
    ];

    public function advertisement()
    {
        return $this->belongsTo(Advertisement::class, 'advertisement_id');
    }

    public function src_user()
    {
        return $this->belongsTo(User::class, 'src_user_id');
    }

    public function dst_user()
    {
        return $this->belongsTo(User::class, 'dst_user_id');
    }

    public function src_account()
    {
        return $this->belongsTo(Account::class, 'src_account_id');
    }

    public function dst_account()
    {
        return $this->belongsTo(Account::class, 'dst_account_id');
    }

    public function fee_setting()
    {
        return $this->belongsTo(FeeSetting::class, 'fee_setting_id');
    }

    public function transactions()
    {
        return $this->morphMany(Transaction::class, 'transactable');
    }

    public function asset_transactions()
    {
        return $this->morphMany(AssetTransaction::class, 'transactable');
    }

    public function payment_src()
    {
        return $this->morphTo();
    }

    public function payment_dst()
    {
        return $this->morphTo();
    }

    public function bank_accounts()
    {
        return $this->morphToMany(BankAccount::class, 'bank_account_payable')->withTimestamps();
    }

    public function verifications()
    {
        return $this->morphMany(verification::class, 'verificable');
    }

    public function admin_actions()
    {
        return $this->morphMany(AdminAction::class, 'applicable');
    }

    public function system_actions()
    {
        return $this->morphMany(SystemAction::class, 'applicable');
    }

    public function wfpayments()
    {
        return $this->hasMany(Wfpayment::class);
    }

    public function getAdOwnerAttribute()
    {
        return $this->advertisement->owner;
    }

    public function getIsAdOwnerAttribute()
    {
        $user = auth()->user();
        return data_get($user, 'id') === $this->advertisement->user_id;
    }

    public function getMessageAttribute()
    {
        return $this->advertisement->message;
    }

    public function getTermsAttribute()
    {
        return $this->advertisement->terms;
    }

    public function getIsExpiredAttribute()
    {
        return $this->expired_at->lt(Carbon::now());
    }

    public function getTimelineAttribute()
    {
        return [
            Order::TIMELINE_CREATED => [
                'event' => Order::TIMELINE_CREATED,
                'time' => $this->created_at,
            ],
            Order::TIMELINE_PAYMENT_AWAITING => [
                'event' => Order::TIMELINE_PAYMENT_AWAITING,
                'time' => $this->expired_at,
            ],
            Order::TIMELINE_CLAIMED => [
                'event' => Order::TIMELINE_CLAIMED,
                'time' => $this->claimed_at,
            ],
            Order::TIMELINE_COMPLETED => [
                'event' => Order::TIMELINE_COMPLETED,
                'time' => $this->completed_at,
            ],
            Order::TIMELINE_CANCELED => [
                'event' => Order::TIMELINE_CANCELED,
                'time' => $this->canceled_at,
            ],
        ];
    }
    public function getCurrentTimelineAttribute()
    {
        $timeline = $this->timeline;
        $event = ['current' => [], 'next' => []];
        if ($this->status === Order::STATUS_PROCESSING) {
            $event['current'][] = $timeline[Order::TIMELINE_CREATED];
            $event['current'][] = $timeline[Order::TIMELINE_PAYMENT_AWAITING];
            $event['next'][] = $timeline[Order::TIMELINE_CLAIMED];
            $event['next'][] = $timeline[Order::TIMELINE_COMPLETED];
        } elseif ($this->status === Order::STATUS_CLAIMED) {
            $event['current'][] = $timeline[Order::TIMELINE_CREATED];
            $event['current'][] = $timeline[Order::TIMELINE_PAYMENT_AWAITING];
            $event['current'][] = $timeline[Order::TIMELINE_CLAIMED];
            $event['next'][] = $timeline[Order::TIMELINE_COMPLETED];
        } elseif ($this->status === Order::STATUS_COMPLETED) {
            $event['current'][] = $timeline[Order::TIMELINE_CREATED];
            $event['current'][] = $timeline[Order::TIMELINE_PAYMENT_AWAITING];
            $event['current'][] = $timeline[Order::TIMELINE_CLAIMED];
            if ($this->admin_actions()->first()) {
                $event['current'][] = array_merge($timeline[Order::TIMELINE_COMPLETED], ['operator' => Order::OP_ADMIN]);
            } else {
                $event['current'][] = array_merge($timeline[Order::TIMELINE_COMPLETED], ['operator' => Order::OP_USER]);
            }
        } elseif ($this->status === Order::STATUS_CANCELED) {
            if (is_null($this->claimed_at)) {
                $event['current'][] = $timeline[Order::TIMELINE_CREATED];
                $event['current'][] = $timeline[Order::TIMELINE_PAYMENT_AWAITING];
                if ($this->system_actions()->first()) {
                    $event['current'][] = array_merge($timeline[Order::TIMELINE_CANCELED], ['operator' => Order::OP_SYSTEM]);
                } else {
                    $event['current'][] = array_merge($timeline[Order::TIMELINE_CANCELED], ['operator' => Order::OP_USER]);
                }
            } else {
                $event['current'][] = $timeline[Order::TIMELINE_CREATED];
                $event['current'][] = $timeline[Order::TIMELINE_PAYMENT_AWAITING];
                $event['current'][] = $timeline[Order::TIMELINE_CLAIMED];
                if ($this->admin_actions()->first()) {
                    $event['current'][] = array_merge($timeline[Order::TIMELINE_CANCELED], ['operator' => Order::OP_ADMIN]);
                }
            }
        }
        return $event;
    }
}
