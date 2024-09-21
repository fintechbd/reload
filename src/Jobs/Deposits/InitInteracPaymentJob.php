<?php

namespace Fintech\Reload\Jobs\Deposits;

use Fintech\Core\Exceptions\UpdateOperationException;
use Fintech\Core\Exceptions\VendorNotFoundException;
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
    public $tries = 1;

    /**
     * Handle the event.
     */
    public function handle(InteracTransferReceived $event): void
    {
        try {
            Reload::assignVendor()->initPayment($event->deposit);
        } catch (\ErrorException $e) {

        } catch (UpdateOperationException $e) {

        } catch (VendorNotFoundException $e) {

        }
    }

    //    /**
    //     * Handle a failure.
    //     */
    //    public function failed(InteracTransferReceived $event, \Throwable $exception): void
    //    {
    //        // ...
    //    }
}
