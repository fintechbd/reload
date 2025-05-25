<?php

namespace Fintech\Reload\Listeners\Deposit;

use Fintech\Core\Enums\Transaction\OrderType;
use Fintech\Reload\Events\InteracTransferReceived;
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
        $orderType = OrderType::tryFrom($event->deposit->order_data['order_type']);

        if ($orderType == OrderType::InteracDeposit) {
            reload()->assignVendor()->requestPayout($event->deposit);
        }
    }

    /**
     * Handle a failure.
     */
    public function failed(InteracTransferReceived $event, \Throwable $exception): void
    {
        reload()->deposit()->update($event->deposit->getKey(), [
            'status' => \Fintech\Core\Enums\Transaction\OrderStatus::AdminVerification->value,
            'notes' => $exception->getMessage(),
        ]);
    }
}
