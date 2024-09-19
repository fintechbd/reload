<?php

namespace Fintech\Reload\Jobs\Deposits;

use Fintech\Reload\Events\InteracTransferReceived;
use Fintech\Reload\Facades\Reload;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class InitInteracPaymentJob implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * The number of times the queued listener may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Determine the time at which the listener should timeout.
     */
    public function retryUntil()
    {
        return now()->addSeconds(30);
    }

    /**
     * Handle the event.
     */
    public function handle(InteracTransferReceived $event): void
    {
        Reload::assignVendor()->initPayment($event->deposit);
    }

    /**
     * Handle a failure.
     */
    public function failed(InteracTransferReceived $event, \Throwable $exception): void
    {
        // ...
    }
}
