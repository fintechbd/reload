<?php

namespace Fintech\Reload\Events;

use Fintech\Core\Abstracts\BaseModel;
use Fintech\Reload\Models\Deposit;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DepositAccepted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $deposit;

    /**
     * Create a new event instance.
     *
     * @param  Deposit|BaseModel  $deposit
     */
    public function __construct($deposit)
    {
        $this->deposit = $deposit;
    }

}
