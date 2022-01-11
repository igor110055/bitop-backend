<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Repos\Interfaces\{
    AgencyRepo,
    AssetReportRepo,
    CoinExchangeRateRepo,
    CurrencyExchangeRateRepo,
    GroupRepo,
    OrderReportRepo,
    AdReportRepo,
    TransferReportRepo,
    FeeReportRepo,
    FeeShareReportRepo,
    AccountReportRepo,
    WithdrawalDepositReportRepo,
    WalletBalanceReportRepo,
};

class ReportController extends AdminController
{

    public function __construct(
        AgencyRepo $AgencyRepo,
        CoinExchangeRateRepo $CoinExchangeRateRepo,
        CurrencyExchangeRateRepo $CurrencyExchangeRateRepo,
        AssetReportRepo $AssetReportRepo,
        GroupRepo $GroupRepo,
        OrderReportRepo $OrderReportRepo,
        AdReportRepo $AdReportRepo,
        TransferReportRepo $TransferReportRepo,
        FeeReportRepo $FeeReportRepo,
        FeeShareReportRepo $FeeShareReportRepo,
        AccountReportRepo $AccountReportRepo,
        WithdrawalDepositReportRepo $WithdrawalDepositReportRepo,
        WalletBalanceReportRepo $WalletBalanceReportRepo
    ) {
        parent::__construct();
        $this->AgencyRepo = $AgencyRepo;
        $this->CoinExchangeRateRepo = $CoinExchangeRateRepo;
        $this->CurrencyExchangeRateRepo = $CurrencyExchangeRateRepo;
        $this->AssetReportRepo = $AssetReportRepo;
        $this->GroupRepo = $GroupRepo;
        $this->OrderReportRepo = $OrderReportRepo;
        $this->AdReportRepo = $AdReportRepo;
        $this->TransferReportRepo = $TransferReportRepo;
        $this->FeeReportRepo = $FeeReportRepo;
        $this->FeeShareReportRepo = $FeeShareReportRepo;
        $this->AccountReportRepo = $AccountReportRepo;
        $this->WithdrawalDepositReportRepo = $WithdrawalDepositReportRepo;
        $this->WalletBalanceReportRepo = $WalletBalanceReportRepo;
        $this->timezone = config('core.timezone.default');
        $this->coins = config('core.coin.all');
        $this->currencies = config('core.currency.all');
        $this->currency = config('core.currency.base');

        $this->groups = array_merge(['system'], $this->GroupRepo
            ->getAllGroups()->pluck('id')->toArray());
        $this->agencies = $this->AgencyRepo
            ->getAll()->keyBy('id');
    }

    public function index()
    {
        $yesterday = Carbon::yesterday($this->timezone)->toDateString();
        return $this->daily($yesterday);
    }

    public function daily(string $date)
    {
        if (!$datetime = strtotime($date)) {
            return redirect()->route('admin.report.index');
        }

        // for getting yesteday and tmr date string
        $date = Carbon::createFromDate($date);
        if ($date->gte(Carbon::today($this->timezone))) {
            return redirect()->route('admin.report.index');
        }
        $yesterday = $date->copy()->subDay()->toDateString();
        $tomorrow = $date->copy()->addDay();
        if ($tomorrow->gte(Carbon::today())) {
            $tomorrow = null;
        } else {
            $tomorrow = $tomorrow->toDateString();
        }
        $date = $date->toDateString();

        /* $asset_report = $this->AssetReportRepo->getAllByDate($date); */

        return view('admin.report', [
            'date' => $date,
            'yesterday' => $yesterday,
            'tomorrow' => $tomorrow,
            'base_currency' => $this->currency,
            'groups' => $this->groups,
            'agencies' => $this->agencies,
            //'currency_exchange_rates' => $this->CurrencyExchangeRateRepo->getAllByDate($date),
            'coin_prices' => $this->CoinExchangeRateRepo->getAllByDate($date),
            'order_report' => $this->OrderReportRepo->getAllByDate($date),
            'ad_report' => $this->AdReportRepo->getAllByDate($date),
            'transfer_report' => $this->TransferReportRepo->getAllByDate($date),
            'fee_report' => $this->FeeReportRepo->getAllByDate($date),
            'fee_share_report' => $this->FeeShareReportRepo->getAllByDate($date),
            'account_report' => $this->AccountReportRepo->getAllByDate($date),
            'wallet_balance_report' => $this->WalletBalanceReportRepo->getAllByDate($date),
            'withdrawal_deposit_report' => $this->WithdrawalDepositReportRepo->getAllByDate($date),
            //'asset_report' => $asset_report,
            //'has_multiple_agencies' => (bool)(count($asset_report['agency']) > 1)
        ]);
    }

