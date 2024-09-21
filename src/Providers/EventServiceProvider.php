<?php

namespace Fintech\Reload\Providers;

use Fintech\Reload\Events\BankDepositReceived;
use Fintech\Reload\Events\CardDepositReceived;
use Fintech\Reload\Events\CurrencySwapped;
use Fintech\Reload\Events\DepositAccepted;
use Fintech\Reload\Events\DepositCancelled;
use Fintech\Reload\Events\DepositRejected;
use Fintech\Reload\Events\InteracTransferReceived;
use Fintech\Reload\Events\WalletTransferred;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        CurrencySwapped::class => [

        ],
        BankDepositReceived::class => [

        ],
        CardDepositReceived::class => [

        ],
        InteracTransferReceived::class => [
            \Fintech\Reload\Jobs\Deposits\InitInteracPaymentJob::class,
        ],

        DepositRejected::class => [

        ],
        DepositAccepted::class => [

        ],
        DepositCancelled::class => [

        ],
        WalletTransferred::class => [

        ],
    ];
}
