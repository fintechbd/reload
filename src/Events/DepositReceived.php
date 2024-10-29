<?php

namespace Fintech\Reload\Events;

use Fintech\Core\Abstracts\BaseModel;
use Fintech\Core\Enums\Transaction\OrderType;
use Fintech\Reload\Models\Deposit;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DepositReceived
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $deposit;

    /**
     * Create a new event instance.
     *
     * @param Deposit|BaseModel|null $deposit
     */
    public function __construct($deposit)
    {
        $orderType = OrderType::tryFrom($deposit->order_data['order_type']);

        match ($orderType) {
            OrderType::InteracDeposit => event(new InteracTransferReceived($deposit)),
            OrderType::CardDeposit => event(new CardDepositReceived($deposit)),
            OrderType::BankDeposit => event(new BankDepositReceived($deposit)),
            default => logger('Unknown Deposit Type', ['type' => $orderType, 'deposit' => $deposit]),
        };

        $this->deposit = $deposit;
    }
}
