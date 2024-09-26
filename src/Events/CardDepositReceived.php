<?php

namespace Fintech\Reload\Events;

use Fintech\Business\Facades\Business;
use Fintech\Reload\Facades\Reload;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CardDepositReceived implements ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $deposit;

    /**
     * Create a new event instance.
     */
    public function __construct($cardDeposit)
    {
        $timeline = $cardDeposit->timeline;

        $service = Business::service()->find($cardDeposit->service_id);

        $timeline[] = [
            'message' => ucwords(strtolower($service->service_name)).' card deposit received',
            'flag' => 'info',
            'timestamp' => now(),
        ];

        $this->deposit = Reload::deposit()->update($cardDeposit->getKey(), ['timeline' => $timeline]);
    }
}
