<?php

namespace Fintech\Reload\Listeners\Deposits;

use Fintech\Reload\Events\InteracTransferReceived;
use Fintech\Reload\Facades\Reload;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class InitInteracPayment implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * The number of times the queued listener may be attempted.
     *
     * @var int
     */
    public $tries = 1;

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
        Reload::deposit()->update($event->deposit->getKey(), [
            'status' => \Fintech\Core\Enums\Transaction\OrderStatus::AdminVerification->value,
            'notes' => $exception->getMessage(),
        ]);
    }
}
