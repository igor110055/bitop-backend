<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

use App\Console\Commands\{
    CheckBalance,
    CheckExtraBalance,
    CheckUserLockCommand,
    DailyReport,
    UpdateCoinExchangeRate,
    UpdateCurrencyExchangeRate,
    CancelUnconfirmedExpiredWithdrawals,
    SubmitPendingWithdrawals,
    CancelExpiredOrders,
    CancelExpiredTransfers,
    UpdateWithdrawalFeeCost,
    PruneAnnouncementReadTableCommand,
    CheckPendingWfpayments,
    CheckPendingWftransfers,
    SubmitExportLogs,
};

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $timezone = config('core.timezone.default');

        $schedule->command(CheckBalance::class)
            ->everyTenMinutes()
            ->withoutOverLapping();

        $schedule->command(CheckUserLockCommand::class)
            ->everyMinute()
            ->withoutOverLapping();

        $schedule->command(UpdateCoinExchangeRate::class)
            ->everyTenMinutes()
            ->withoutOverLapping();

        $schedule->command(UpdateCurrencyExchangeRate::class)
            ->hourly()
            ->withoutOverLapping();

        $schedule->command(CancelUnconfirmedExpiredWithdrawals::class)
            ->everyMinute()
            ->withoutOverLapping();

        $schedule->command(SubmitPendingWithdrawals::class)
            ->everyMinute()
            ->withoutOverLapping();

        $schedule->command(CancelExpiredOrders::class)
            ->everyMinute()
            ->withoutOverLapping();

        $schedule->command(CancelExpiredTransfers::class)
            ->everyMinute()
            ->withoutOverLapping();

        $schedule->command(UpdateWithdrawalFeeCost::class)
            ->everyFiveMinutes()
            ->withoutOverlapping();

        $schedule->command(CheckPendingWfpayments::class)
            ->everyThreeMinutes()
            ->withoutOverlapping();

        $schedule->command(CheckPendingWftransfers::class)
            ->everyThreeMinutes()
            ->withoutOverlapping();

        $schedule->command(SubmitExportLogs::class)
            ->everyMinute()
            ->withoutOverlapping();

        $schedule
            ->command(PruneAnnouncementReadTableCommand::class)
            ->daily()
            ->timezone($timezone)
            ->withoutOverlapping();

        $schedule
            ->command(DailyReport::class, ['--save-to-db'])
            ->daily()
            ->timezone($timezone)
            ->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
