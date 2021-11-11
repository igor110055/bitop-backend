<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Dec\Dec;
use DB;
use Illuminate\Console\Command;

use App\Models\{
    AssetTransaction,
    Order,
    Transaction,
    Advertisement,
};

use App\Exceptions\{
    DuplicateRecordError,
};

use App\Repos\Interfaces\{
    AccountRepo,
    AgencyRepo,
    AssetRepo,
    AssetReportRepo,
    AssetTransactionRepo,
    CurrencyExchangeRateRepo,
    GroupRepo,
    OrderRepo,
    AdvertisementRepo,
    ReportRepo,
    TransferRepo,
    WithdrawalRepo,
    DepositRepo,
    TransactionRepo,
    OrderReportRepo,
    AdReportRepo,
    TransferReportRepo,
    WithdrawalDepositReportRepo,
    AccountReportRepo,
    FeeReportRepo,
    WalletBalanceReportRepo,
    FeeShareReportRepo,
};

class DailyReport extends Command
{
    protected $signature = 'daily-report:generate {--date=} {--save-to-db}';
    protected $description = 'Generate daily reports';

    protected static $asset_transaction_type_map = [
        'deposit_amount' => AssetTransaction::TYPE_SELL_ORDER,
        'withdraw_amount' => AssetTransaction::TYPE_BUY_ORDER,
        'manual_deposit_amount' => AssetTransaction::TYPE_MANUAL_DEPOSIT,
        'manual_withdraw_amount' => AssetTransaction::TYPE_MANUAL_WITHDRAWAL,
    ];

    public function __construct(
        AccountRepo $AccountRepo,
        AgencyRepo $AgencyRepo,
        AssetRepo $AssetRepo,
        AssetTransactionRepo $AssetTransactionRepo,
        CurrencyExchangeRateRepo $CurrencyExchangeRateRepo,
        AssetReportRepo $AssetReportRepo,
        GroupRepo $GroupRepo,
        OrderRepo $OrderRepo,
        ReportRepo $ReportRepo
    ) {
        parent::__construct();
        $this->AccountRepo = $AccountRepo;
        $this->AgencyRepo = $AgencyRepo;
        $this->AssetRepo = $AssetRepo;
        $this->AssetTransactionRepo = $AssetTransactionRepo;
        $this->CurrencyExchangeRateRepo = $CurrencyExchangeRateRepo;
        $this->AssetReportRepo = $AssetReportRepo;
        $this->GroupRepo = $GroupRepo;
        $this->OrderRepo = $OrderRepo;
        $this->ReportRepo = $ReportRepo;
        $this->timezone = config('core.timezone.default');
        $this->coins = config('core.coin.all');
        $this->currencies = config('core.currency.all');
    }

    public function handle()
    {
        $date = $this->option('date');
        $date = $date
            ? Carbon::parse($date, $this->timezone)
            : Carbon::now($this->timezone)->subDay();
        list($this->from, $this->to) = today_and_tomorrow($date);
        $this->date = $this->from->toDateString();

        $this->save_to_db = $this->option('save-to-db');
        if (Carbon::now()->lt($this->to)) {
            $this->error('Warning: You are trying to get a future report. Will not save to database.');
            $this->save_to_db = false;
        }

        $this->info('Generate report of '.$this->date);

        $this->agencies = $this->AgencyRepo
            ->getAll();
        $this->orders = $this->getOrders();
        $this->currency_exchange_rates = $this->getCurrencyExchangeRates();

        DB::transaction(function () {
            $this->generalReport();
            $this->assetReport();
            $this->accountReport();
            $this->orderReport();
            $this->feeReport();
            $this->withdrawalDepositReport();
            $this->adReport();
            $this->transferReport();
            $this->walletBalanceReport();
            $this->feeShareReport();
        });
    }

    protected function getCurrencyExchangeRates()
    {
        $currency_exchange_rates = [];
        foreach ($this->currencies as $currency) {
            $currency_exchange_rates[$currency] = $this->CurrencyExchangeRateRepo
                ->getLatest($currency, null, $this->to)
                ->toArray();
        }
        return $currency_exchange_rates;
    }

    protected function getOrders()
    {
        return $this->OrderRepo
            ->queryOrder([
                ['status', '=', Order::STATUS_COMPLETED],
                ['completed_at', '>=', $this->from],
                ['completed_at', '<', $this->to]
            ], null)
            ->get();
    }

