<?php

namespace Fintech\Reload\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DepositAccepted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $deposit;

    /**
     * Create a new event instance.
     *
     * @param  \Fintech\Reload\Models\Deposit  $deposit
     */
    public function __construct($deposit)
    {
        $this->deposit = $deposit;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel-name'),
        ];
    }
}