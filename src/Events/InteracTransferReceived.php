<?php

namespace Fintech\Reload\Events;

use Fintech\Core\Abstracts\BaseModel;
use Fintech\Reload\Facades\Reload;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InteracTransferReceived implements ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public ?BaseModel $deposit;

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