    protected function generalReport()
    {
        # initiate
        $profit = Dec::create(0);
        $order_count = $this->orders->count();
        foreach ($this->agencies as $agency) {
            $agency_report[$agency->id]['agency_id'] = $agency->id;
            $agency_report[$agency->id]['orders'] = 0;
            $agency_report[$agency->id]['sell_orders'] = 0;
            $agency_report[$agency->id]['buy_orders'] = 0;
            $agency_report[$agency->id]['profit'] = '0';
        }

        foreach ($this->orders as $order) {
            $src_group_id = $order->src_user->group_id;
            $dst_group_id = $order->dst_user->group_id;
            $src_agency_id = $order->src_user->agency_id;
            $dst_agency_id = $order->dst_user->agency_id;

            if ($src_agency_id) {
                $agency_report[$src_agency_id]['sell_orders']++;
                $agency_report[$src_agency_id]['orders']++;
            }
            if ($dst_agency_id) {
                $agency_report[$dst_agency_id]['buy_orders']++;
                if ($dst_agency_id !== $src_agency_id) {
                    $agency_report[$dst_agency_id]['orders']++;
                }
            }
            if ($order->profit) {
                $profit = (string)Dec::add($profit, $order->profit);
                if ($dst_agency_id) {
                    $agency_report[$dst_agency_id]['profit'] = (string)Dec::add($agency_report[$dst_agency_id]['profit'], $order->profit);
                }
            }
        }

        $this->printLine();
        $this->info('System Report');
        $this->table(['System Orders', 'System Profit'], [[$order_count, $profit]]);
        $this->printLine();
        $this->info('Agency Report');
        $this->table(['Agnecy ID', 'Sell Orders', 'Buy Orders', 'Profit'], $agency_report);
        $this->printLine();

        if ($this->save_to_db) {
            # system report
            try {
                $this->ReportRepo
                    ->create($this->date, [
                        'agency_id' => null,
                        'orders' => $order_count,
                        'sell_orders' => $order_count,
                        'buy_orders' => $order_count,
                        'profit' => $profit,
                    ]);
            } catch (DuplicateRecordError $e) {
                $this->error("System Report of {$this->date} already exists.");
            }

            # agency report
            foreach ($agency_report as $report) {
                try {
                    $this->ReportRepo
                        ->create($this->date, $report);
                } catch (DuplicateRecordError $e) {
                    $this->error("Agency Report of {$this->date} {$report['agency_id']} already exists.");
                }
            }
        }
    }

    protected function accountReport()
    {
        $AccountReportRepo = app()->make(AccountReportRepo::class);
        $AccountRepo = app()->make(AccountRepo::class);

        $this->printLine();
        $this->info('Account Report');

        $report = $AccountReportRepo->initReport($this->from, $this->to);

        $accounts = $AccountRepo->all();

        foreach ($accounts as $account) {
            $coin = $account->coin;
            if (!in_array($coin , $this->coins)) {
                continue;
            }
            $group = $account->user->group->id;

            $price_delta = Dec::mul($account->balance, $report[$coin]['exchange_rate']);
            # coin
            $report[$coin]['balance'] = Dec::add(
                $report[$coin]['balance'],
                $account->balance
            );
            $report[$coin]['balance_price'] = Dec::add(
                $report[$coin]['balance_price'], $price_delta
            );
            # system
            $report['system']['balance_price'] = Dec::add(
                $report['system']['balance_price'], $price_delta
            );
            # group
            $report[$group]['balance_price'] = Dec::add(
                $report[$group]['balance_price'], $price_delta
            );
            # coin, group
            $report["{$coin}-{$group}"]['balance'] = Dec::add(
                $report["{$coin}-{$group}"]['balance'],
                $account->balance
            );
            $report["{$coin}-{$group}"]['balance_price'] = Dec::add(
                $report["{$coin}-{$group}"]['balance_price'], $price_delta
            );
        }
        $this->save(
            $AccountReportRepo,
            $report
        );

        $output = [];
        foreach ($report as $row) {
            $row['balance'] = formatted_coin_amount((string) $row['balance'], $row['coin']);
            $row['balance_price'] = formatted_price((string) $row['balance_price']);
            $output[] = $row;
        }
        $this->table(['coin','exchange_rate', 'group_id', 'balance', 'balance_price'], $output);
        return $report;
    }

    protected function walletBalanceReport()
    {
        $WalletBalanceReportRepo = app()->make(WalletBalanceReportRepo::class);

        $this->printLine();
        $this->info('Wallet Balance Report');

        $report = $WalletBalanceReportRepo->initReport($this->to);

        $this->save(
            $WalletBalanceReportRepo,
            $report
        );

        $output = [];
        foreach ($report as $row) {
            $row['balance'] = formatted_coin_amount((string) $row['balance'], $row['coin']);
            $row['balance_price'] = formatted_price((string) $row['balance_price']);
            $output[] = $row;
        }
        $this->table(['coin','exchange_rate', 'balance', 'balance_price'], $output);
        return $report;
    }

