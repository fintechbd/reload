<?php

namespace Fintech\Reload\Events;

use Fintech\Reload\Facades\Reload;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BankDepositReceived
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $deposit;

    /**
     * Create a new event instance.
     */
    public function __construct($BankDeposit)
    {
        $timeline = $BankDeposit->timeline;

        $timeline[] = [
            'message' => 'Bank deposit received',
            'flag' => 'info',
            'timestamp' => now(),
        ];

        $this->deposit = Reload::deposit()->update($BankDeposit->getKey(), ['timeline' => $timeline]);
    }
}
