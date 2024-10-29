<?php

namespace Fintech\Reload\Events;

use Fintech\Business\Facades\Business;
use Fintech\Reload\Facades\Reload;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CardDepositReceived
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $deposit;

    /**
     * Create a new event instance.
     */
    public function __construct($deposit)
    {
        $timeline = $deposit->timeline;

        $service = Business::service()->find($deposit->service_id);

        $timeline[] = [
            'message' => ucwords(strtolower($service->service_name)).' card deposit received',
            'flag' => 'info',
            'timestamp' => now(),
        ];

        $this->deposit = Reload::deposit()->update($deposit->getKey(), ['timeline' => $timeline]);
    }
}
