<?php

namespace Fintech\Reload\Jobs\Deposit;

use Fintech\Reload\Events\InteracTransferReceived;
use Fintech\Reload\Facades\Reload;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class InteracExpiredRequestRejectJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var \Fintech\Core\Abstracts\BaseModel|null
     */
    private $deposit;

    /**
     * Create a new job instance.
     */
    public function __construct($order_id)
    {
        $this->deposit = Reload::deposit()->find($order_id);
    }

    /**
     * Handle the event.
     */
    public function handle(InteracTransferReceived $event): void
    {
        Reload::assignVendor()->requestPayout($event->deposit);
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