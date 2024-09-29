<?php

namespace Fintech\Reload\Events;

use Fintech\Reload\Facades\Reload;
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
    public function __construct($interacTransfer)
    {
        $timeline = $interacTransfer->timeline;

        $timeline[] = [
            'message' => 'Interac-E-Transfer deposit received',
            'flag' => 'info',
            'timestamp' => now(),
        ];

        $this->deposit = Reload::deposit()->update($interacTransfer->getKey(), ['timeline' => $timeline]);
    }
}