    protected function withdrawalDepositReport()
    {
        $WithdrawalDepositReportRepo = app()->make(WithdrawalDepositReportRepo::class);
        $WithdrawalRepo = app()->make(WithdrawalRepo::class);
        $DepositRepo = app()->make(DepositRepo::class);
        $this->printLine();
        $this->info('Withdrawal Deposit Report');

        $report = $WithdrawalDepositReportRepo->initReport($this->from, $this->to);

        $withdrawals = $WithdrawalRepo
            ->queryWithdrawal([
                ['submitted_confirmed_at', '>=', $this->from],
                ['submitted_confirmed_at', '<', $this->to]
            ])
            ->get();
        $deposits = $DepositRepo
            ->queryDeposit([
                ['confirmed_at', '>=', $this->from],
                ['confirmed_at', '<', $this->to]
            ])
            ->get();

        foreach ($withdrawals as $withdrawal) {
            $coin = $withdrawal->coin;
            $group = $withdrawal->user->group->id;

            $report[$coin]['withdrawal_count']++;
            $report[$group]['withdrawal_count']++;
            $report["{$coin}-{$group}"]['withdrawal_count']++;
            $price_delta = Dec::mul($withdrawal->amount, $report[$coin]['exchange_rate']);
            # coin
            $report[$coin]['withdrawal_amount'] = Dec::add(
                $report[$coin]['withdrawal_amount'],
                $withdrawal->amount
            );
            $report[$coin]['withdrawal_price'] = Dec::add(
                $report[$coin]['withdrawal_price'], $price_delta
            );
            # system
            $report['system']['withdrawal_price'] = Dec::add(
                $report['system']['withdrawal_price'], $price_delta
            );
            # group
            $report[$group]['withdrawal_price'] = Dec::add(
                $report[$group]['withdrawal_price'], $price_delta
            );
            # coin, group
            $report["{$coin}-{$group}"]['withdrawal_amount'] = Dec::add(
                $report["{$coin}-{$group}"]['withdrawal_amount'],
                $withdrawal->amount
            );
            $report["{$coin}-{$group}"]['withdrawal_price'] = Dec::add(
                $report["{$coin}-{$group}"]['withdrawal_price'], $price_delta
            );
        }
        foreach ($deposits as $deposit) {
            $coin = $deposit->coin;
            $group = $deposit->user->group->id;

            $report[$coin]['deposit_count']++;
            $report[$group]['deposit_count']++;
            $report["{$coin}-{$group}"]['deposit_count']++;
            $price_delta = Dec::mul($deposit->amount, $report[$coin]['exchange_rate']);
            # coin
            $report[$coin]['deposit_amount'] = Dec::add(
                $report[$coin]['deposit_amount'],
                $deposit->amount
            );
            $report[$coin]['deposit_price'] = Dec::add(
                $report[$coin]['deposit_price'], $price_delta
            );
            # system
            $report['system']['deposit_price'] = Dec::add(
                $report['system']['deposit_price'], $price_delta
            );
            # group
            $report[$group]['deposit_price'] = Dec::add(
                $report[$group]['deposit_price'], $price_delta
            );
            # coin, group
            $report["{$coin}-{$group}"]['deposit_amount'] = Dec::add(
                $report["{$coin}-{$group}"]['deposit_amount'],
                $deposit->amount
            );
            $report["{$coin}-{$group}"]['deposit_price'] = Dec::add(
                $report["{$coin}-{$group}"]['deposit_price'], $price_delta
            );
        }

        $this->save(
            $WithdrawalDepositReportRepo,
            $report
        );

        $output = [];
        foreach ($report as $row) {
            $row['withdrawal_amount'] = formatted_coin_amount((string) $row['withdrawal_amount'], $row['coin']);
            $row['deposit_amount'] = formatted_coin_amount((string) $row['deposit_amount'], $row['coin']);
            $row['withdrawal_price'] = formatted_price((string) $row['withdrawal_price']);
            $row['deposit_price'] = formatted_price((string) $row['deposit_price']);
            $output[] = $row;
        }
        $this->table(['coin','exchange_rate', 'group_id', 'withdrawal_count', 'withdrawal_amount', 'withdrawal_price', 'deposit_count', 'deposit_amount', 'deposit_price'], $output);
        return $report;
    }

