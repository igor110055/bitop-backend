<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use DB;
use App\Models\Advertisement;

class MigrateExistedAdToPartialVersionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:ad-partial-version';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate existed advertisement to partial version';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $payment_window = 180;

        DB::transaction(function () use ($payment_window) {
            $matched = Advertisement::where('status', 'matched')->get();
            foreach ($matched as $m) {
                $m->update([
                    'status' => Advertisement::STATUS_COMPLETED,
                    'max_limit' => $m->price,
                    'min_limit' => $m->price,
                    'payment_window' => $payment_window,
                ]);
            }
        });
        $this->line('update existed matched advertisement successfully');

        DB::transaction(function () use ($payment_window) {
            $deleted = Advertisement::where('status', Advertisement::STATUS_DELETED)->get();
            foreach ($deleted as $d) {
                $d->update([
                    'remaining_amount' => $d->amount,
                    'remaining_fee' => $d->fee,
                    'max_limit' => $d->price,
                    'min_limit' => $d->price,
                    'payment_window' => $payment_window,
                ]);
                $d->delete();
            }
        });
        $this->line('update existed deleted advertisement successfully');

        DB::transaction(function () use ($payment_window) {
            $available = Advertisement::where('status', Advertisement::STATUS_AVAILABLE)->get();
            foreach ($available as $a) {
                $a->update([
                    'remaining_amount' => $a->amount,
                    'remaining_fee' => $a->fee,
                    'max_limit' => $a->price,
                    'min_limit' => $a->price,
                    'payment_window' => $payment_window,
                ]);
            }
        });
        $this->line('update existed claimed advertisement successfully');

        DB::transaction(function () use ($payment_window) {
            $unavailable = Advertisement::where('status', Advertisement::STATUS_UNAVAILABLE)->get();
            foreach ($unavailable as $u) {
                $u->update([
                    'remaining_amount' => $u->amount,
                    'remaining_fee' => $u->fee,
                    'max_limit' => $u->price,
                    'min_limit' => $u->price,
                    'payment_window' => $payment_window,
                ]);
            }
        });
        $this->line('update existed unavailable advertisement successfully');
    }
}
