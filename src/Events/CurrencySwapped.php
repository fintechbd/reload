<?php

namespace Fintech\Reload\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CurrencySwapped
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $currencySwap;

    /**
     * Create a new event instance.
     * @param \Fintech\Reload\Models\CurrencySwap $currencySwap
     */
    public function __construct($currencySwap)
    {
        $this->currencySwap = $currencySwap;
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