    protected function adReport()
    {
        $AdReportRepo = app()->make(AdReportRepo::class);
        $AdvertisementRepo = app()->make(AdvertisementRepo::class);
        $this->printLine();
        $this->info('Advertisement Report');

        $report = $AdReportRepo->initReport($this->from, $this->to);
        $ads = $AdvertisementRepo
            ->queryAdvertisement([
                ['status', '=', Advertisement::STATUS_AVAILABLE],
                ['created_at', '>=', $this->from],
                ['created_at', '<', $this->to]
            ])
            ->get();

        foreach ($ads as $ad) {
            $coin = $ad->coin;
            $group = $ad->owner->group->id;

            $report[$coin]['ad_count']++;
            $report[$group]['ad_count']++;
            $report["{$coin}-{$group}"]['ad_count']++;
            $price_delta = Dec::mul($ad->amount, $report[$coin]['exchange_rate']);
            if ($ad->type === Advertisement::TYPE_BUY) {
                # coin
                $report[$coin]['buy_ad_count']++;
                $report[$coin]['buy_ad_amount'] = Dec::add(
                    $report[$coin]['buy_ad_amount'],
                    $ad->amount
                );
                $report[$coin]['buy_ad_price'] = Dec::add(
                    $report[$coin]['buy_ad_price'], $price_delta
                );
                # system
                $report['system']['buy_ad_price'] = Dec::add(
                    $report['system']['buy_ad_price'], $price_delta
                );
                # group
                $report[$group]['buy_ad_count']++;
                $report[$group]['buy_ad_price'] = Dec::add(
                    $report[$group]['buy_ad_price'], $price_delta
                );
                # coin, group
                $report["{$coin}-{$group}"]['buy_ad_count']++;
                $report["{$coin}-{$group}"]['buy_ad_amount'] = Dec::add(
                    $report["{$coin}-{$group}"]['buy_ad_amount'],
                    $ad->amount
                );
                $report["{$coin}-{$group}"]['buy_ad_price'] = Dec::add(
                    $report["{$coin}-{$group}"]['buy_ad_price'], $price_delta
                );
            } elseif ($ad->type === Advertisement::TYPE_SELL) {
                # coin
                $report[$coin]['sell_ad_count']++;
                $report[$coin]['sell_ad_amount'] = Dec::add(
                    $report[$coin]['sell_ad_amount'],
                    $ad->amount
                );
                $report[$coin]['sell_ad_price'] = Dec::add(
                    $report[$coin]['sell_ad_price'], $price_delta
                );
                # system
                $report['system']['sell_ad_price'] = Dec::add(
                    $report['system']['sell_ad_price'], $price_delta
                );
                # group
                $report[$group]['sell_ad_count']++;
                $report[$group]['sell_ad_price'] = Dec::add(
                    $report[$group]['sell_ad_price'], $price_delta
                );
                # coin, group
                $report["{$coin}-{$group}"]['sell_ad_count']++;
                $report["{$coin}-{$group}"]['sell_ad_amount'] = Dec::add(
                    $report["{$coin}-{$group}"]['sell_ad_amount'],
                    $ad->amount
                );
                $report["{$coin}-{$group}"]['sell_ad_price'] = Dec::add(
                    $report["{$coin}-{$group}"]['sell_ad_price'], $price_delta
                );
            }
        }

        $this->save(
            $AdReportRepo,
            $report
        );

        $output = [];
        foreach ($report as $row) {
            $row['buy_ad_amount'] = formatted_coin_amount((string) $row['buy_ad_amount'], $row['coin']);
            $row['sell_ad_amount'] = formatted_coin_amount((string) $row['sell_ad_amount'], $row['coin']);
            $row['buy_ad_price'] = formatted_price((string) $row['buy_ad_price']);
            $row['sell_ad_price'] = formatted_price((string) $row['sell_ad_price']);
            $output[] = $row;
        }
        $this->table(['coin','exchange_rate', 'group_id', 'ad_count', 'buy_ad_count', 'buy_ad_amount', 'buy_ad_price', 'sell_ad_count', 'sell_ad_amount', 'sell_ad_price'], $output);
        return $report;
    }

