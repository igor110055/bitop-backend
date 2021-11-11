<?php
namespace App\Jobs;

abstract class ExpBackoffJob
{
    public $tries = 1; // override default and make sure we don't requeue automatically
    public $currentRetryCount = 1;
    public $maxRetries = 7; // 3, 9, 27, 81, 243, 729, 2187 seconds
    public $backoffFactor = 3;

    public function failed(\Throwable $e)
    {
        if ($this->currentRetryCount <= $this->maxRetries) {
            $this->delay(now()->addSeconds($this->backoffFactor ** $this->currentRetryCount));
            $this->currentRetryCount += 1;

            return dispatch($this);
        }
    }
}
