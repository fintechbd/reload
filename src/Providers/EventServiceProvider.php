<?php

namespace Fintech\Reload\Providers;

use Fintech\Core\Listeners\TriggerNotification;
use Fintech\Reload\Events\BankDepositReceived;
use Fintech\Reload\Events\CardDepositReceived;
use Fintech\Reload\Events\CurrencySwapped;
use Fintech\Reload\Events\DepositAccepted;
use Fintech\Reload\Events\DepositCancelled;
use Fintech\Reload\Events\DepositReceived;
use Fintech\Reload\Events\DepositRejected;
use Fintech\Reload\Events\InteracTransferReceived;
use Fintech\Reload\Events\WalletTransferred;
use Fintech\Reload\Listeners\Deposit\InitInteracPayment;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        DepositRejected::class => [
            TriggerNotification::class,
        ],
        DepositReceived::class => [
            TriggerNotification::class,
        ],
        DepositAccepted::class => [
            TriggerNotification::class,
        ],
        DepositCancelled::class => [
            TriggerNotification::class,

        ],
        BankDepositReceived::class => [
            TriggerNotification::class,
        ],
        CardDepositReceived::class => [
            TriggerNotification::class,
        ],
        InteracTransferReceived::class => [
            InitInteracPayment::class,
            TriggerNotification::class,
        ],
        WalletTransferred::class => [
            TriggerNotification::class,
        ],
        CurrencySwapped::class => [
            TriggerNotification::class,
        ],
    ];
}