    protected function feeShareReport()
    {
        $FeeShareReportRepo = app()->make(FeeShareReportRepo::class);
        $TransactionRepo = app()->make(TransactionRepo::class);
        $this->printLine();
        $this->info('Fee Share Report');

        $report = $FeeShareReportRepo->initReport($this->from, $this->to);
        $transactions = $TransactionRepo->queryTransaction([
                ['type', '=', Transaction::TYPE_FEE_SHARE],
                ['created_at', '>=', $this->from],
                ['created_at', '<', $this->to]
            ])
            ->get();

        # fee share
        foreach ($transactions as $t) {
            $coin = $t->coin;
            $group = optional($t->account->user->group)->id;
            $price_delta = Dec::mul($t->amount, $report[$coin]['exchange_rate']);
            # coins, null group
            $report[$coin]['share_amount'] = Dec::add(
                $report[$coin]['share_amount'],
                $t->amount
            );
            $report[$coin]['share_price'] = Dec::add(
                $report[$coin]['share_price'], $price_delta
            );

            # system
            $report['system']['share_price'] = Dec::add(
                $report['system']['share_price'], $price_delta
            );

            # groups, null coin
            $report[$group]['share_price'] = Dec::add(
                $report[$group]['share_price'], $price_delta
            );
            # coins, groups
            $report["{$coin}-{$group}"]['share_amount'] = Dec::add(
                $report["{$coin}-{$group}"]['share_amount'],
                $t->amount
            );
            $report["{$coin}-{$group}"]['share_price'] = Dec::add(
                $report["{$coin}-{$group}"]['share_price'], $price_delta
            );
        }

        $this->save(
            $FeeShareReportRepo,
            $report
        );

        $output = [];
        foreach ($report as $row) {
            $row['share_amount'] = formatted_coin_amount((string) $row['share_amount'], $row['coin']);
            $row['share_price'] = formatted_price((string) $row['share_price']);
            $output[] = $row;
        }
        $this->table(['coin','exchange_rate', 'group_id', 'share_amount', 'share_price'], $output);
        return $report;
    }

    protected function feeReport()
    {
        $FeeReportRepo = app()->make(FeeReportRepo::class);
        $WithdrawalRepo = app()->make(WithdrawalRepo::class);
        $this->printLine();
        $this->info('Fee Report');

        $report = $FeeReportRepo->initReport($this->from, $this->to);
        $withdrawals = $WithdrawalRepo
            ->queryWithdrawal([
                ['submitted_confirmed_at', '>=', $this->from],
                ['submitted_confirmed_at', '<', $this->to]
            ])
            ->get();

        # withdrawal fee, wallet fee
        foreach ($withdrawals as $withdrawal) {
            $coin = $withdrawal->coin;
            $fee_coin = config('services.wallet.reverse_coin_map')[$withdrawal->wallet_fee_coin];
            $group = $withdrawal->user->group->id;

            $coin_price_delta = Dec::mul($withdrawal->fee, $report[$coin]['exchange_rate']);
            $fee_coin_price_delta = Dec::mul($withdrawal->wallet_fee, $report[$fee_coin]['exchange_rate']);
            # coin
            $report[$coin]['withdrawal_fee'] = Dec::add(
                $report[$coin]['withdrawal_fee'],
                $withdrawal->fee
            );
            $report[$coin]['withdrawal_fee_price'] = Dec::add(
                $report[$coin]['withdrawal_fee_price'], $coin_price_delta
            );
            $report[$fee_coin]['wallet_fee'] = Dec::add(
                $report[$fee_coin]['wallet_fee'],
                $withdrawal->wallet_fee
            );
            $report[$fee_coin]['wallet_fee_price'] = Dec::add(
                $report[$fee_coin]['wallet_fee_price'], $fee_coin_price_delta
            );
            # system
            $report['system']['withdrawal_fee_price'] = Dec::add(
                $report['system']['withdrawal_fee_price'], $coin_price_delta
            );
            $report['system']['wallet_fee_price'] = Dec::add(
                $report['system']['wallet_fee_price'], $fee_coin_price_delta
            );
            # group
            $report[$group]['withdrawal_fee_price'] = Dec::add(
                $report[$group]['withdrawal_fee_price'], $coin_price_delta
            );
            $report[$group]['wallet_fee_price'] = Dec::add(
                $report[$group]['wallet_fee_price'], $fee_coin_price_delta
            );
            # coin, group
            $report["{$coin}-{$group}"]['withdrawal_fee'] = Dec::add(
                $report["{$coin}-{$group}"]['withdrawal_fee'],
                $withdrawal->fee
            );
            $report["{$coin}-{$group}"]['withdrawal_fee_price'] = Dec::add(
                $report["{$coin}-{$group}"]['withdrawal_fee_price'], $coin_price_delta
            );
            $report["{$fee_coin}-{$group}"]['wallet_fee'] = Dec::add(
                $report["{$fee_coin}-{$group}"]['wallet_fee'],
                $withdrawal->wallet_fee
            );
            $report["{$fee_coin}-{$group}"]['wallet_fee_price'] = Dec::add(
                $report["{$fee_coin}-{$group}"]['wallet_fee_price'], $fee_coin_price_delta
            );
        }
        # order fee
        foreach ($this->orders as $order) {
            $coin = $order->coin;
            $price_delta = Dec::mul($order->fee, $report[$coin]['exchange_rate']);
            # coins, null group
            $report[$coin]['order_fee'] = Dec::add(
                $report[$coin]['order_fee'],
                $order->fee
            );
            $report[$coin]['order_fee_price'] = Dec::add(
                $report[$coin]['order_fee_price'], $price_delta
            );

            # system
            $report['system']['order_fee_price'] = Dec::add(
                $report['system']['order_fee_price'], $price_delta
            );

            # groups, coin and null coin
            $groups = [
                $order->src_user->group->id,
                $order->dst_user->group->id,
            ];
            if ($groups[0] === $groups[1]) {
                array_pop($groups);
            }

            foreach ($groups as $group) {
                # groups, coin
                $report["{$coin}-{$group}"]['order_fee'] = Dec::add(
                    $report["{$coin}-{$group}"]['order_fee'],
                    $order->fee
                );
                $report["{$coin}-{$group}"]['order_fee_price'] = Dec::add(
                    $report["{$coin}-{$group}"]['order_fee_price'], $price_delta
                );

                # groups, null coin
                $report[$group]['order_fee_price'] = Dec::add(
                    $report[$group]['order_fee_price'], $price_delta
                );
            }
        }

        $this->save(
            $FeeReportRepo,
            $report
        );

        $output = [];
        foreach ($report as $row) {
            $row['order_fee'] = formatted_coin_amount((string) $row['order_fee'], $row['coin']);
            $row['withdrawal_fee'] = formatted_coin_amount((string) $row['withdrawal_fee'], $row['coin']);
            $row['wallet_fee'] = formatted_coin_amount((string) $row['wallet_fee'], $row['coin']);
            $row['order_fee_price'] = formatted_price((string) $row['order_fee_price']);
            $row['withdrawal_fee_price'] = formatted_price((string) $row['withdrawal_fee_price']);
            $row['wallet_fee_price'] = formatted_price((string) $row['wallet_fee_price']);
            $output[] = $row;
        }
        $this->table(['coin','exchange_rate', 'group_id', 'order_fee', 'order_fee_price', 'withdrawal_fee', 'withdrawal_fee_price', 'wallet_fee', 'wallet_fee_price'], $output);
        return $report;
    }

