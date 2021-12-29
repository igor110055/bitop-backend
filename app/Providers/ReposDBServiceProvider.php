<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

use App\Repos\{
    DB,
    Interfaces,
};

class ReposDBServiceProvider extends ServiceProvider
{
    public $binds = [
        Interfaces\UserRepo::class => DB\UserRepo::class,
        Interfaces\CurrencyExchangeRateRepo::class => DB\CurrencyExchangeRateRepo::class,
        Interfaces\CoinExchangeRateRepo::class => DB\CoinExchangeRateRepo::class,
        Interfaces\VerificationRepo::class => DB\VerificationRepo::class,
        Interfaces\AdvertisementRepo::class => DB\AdvertisementRepo::class,
        Interfaces\OrderRepo::class => DB\OrderRepo::class,
        Interfaces\BankRepo::class => DB\BankRepo::class,
        Interfaces\BankAccountRepo::class => DB\BankAccountRepo::class,
        Interfaces\AccountRepo::class => DB\AccountRepo::class,
        Interfaces\AuthenticationRepo::class => DB\AuthenticationRepo::class,
        Interfaces\FeeSettingRepo::class => DB\FeeSettingRepo::class,
        Interfaces\TransactionRepo::class => DB\TransactionRepo::class,
        Interfaces\GroupRepo::class => DB\GroupRepo::class,
        Interfaces\TransferRepo::class => DB\TransferRepo::class,
        Interfaces\AgencyRepo::class => DB\AgencyRepo::class,
        Interfaces\AssetRepo::class => DB\AssetRepo::class,
        Interfaces\AssetTransactionRepo::class => DB\AssetTransactionRepo::class,
        Interfaces\ShareSettingRepo::class => DB\ShareSettingRepo::class,
        Interfaces\ManipulationRepo::class => DB\ManipulationRepo::class,
        Interfaces\DepositRepo::class => DB\DepositRepo::class,
        Interfaces\WithdrawalRepo::class => DB\WithdrawalRepo::class,
        Interfaces\LimitationRepo::class => DB\LimitationRepo::class,
        Interfaces\ReportRepo::class => DB\ReportRepo::class,
        Interfaces\AssetReportRepo::class => DB\AssetReportRepo::class,
        Interfaces\LimitationRepo::class => DB\LimitationRepo::class,
        Interfaces\ContactRepo::class => DB\ContactRepo::class,
        Interfaces\AddressRepo::class => DB\AddressRepo::class,
        Interfaces\AdminActionRepo::class => DB\AdminActionRepo::class,
        Interfaces\SystemActionRepo::class => DB\SystemActionRepo::class,
        Interfaces\TwoFactorAuthRepo::class => DB\TwoFactorAuthRepo::class,
        Interfaces\AnnouncementRepo::class => DB\AnnouncementRepo::class,
        Interfaces\DeviceTokenRepo::class => DB\DeviceTokenRepo::class,
        Interfaces\OrderReportRepo::class => DB\OrderReportRepo::class,
        Interfaces\AdReportRepo::class => DB\AdReportRepo::class,
        Interfaces\TransferReportRepo::class => DB\TransferReportRepo::class,
        Interfaces\AccountReportRepo::class => DB\AccountReportRepo::class,
        Interfaces\FeeReportRepo::class => DB\FeeReportRepo::class,
        Interfaces\WithdrawalDepositReportRepo::class => DB\WithdrawalDepositReportRepo::class,
        Interfaces\RoleRepo::class => DB\RoleRepo::class,
        Interfaces\ConfigRepo::class => DB\ConfigRepo::class,
        Interfaces\FeeCostRepo::class => DB\FeeCostRepo::class,
        Interfaces\WalletBalanceRepo::class => DB\WalletBalanceRepo::class,
        Interfaces\WalletBalanceLogRepo::class => DB\WalletBalanceLogRepo::class,
        Interfaces\WalletManipulationRepo::class => DB\WalletManipulationRepo::class,
        Interfaces\WalletLogRepo::class => DB\WalletLogRepo::class,
        Interfaces\WalletBalanceReportRepo::class => DB\WalletBalanceReportRepo::class,
        Interfaces\AnnouncementReadRepo::class => DB\AnnouncementReadRepo::class,
        Interfaces\GroupApplicationRepo::class => DB\GroupApplicationRepo::class,
        Interfaces\FeeShareReportRepo::class => DB\FeeShareReportRepo::class,
        Interfaces\WfpaymentRepo::class => DB\WfpaymentRepo::class,
        Interfaces\WftransferRepo::class => DB\WftransferRepo::class,
        Interfaces\WfpayAccountRepo::class => DB\WfpayAccountRepo::class,
    ];

    public function register()
    {
        foreach ($this->binds as $interface => $implementation) {
            $this->app->bind($interface, $implementation);
        }
    }
}
