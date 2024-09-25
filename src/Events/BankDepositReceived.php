<?php

namespace Fintech\Reload\Events;

use Fintech\Business\Facades\Business;
use Fintech\Reload\Facades\Reload;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BankDepositReceived implements ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $deposit;

    /**
     * Create a new event instance.
     */
    public function __construct($BankDeposit)
    {
        $timeline = $BankDeposit->timeline;

        $service = Business::service()->find($BankDeposit->service_id);

        $timeline[] = [
            'message' => ucwords(strtolower($service->service_name)).' bank deposit received',
            'flag' => 'info',
            'timestamp' => now(),
        ];

        $this->deposit = Reload::deposit()->update($BankDeposit->getKey(), ['timeline' => $timeline]);
    }
}