    protected function transferReport()
    {
        $TransferReportRepo = app()->make(TransferReportRepo::class);
        $TransferRepo = app()->make(TransferRepo::class);
        $this->printLine();
        $this->info('Transfer Report');

        $report = $TransferReportRepo->initReport($this->from, $this->to);
        $transfers =  $TransferRepo
            ->queryTransfer([
                ['created_at', '>=', $this->from],
                ['created_at', '<', $this->to]
            ])
            ->get();

        foreach ($transfers as $transfer) {
            $coin = $transfer->coin;
            $transfer_price_delta = Dec::mul($transfer->amount, $report[$coin]['exchange_rate']);

            # coin, null group
            $report[$coin]['transfer_count']++;
            $report[$coin]['transfer_amount'] = Dec::add(
                $report[$coin]['transfer_amount'], $transfer->amount
            );
            $report[$coin]['transfer_price'] = Dec::add(
                $report[$coin]['transfer_price'], $transfer_price_delta
            );

            # system
            $report['system']['transfer_price'] = Dec::add(
                $report['system']['transfer_price'], $transfer_price_delta
            );

            # groups, coin and null coin
            $groups = [
                $transfer->src_user->group->id,
                $transfer->dst_user->group->id,
            ];
            if ($groups[0] === $groups[1]) {
                array_pop($groups);
            }

            foreach ($groups as $group) {
                # groups, coin
                $report["{$coin}-{$group}"]['transfer_count']++;
                $report["{$coin}-{$group}"]['transfer_amount'] = Dec::add(
                    $report["{$coin}-{$group}"]['transfer_amount'], $transfer->amount
                );
                $report["{$coin}-{$group}"]['transfer_price'] = Dec::add(
                    $report["{$coin}-{$group}"]['transfer_price'], $transfer_price_delta
                );

                # groups, null coin
                $report[$group]['transfer_count']++;
                $report[$group]['transfer_price'] = Dec::add(
                    $report[$group]['transfer_price'], $transfer_price_delta
                );
            }
        }

        $this->save(
            $TransferReportRepo,
            $report
        );

        $output = [];
        foreach ($report as $row) {
            $row['transfer_amount'] = formatted_coin_amount((string) $row['transfer_amount'], $row['coin']);
            $row['transfer_price'] = formatted_price((string) $row['transfer_price']);
            $output[] = $row;
        }
        $this->table(['coin','exchange_rate', 'group_id', 'transfer_count', 'transfer_amount', 'transfer_price'], $output);
        return $report;
    }

