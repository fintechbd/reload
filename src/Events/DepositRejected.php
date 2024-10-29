<?php

namespace Fintech\Reload\Events;

use Fintech\Core\Abstracts\BaseModel;
use Fintech\Reload\Models\Deposit;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DepositRejected
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $deposit;

    /**
     * Create a new event instance.
     *
     * @param  Deposit|BaseModel|null  $deposit
     */
    public function __construct($deposit)
    {
        $this->deposit = $deposit;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel-name'),
        ];
    }
}
