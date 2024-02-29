<?php

namespace Fintech\Reload\Events;

use Fintech\Reload\Models\CurrencySwap;
use Illuminate\Broadcasting\Channel;
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
     *
     * @param  CurrencySwap  $currencySwap
     */
    public function __construct($currencySwap)
    {
        $this->currencySwap = $currencySwap;
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