    protected function orderReport()
    {
        $OrderReportRepo = app()->make(OrderReportRepo::class);
        $this->printLine();
        $this->info('Order Report');

        $report = $OrderReportRepo->initReport($this->from, $this->to);

        foreach ($this->orders as $order) {
            $coin = $order->coin;
            $share = $order->transactions()->where('type', Transaction::TYPE_FEE_SHARE)->get();
            if ($share->isEmpty()) {
                $share_amount = 0;
            } else {
                $share_amount = $share->reduce(function ($carry, $item) {
                    return $carry + $item->amount;
                });
            }
            $order_price_delta = Dec::mul($order->amount, $report[$coin]['exchange_rate']);
            $share_price_delta = Dec::mul($share_amount, $report[$coin]['exchange_rate']);

            # coins, null group
            $report[$coin]['order_count']++;
            $report[$coin]['order_amount'] = Dec::add(
                $report[$coin]['order_amount'],
                $order->amount
            );
            $report[$coin]['order_price'] = Dec::add(
                $report[$coin]['order_price'], $order_price_delta
            );
            $report[$coin]['share_amount'] = Dec::add(
                $report[$coin]['share_amount'],
                $share_amount
            );
            $report[$coin]['share_price'] = Dec::add(
                $report[$coin]['share_price'], $share_price_delta
            );
            $report[$coin]['profit'] = Dec::add(
                $report[$coin]['profit'],
                data_get($order, 'profit', 0)
            );

            # system
            $report['system']['order_price'] = Dec::add(
                $report['system']['order_price'], $order_price_delta
            );
            $report['system']['share_price'] = Dec::add(
                $report['system']['share_price'], $share_price_delta
            );
            $report['system']['profit'] = Dec::add(
                $report['system']['profit'],
                data_get($order, 'profit', 0)
            );

            # groups, coin and null coin
            $groups = [
                $order->src_user->group->id,
                $order->dst_user->group->id,
            ];
            if ($groups[0] === $groups[1]) {
                array_pop($groups);
            }

            foreach ($groups as $group) {
                # groups, coin
                $report["{$coin}-{$group}"]['order_count']++;
                $report["{$coin}-{$group}"]['order_amount'] = Dec::add(
                    $report["{$coin}-{$group}"]['order_amount'],
                    $order->amount
                );
                $report["{$coin}-{$group}"]['order_price'] = Dec::add(
                    $report["{$coin}-{$group}"]['order_price'], $order_price_delta
                );
                $report["{$coin}-{$group}"]['share_amount'] = Dec::add(
                    $report["{$coin}-{$group}"]['share_amount'],
                    $share_amount
                );
                $report["{$coin}-{$group}"]['share_price'] = Dec::add(
                    $report["{$coin}-{$group}"]['share_price'], $share_price_delta
                );
                $report["{$coin}-{$group}"]['profit'] = Dec::add(
                    $report["{$coin}-{$group}"]['profit'],
                    data_get($order, 'profit', 0)
                );

                # groups, null coin
                $report[$group]['order_count']++;
                $report[$group]['order_price'] = Dec::add(
                    $report[$group]['order_price'], $order_price_delta
                );
                $report[$group]['share_price'] = Dec::add(
                    $report[$group]['share_price'], $share_price_delta
                );
                $report[$group]['profit'] = Dec::add(
                    $report[$group]['profit'], data_get($order, 'profit', 0)
                );
            }
        }

        $this->save(
            $OrderReportRepo,
            $report
        );

        $output = [];
        foreach ($report as $row) {
            $row['order_amount'] = formatted_coin_amount((string) $row['order_amount'], $row['coin']);
            $row['share_amount'] = formatted_coin_amount((string) $row['share_amount'], $row['coin']);
            $row['order_price'] = formatted_price((string) $row['order_price']);
            $row['share_price'] = formatted_price((string) $row['share_price']);
            $row['profit'] = (string) $row['profit'];
            $output[] = $row;
        }
        $this->table(['coin','exchange_rate', 'group_id', 'order_count', 'order_amount', 'order_price', 'share_amount', 'share_price', 'profit'], $output);
        return $report;
    }

