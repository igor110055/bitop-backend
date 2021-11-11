<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Jobs\ExpBackoffJob;
use App\Models\{
    User,
    Announcement,
};
use App\Notifications\AnnouncementNotification;

class BroadcastEmailJob extends ExpBackoffJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $users;
    public $announcement;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($users, Announcement $announcement)
    {
        $this->users = $users;
        $this->announcement = $announcement;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        \Notification::send($this->users, new AnnouncementNotification($this->announcement));
    }

    public function failed(\Throwable $e)
    {
        \Log::debug('Job: Broadcast Email Job failed, FAILED EXCEPTION: '.$e);
        parent::failed($e);
    }
}
