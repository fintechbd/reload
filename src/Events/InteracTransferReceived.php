<?php

namespace Fintech\Reload\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InteracTransferReceived
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $deposit;

    /**
     * Create a new event instance.
     */
    public function __construct($deposit)
    {
        $timeline = $deposit->timeline;

        $timeline[] = [
            'message' => 'Interac-E-Transfer deposit received',
            'flag' => 'info',
            'timestamp' => now(),
        ];

        $this->deposit = reload()->deposit()->update($deposit->getKey(), ['timeline' => $timeline]);
    }
}