    protected function save($repo, $reports)
    {
        if ($this->save_to_db) {
            foreach ($reports as $report) {
                try {
                    $repo->create($this->date, $report);
                } catch (DuplicateRecordError $e) {
                    $row = json_encode($report);
                    $this->error("Report of {$this->date} {$row} existed in database.");
                }
            }
        }
    }

    protected function assetReport()
    {
        $this->printLine();
        $this->info('Asset Export');
        $this->printLine();

        foreach ($this->currencies as $currency) {
            $system_asset_report[$currency] = [
                'currency' => $currency,
                'balance' => Dec::create(0),
                'unit_price' => null
            ];
            foreach (static::$asset_transaction_type_map as $amount_type => $transaction_type) {
                $system_asset_report[$currency][$amount_type] = Dec::create(0);
            }
        }

        foreach ($this->agencies as $agency) {
            $asset_report = [];
            foreach ($this->currencies as $currency) {
                $asset = $this->AssetRepo
                    ->findByAgencyCurrency($agency, $currency);
                $latest = $this->AssetTransactionRepo
                    ->getFilteringQuery($asset, null, $this->to, null, false)
                    ->first();

                $asset_report[$currency] = [
                    'currency' => $currency,
                    'balance' => data_get($latest, 'balance', formatted_price(0)),
                    'unit_price' => data_get($latest, 'result_unit_price', null),
                ];

                foreach (static::$asset_transaction_type_map as $amount_type => $transaction_type) {
                    $amount_sum = $this->AssetTransactionRepo
                        ->getFilteringQuery($asset, $this->from, $this->to, null, false)
                        ->where('type', $transaction_type)
                        ->sum('amount');
                    if (in_array($transaction_type, [AssetTransaction::TYPE_BUY_ORDER, AssetTransaction::TYPE_MANUAL_WITHDRAWAL])) {
                        $amount_sum = (string)Dec::sub(0, $amount_sum);
                    }
                    $asset_report[$currency][$amount_type] = formatted_price($amount_sum);
                    $system_asset_report[$currency][$amount_type] = (string)Dec::add($system_asset_report[$currency][$amount_type], $amount_sum);
                }

                $system_asset_report[$currency]['balance'] = (string)Dec::add($system_asset_report[$currency]['balance'], $asset_report[$currency]['balance']);

                if (!is_null($asset_report[$currency]['unit_price'])) {
                    if (!isset($unit_price_weighting_sum[$currency])) {
                        $unit_price_weighting_sum[$currency] = Dec::create(0);
                        $unit_price_base_sum[$currency] = Dec::create(0);
                    }
                    $unit_price_weighting_sum[$currency] = Dec::mul($asset_report[$currency]['unit_price'], $asset_report[$currency]['balance'])->add($unit_price_weighting_sum[$currency]);
                    $unit_price_base_sum[$currency] = Dec::add($unit_price_base_sum[$currency], $asset_report[$currency]['balance']);
                }
            }
            $this->exportAgencyCurrencyReport($agency, $asset_report);
        }

        foreach ($this->currencies as $currency) {
            if (isset($unit_price_weighting_sum[$currency]) and isset($unit_price_base_sum[$currency]) and !$unit_price_base_sum[$currency]->isZero()) {
                $system_asset_report[$currency]['unit_price'] = (string)Dec::div($unit_price_weighting_sum[$currency], $unit_price_base_sum[$currency]);
            }
        }
        $this->exportAgencyCurrencyReport(null, $system_asset_report);
    }

    protected function exportAgencyCurrencyReport($agency, $data)
    {
        $agency_id = data_get($agency, 'id', 'system');
        if ($agency) {
            $this->info("Agency : {$agency->name}");
        } else {
            $this->info("System summary");
        }
        $this->table(['currency', 'balance', 'unit_price', 'deposit_amount', 'withdraw_amount', 'manual_deposit_amount', 'manual_withdraw_amount'], $data);

        if ($this->save_to_db) {
            foreach ($this->currencies as $currency) {
                try {
                    $this->AssetReportRepo
                        ->create($this->date, $agency, $data[$currency]);
                } catch (DuplicateRecordError $e) {
                    $this->error("Asset Report of {$this->date} {$agency_id} {$currency} already exists.");
                }
            }
        }
        $this->printLine();
    }

    protected function printLine()
    {
        $this->info('============================================================================================================================');
    }
}