    protected function checkDates($from, $to)
    {
        if (!strtotime($from)) {
            $from = null;
        }
        if (!strtotime($to)) {
            $to = null;
        }
        if (is_null($from) && is_null($to)) {
            $to = Carbon::yesterday();
            $from = $to->copy()->subDays(9);
        } elseif (is_null($from)) {
            $to = Carbon::createFromDate($to);
            $from = $to->copy()->subDays(9);
        } elseif (is_null($to)) {
            $from = Carbon::createFromDate($from);
            $to = $from->copy()->addDays(9);
        } else {
            $from = Carbon::createFromDate($from);
            $to = Carbon::createFromDate($to);
        }

        if ($to->gte(Carbon::today())) {
            $to = Carbon::yesterday();
        }
        if ($from->gte($to)) {
            $from = $to->copy()->subDays(9);
        }
        $earlist = $to->copy()->subDays(99);
        if ($from->lt($earlist)) {
            $from = $earlist;
        }
        return [$from->toDateString(), $to->toDateString()];
    }

    public function orders(Request $request)
    {
        $values = $request->validate([
            'from' => 'string|date',
            'to' => 'string|date',
            'group_id' => 'string|nullable',
        ]);
        list($from, $to) = $this->checkDates(data_get($values, 'from'), data_get($values, 'to'));
        $date_range = "{$from} - {$to}";
        $data = $this->OrderReportRepo
            ->getChartData($from, $to, data_get($values, 'group_id'));

        return view('admin.report_orders', [
            'date_range' => $date_range,
            'from' => $from,
            'to' => $to,
            'ticks' => date_ticks_for_chart($from, $to),
            'current_group' => data_get($values, 'group_id', 'system'),
            'groups' => array_combine($this->groups, $this->groups),
            'reports' => $data,
        ]);
    }

    public function fees(Request $request)
    {
        $values = $request->validate([
            'from' => 'string|date',
            'to' => 'string|date',
            'group_id' => 'string|nullable',
        ]);
        list($from, $to) = $this->checkDates(data_get($values, 'from'), data_get($values, 'to'));
        $date_range = "{$from} - {$to}";
        $data = $this->FeeReportRepo
            ->getChartData($from, $to, data_get($values, 'group_id'));

        return view('admin.report_fees', [
            'date_range' => $date_range,
            'from' => $from,
            'to' => $to,
            'ticks' => date_ticks_for_chart($from, $to),
            'current_group' => data_get($values, 'group_id', 'system'),
            'groups' => array_combine($this->groups, $this->groups),
            'reports' => $data,
        ]);
    }

    public function feeShares(Request $request)
    {
        $values = $request->validate([
            'from' => 'string|date',
            'to' => 'string|date',
            'group_id' => 'string|nullable',
        ]);
        list($from, $to) = $this->checkDates(data_get($values, 'from'), data_get($values, 'to'));
        $date_range = "{$from} - {$to}";
        $data = $this->FeeShareReportRepo
            ->getChartData($from, $to, data_get($values, 'group_id'));

        return view('admin.report_fee_shares', [
            'date_range' => $date_range,
            'from' => $from,
            'to' => $to,
            'ticks' => date_ticks_for_chart($from, $to),
            'current_group' => data_get($values, 'group_id', 'system'),
            'groups' => array_combine($this->groups, $this->groups),
            'reports' => $data,
        ]);
    }

    public function ads(Request $request)
    {
        $values = $request->validate([
            'from' => 'string|date',
            'to' => 'string|date',
            'group_id' => 'string|nullable',
        ]);
        list($from, $to) = $this->checkDates(data_get($values, 'from'), data_get($values, 'to'));
        $date_range = "{$from} - {$to}";
        $data = $this->AdReportRepo
            ->getChartData($from, $to, data_get($values, 'group_id'));

        return view('admin.report_ads', [
            'date_range' => $date_range,
            'from' => $from,
            'to' => $to,
            'ticks' => date_ticks_for_chart($from, $to),
            'current_group' => data_get($values, 'group_id', 'system'),
            'groups' => array_combine($this->groups, $this->groups),
            'reports' => $data,
        ]);
    }

    public function transfers(Request $request)
    {
        $values = $request->validate([
            'from' => 'string|date',
            'to' => 'string|date',
            'group_id' => 'string|nullable',
        ]);
        list($from, $to) = $this->checkDates(data_get($values, 'from'), data_get($values, 'to'));
        $date_range = "{$from} - {$to}";
        $data = $this->TransferReportRepo
            ->getChartData($from, $to, data_get($values, 'group_id'));

        return view('admin.report_transfers', [
            'date_range' => $date_range,
            'from' => $from,
            'to' => $to,
            'ticks' => date_ticks_for_chart($from, $to),
            'current_group' => data_get($values, 'group_id', 'system'),
            'groups' => array_combine($this->groups, $this->groups),
            'reports' => $data,
        ]);
    }

