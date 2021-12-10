<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use App\Models\{
    Authentication,
    BankAccount,
};
use App\Repos\Interfaces\{
    AccountRepo,
    AssetRepo,
    AssetReportRepo,
    BankAccountRepo,
    CoinExchangeRateRepo,
    CurrencyExchangeRateRepo,
    UserRepo,
    AccountReportRepo,
    WalletBalanceRepo,
    WalletBalanceReportRepo,
    GroupApplicationRepo,
};

class IndexController extends AdminController
{
    public function __construct(
        AccountRepo $AccountRepo,
        AssetRepo $AssetRepo,
        AssetReportRepo $AssetReportRepo,
        BankAccountRepo $BankAccountRepo,
        CoinExchangeRateRepo $CoinExchangeRateRepo,
        CurrencyExchangeRateRepo $CurrencyExchangeRateRepo,
        AccountReportRepo $AccountReportRepo,
        WalletBalanceReportRepo $WalletBalanceReportRepo,
        UserRepo $UserRepo,
        WalletBalanceRepo $WalletBalanceRepo,
        GroupApplicationRepo $GroupApplicationRepo
    ) {
        parent::__construct();
        $this->AccountRepo = $AccountRepo;
        $this->AssetRepo = $AssetRepo;
        $this->AssetReportRepo = $AssetReportRepo;
        $this->BankAccountRepo = $BankAccountRepo;
        $this->CoinExchangeRateRepo = $CoinExchangeRateRepo;
        $this->CurrencyExchangeRateRepo = $CurrencyExchangeRateRepo;
        $this->AccountReportRepo = $AccountReportRepo;
        $this->WalletBalanceReportRepo = $WalletBalanceReportRepo;
        $this->UserRepo = $UserRepo;
        $this->WalletBalanceRepo = $WalletBalanceRepo;
        $this->GroupApplicationRepo = $GroupApplicationRepo;
        $this->currencies = config('core.currency.all');
        $this->coins = config('core.coin.all');
    }

    public function index(string $keyword = null)
    {
        $user = \Auth::user();
        $user_count = $this->UserRepo
            ->getFilteringQuery(null, Authentication::PROCESSING, null)
            ->count();
        $bank_account_pending_count = $this->BankAccountRepo
            ->getFilteringQuery(BankAccount::STATUS_PENDING)
            ->count();
        $group_application_count = $this->GroupApplicationRepo
            ->getProcessingCount();

        $to = Carbon::yesterday();
        $from = $to->copy()->subDays(9);
        $dates = date_ticks($from->toDateString(), $to->toDateString());

        foreach ($this->coins as $coin) {
            $coin_prices[$coin] = $this->CoinExchangeRateRepo
                ->getLatest($coin, null)
                ->toArray();
            $coin_balances[$coin] = $this->AccountRepo->getBalancesSum($coin);
            $wallet_balances[$coin] = $this->WalletBalanceRepo->getBalance($coin);
            $dates_coin_price = $this->CoinExchangeRateRepo
                ->getByDates($coin, $dates);
            $coin_price_history[$coin] = collect($dates_coin_price)->map(function ($item, $key) {
                return data_get($item, 'price', '0.00');
            })->values()->toArray();
            $coin_price_history[$coin] = implode(',', $coin_price_history[$coin]);
        }

        $balance_chart_data = $this->AccountReportRepo->getChartData($from, $to);
        $wallet_balance_chart_data = $this->WalletBalanceReportRepo->getChartData($from, $to);

        return view('admin.index', [
            'user_count' => $user_count,
            'bank_account_pending_count' => $bank_account_pending_count,
            'group_application_count' => $group_application_count,
            'from' => $from,
            'to' => $to,
            'ticks' => date_ticks_for_chart($from, $to),
            'coin_prices' => $coin_prices,
            'coin_price_history' => $coin_price_history,
            'coin_balances' => $coin_balances,
            'wallet_balances' => $wallet_balances,
            'balance_chart_data' => $balance_chart_data,
            'wallet_balance_chart_data' => $wallet_balance_chart_data,
        ]);
    }
}
