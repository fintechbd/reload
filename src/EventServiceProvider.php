<?php

namespace Fintech\Reload;

use Fintech\Auth\Events\AccountFreezed;
use Fintech\Auth\Events\LoggedIn;
use Fintech\Auth\Events\LoggedOut;
use Fintech\Auth\Events\PasswordResetRequested;
use Fintech\Auth\Events\PasswordResetSuccessful;
use Fintech\Auth\Events\VerificationRequested;
use Fintech\Reload\Events\CurrencySwapped;
use Fintech\Reload\Events\DepositAccepted;
use Fintech\Reload\Events\DepositCancelled;
use Fintech\Reload\Events\DepositReceived;
use Fintech\Reload\Events\DepositRejected;
use Fintech\Reload\Events\WalletTransferred;
use Illuminate\Auth\Events\Lockout;
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
        DepositReceived::class => [

        ],
        DepositRejected::class => [

        ],
        DepositAccepted::class => [

        ],
        DepositCancelled::class => [

        ],
        WalletTransferred::class => [

        ]
    ];
}