    public function withdrawalsDeposits(Request $request)
    {
        $values = $request->validate([
            'from' => 'string|date',
            'to' => 'string|date',
            'group_id' => 'string|nullable',
        ]);
        list($from, $to) = $this->checkDates(data_get($values, 'from'), data_get($values, 'to'));
        $date_range = "{$from} - {$to}";
        $data = $this->WithdrawalDepositReportRepo
            ->getChartData($from, $to, data_get($values, 'group_id'));

        return view('admin.report_withdrawals_deposits', [
            'date_range' => $date_range,
            'from' => $from,
            'to' => $to,
            'ticks' => date_ticks_for_chart($from, $to),
            'current_group' => data_get($values, 'group_id', 'system'),
            'groups' => array_combine($this->groups, $this->groups),
            'reports' => $data,
        ]);
    }

    public function accounts(Request $request)
    {
        $values = $request->validate([
            'from' => 'string|date',
            'to' => 'string|date',
            'group_id' => 'string|nullable',
        ]);
        list($from, $to) = $this->checkDates(data_get($values, 'from'), data_get($values, 'to'));
        $date_range = "{$from} - {$to}";
        $data = $this->AccountReportRepo
            ->getChartData($from, $to, data_get($values, 'group_id'));

        return view('admin.report_accounts', [
            'date_range' => $date_range,
            'from' => $from,
            'to' => $to,
            'ticks' => date_ticks_for_chart($from, $to),
            'current_group' => data_get($values, 'group_id', 'system'),
            'groups' => array_combine($this->groups, $this->groups),
            'reports' => $data,
        ]);
    }

    public function walletBalances(Request $request)
    {
        $values = $request->validate([
            'from' => 'string|date',
            'to' => 'string|date',
        ]);
        list($from, $to) = $this->checkDates(data_get($values, 'from'), data_get($values, 'to'));
        $date_range = "{$from} - {$to}";
        $data = $this->WalletBalanceReportRepo->getChartData($from, $to);

        return view('admin.report_wallet_balances', [
            'date_range' => $date_range,
            'from' => $from,
            'to' => $to,
            'ticks' => date_ticks_for_chart($from, $to),
            'reports' => $data,
        ]);
    }

    /* public function exchangeRates(Request $request)
    {
        $values = $request->validate([
            'from' => 'string|date',
            'to' => 'string|date',
        ]);
        list($from, $to) = $this->checkDates(data_get($values, 'from'), data_get($values, 'to'));
        $date_range = "{$from} - {$to}";
        $data = $this->CurrencyExchangeRateRepo
            ->getChartData($from, $to);

        return view('admin.report_exchange_rates', [
            'date_range' => $date_range,
            'from' => $from,
            'to' => $to,
            'ticks' => date_ticks_for_chart($from, $to),
            'exchange_rates' => $data,
        ]);
    } */

    public function coinPrices(Request $request)
    {
        $values = $request->validate([
            'from' => 'string|date',
            'to' => 'string|date',
        ]);
        list($from, $to) = $this->checkDates(data_get($values, 'from'), data_get($values, 'to'));
        $date_range = "{$from} - {$to}";
        $data = $this->CoinExchangeRateRepo
            ->getChartData($from, $to);
        $data = $data['price'];

        return view('admin.report_coin_prices', [
            'date_range' => $date_range,
            'from' => $from,
            'to' => $to,
            'ticks' => date_ticks_for_chart($from, $to),
            'coin_prices' => $data,
        ]);
    }

    /* public function assets(Request $request)
    {
        $values = $request->validate([
            'agency' => 'string|nullable',
            'from' => 'string|date',
            'to' => 'string|date',
        ]);
        list($from, $to) = $this->checkDates(data_get($values, 'from'), data_get($values, 'to'));
        if ($agency_id = data_get($values, 'agency')) {
            $agency = $this->AgencyRepo->findOrFail($agency_id);
            $page_title = $agency->name.' ';
        } else {
            $agency = null;
            $page_title = '全系統 ';
        }
        $page_title .= $from.' - '.$to;

        $data = $this->AssetReportRepo
            ->getChartData($from, $to, $agency);

        return view('admin.report_assets', [
            'page_title' => $page_title,
            'agency_id' => $agency_id,
            'from' => $from,
            'to' => $to,
            'ticks' => date_ticks_for_chart($from, $to),
            'data' => $data,
        ]);
    } */
}
