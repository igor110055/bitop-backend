<?php

namespace App\Providers;

use DB;

use Aws\Credentials\Credentials;
use Aws\Sns\SnsClient;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;

use App\Channels\SmsChannel;
use App\Database\MySqlConnection;
use App\Services\{
    AccountServiceInterface,
    AccountService,
    AdvertisementServiceInterface,
    AdvertisementService,
    AssetServiceInterface,
    AssetService,
    CurrencyExchangeServiceInterface,
    CurrencyExchangeService,
    CoinExchangeServiceInterface,
    CoinExchangeService,
    ExchangeServiceInterface,
    ExchangeService,
    FeeServiceInterface,
    FeeService,
    OrderServiceInterface,
    OrderService,
    TransferServiceInterface,
    TransferService,
    TransactionServiceInterface,
    TransactionService,
    WalletServiceInterface,
    WalletService,
    TwoFactorAuthServiceInterface,
    TwoFactorAuthService,
    CaptchaServiceInterface,
    CaptchaService,
    FcmServiceInterface,
    FcmService,
    WfpayServiceInterface,
    WfpayService,
};

class AppServiceProvider extends ServiceProvider
{
    public $binds = [
        AccountServiceInterface::class => AccountService::class,
        AdvertisementServiceInterface::class => AdvertisementService::class,
        AssetServiceInterface::class => AssetService::class,
        CurrencyExchangeServiceInterface::class => CurrencyExchangeService::class,
        CoinExchangeServiceInterface::class => CoinExchangeService::class,
        ExchangeServiceInterface::class => ExchangeService::class,
        FeeServiceInterface::class => FeeService::class,
        OrderServiceInterface::class => OrderService::class,
        TransferServiceInterface::class => TransferService::class,
        TransactionServiceInterface::class => TransactionService::class,
        WalletServiceInterface::class => WalletService::class,
        TwoFactorAuthServiceInterface::class => TwoFactorAuthService::class,
        CaptchaServiceInterface::class => CaptchaService::class,
        FcmServiceInterface::class => FcmService::class,
        WfpayServiceInterface::class => WfpayService::class,
    ];

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        Connection::resolverFor('mysql', function ($conn, $db, $prefix, $cfg) {
            return new MySqlConnection($conn, $db, $prefix, $cfg);
        });

        $this->registerExceptionHandler();

        Notification::resolved(function (ChannelManager $service) {
            $service->extend('sms', function ($app) {
                return new SmsChannel(
                    new SnsClient([
                        'version' => '2010-03-31',
                        'credentials' => new Credentials(
                            config('services.sns.key'),
                            config('services.sns.secret')
                        ),
                        'region' => config('services.sns.region'),
                    ])
                );
            });
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        # Set polymorphic types
        Relation::morphMap([
            'Advertisement' => \App\Models\Advertisement::class,
            'BankAccount' => \App\Models\BankAccount::class,
            'Group' => \App\Models\Group::class,
            'Manipulation' => \App\Models\Manipulation::class,
            'Order' => \App\Models\Order::class,
            'Transfer' => \App\Models\Transfer::class,
            'User' => \App\Models\User::class,
            'Verification' => \App\Models\Verification::class,
            'Deposit' => \App\Models\Deposit::class,
            'Withdrawal' => \App\Models\Withdrawal::class,
            'WalletManipulation' => \App\Models\WalletManipulation::class,
            'WalletLog' => \App\Models\WalletLog::class,
            'GroupApplication' => \App\Models\GroupApplication::class,
            'UserLock' => \App\Models\UserLock::class,
            'TwoFactorAuth' => \App\Models\TwoFactorAuth::class,
            'Wfpayment' => \App\Models\Wfpayment::class,
            'Wftansfer' => \App\Models\Wftansfer::class,
        ]);

        #services binding
        foreach ($this->binds as $interface => $implementation) {
            $this->app->bind($interface, $implementation);
        }

        # Disable resource wrapping
        JsonResource::withoutWrapping();

        # Force https
        if (env('APP_FORCE_HTTPS')) {
            URL::forceScheme('https');
        }

        # bootstrap
        Paginator::useBootstrap();
    }

    protected function registerExceptionHandler()
    {
        app('Dingo\Api\Exception\Handler')->register(function (\Throwable $e) {
            return app('App\Exceptions\ApiExceptionHandler')->handle($e);
        });
    }
}
