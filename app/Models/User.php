<?php

namespace App\Models;

use Spatie\Permission\Traits\HasRoles;
use Carbon\Carbon;
use Dec\Dec;
use Throwable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;

use App\Exceptions\{
    Core\InternalServerError,
};
use App\Repos\Interfaces\{
    OrderRepo,
    UserRepo,
};

class User extends AuthModel
{
    use Notifiable, HasRoles, HasFactory;

    protected $casts = [
        'is_admin' => 'boolean',
        'is_merchant' => 'boolean',
        'is_tester' => 'boolean',
    ];

    protected $fillable = [
        'id',
        'email',
        'password',
        'security_code',
        'mobile',
        'username',
        'group_id',
        'nationality',
        'is_admin',
        'is_merchant',
        'agency_id',
        'email_verification_id',
        'mobile_verification_id',
        'first_name',
        'last_name',
        'invitation_id',
        'authentication_status',
        'locale',
        'two_factor_auth',
        'valid_order_count',
        'complete_order_count',
        'is_tester',
    ];

    protected $hidden = [
        'password',
        'security_code',
        'remember_token',
    ];

    protected $appends = ['name'];

    public function getNameAttribute()
    {
        if (is_null($this->first_name) or is_null($this->last_name)) {
            return '';
        }
        return $this->last_name.$this->first_name;
    }

    public function getIsVerifiedAttribute()
    {
        return ($this->authentication_status === Authentication::PASSED);
    }

    public function getIsAgentAttribute()
    {
        return !is_null($this->agency_id);
    }

    protected function handleCallbackRoute($route)
    {
        if (config('app.env') === 'local') {
            if (is_null(config('services.wallet.callback_proxy_domain'))) {
                throw new InternalServerError('Must set WALLET_CALLBACK_PROXY_DOMAIN in .env file for wallet callback');
            }
            return config('services.wallet.callback_proxy_domain')."/api/wallet/{$route}/{$this->id}";
        }
        return config('app.url')."/api/wallet/{$route}/{$this->id}";
    }

    public function getWalletDepositCallbackAttribute()
    {
        return $this->handleCallbackRoute('deposit-callback');
    }

    public function getWalletPayinCallbackAttribute()
    {
        return $this->handleCallbackRoute('payin-callback');
    }

    public function getWalletPayoutCallbackAttribute()
    {
        return $this->handleCallbackRoute('payout-callback');
    }

    public function getWalletApprovementCallbackAttribute()
    {
        return $this->handleCallbackRoute('approvement-callback');
    }

    public function getIsRootAttribute()
    {
        return (env('ROOT_ID') and ($this->id === env('ROOT_ID')));
    }

    public function getPreferredLocaleAttribute()
    {
        return data_get($this, 'locale', \App::getLocale());
    }

    public function getTradeNumberAttribute() : Int
    {
        return $this->valid_order_count;
    }

    public function getCompleteRateAttribute()
    {
        if ($this->valid_order_count === 0) {
            return null;
        }
        return (string) Dec::div($this->complete_order_count, $this->valid_order_count, 4)->mul(100, 0);
    }

    public function getAveragePayTimeAttribute()
    {
        $order = app()->make(OrderRepo::class);
        return $order->getUserAveragePayTime($this);
    }

    public function getAverageReleaseTimeAttribute()
    {
        $order = app()->make(OrderRepo::class);
        return $order->getUserAverageReleaseTime($this);
    }

    public function getRecentLoginTimeAttribute()
    {
        $log = app()->make(UserRepo::class)->getRecentLogin($this, 1);
        return $log ? $log->created_at : null;
    }

    public function getCurrentLoginTimeAttribute()
    {
        $log = app()->make(UserRepo::class)->getRecentLogin($this);
        return $log ? $log->created_at : null;
    }

    public function routeNotificationForNexmo($notification)
    {
        return $this->mobile;
    }

    public function routeNotificationForSms(?Notification $notication = null)
    {
        return $this->mobile;
    }

    public function user_logs()
    {
        return $this->hasMany(UserLog::class);
    }

    public function user_locks()
    {
        return $this->hasMany(UserLock::class);
    }

    public function invitation()
    {
        return $this->hasOne(GroupInvitation::class, 'id', 'invitation_id');
    }

    public function accounts()
    {
        return $this->hasMany(Account::class);
    }

    public function advertisements()
    {
        return $this->hasMany(Advertisement::class);
    }

    public function bank_accounts()
    {
        return $this->hasMany(BankAccount::class);
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function groups()
    {
        return $this->hasMany(Group::class);
    }

    public function verifications()
    {
        return $this->morphMany(Verification::class, 'verificable');
    }

    public function authentications()
    {
        return $this->hasMany(Authentication::class);
    }

    public function authentication_files()
    {
        return $this->hasMany(AuthenticationFile::class);
    }

    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }

    public function contacts()
    {
        return $this->hasMany(Contact::class);
    }

    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    public function two_factor_auths()
    {
        return $this->hasMany(TwoFactorAuth::class);
    }

    public function withdrawals()
    {
        return $this->hasMany(Withdrawal::class);
    }

    public function deposits()
    {
        return $this->hasMany(Deposit::class);
    }

    public function group_applications()
    {
        return $this->hasMany(GroupApplication::class);
    }

    public function limitations()
    {
        return $this->morphMany(Limitation::class, 'limitable');
    }

    public function admin_actions()
    {
        return $this->morphMany(AdminAction::class, 'applicable');
    }

    public function announcement_reads()
    {
        return $this->hasMany(AnnouncementRead::class);
    }
}
