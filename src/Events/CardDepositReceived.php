<?php

namespace Fintech\Reload\Events;

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
    public function __construct($cardDeposit)
    {
        $timeline = $cardDeposit->timeline;

        $timeline[] = [
            'message' => 'Card deposit received',
            'flag' => 'info',
            'timestamp' => now(),
        ];

        $this->deposit = Reload::deposit()->update($cardDeposit->getKey(), ['timeline' => $timeline]);
    }
}
