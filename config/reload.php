<?php

// config for Fintech/Reload
use Fintech\Reload\Models\CurrencySwap;
use Fintech\Reload\Models\Deposit;
use Fintech\Reload\Models\RequestMoney;
use Fintech\Reload\Models\WalletToWallet;
use Fintech\Reload\Repositories\Eloquent\CurrencySwapRepository;
use Fintech\Reload\Repositories\Eloquent\DepositRepository;
use Fintech\Reload\Repositories\Eloquent\RequestMoneyRepository;
use Fintech\Reload\Repositories\Eloquent\WalletToWalletRepository;

return [

    /*
    |--------------------------------------------------------------------------
    | Enable Module APIs
    |--------------------------------------------------------------------------
    | this setting enable the api will be available or not
    */
    'enabled' => env('PACKAGE_RELOAD_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Reload Group Root Prefix
    |--------------------------------------------------------------------------
    |
    | This value will be added to all your routes from this package
    | Example: APP_URL/{root_prefix}/api/reload/action
    |
    | Note: while adding prefix add closing ending slash '/'
    */

    'root_prefix' => 'test/',

    /*
    |--------------------------------------------------------------------------
    | Deposit Model
    |--------------------------------------------------------------------------
    |
    | This value will be used to across system where model is needed
    */
    'deposit_model' => Deposit::class,

    /*
    |--------------------------------------------------------------------------
    | CurrencySwap Model
    |--------------------------------------------------------------------------
    |
    | This value will be used to across system where model is needed
    */
    'currency_swap_model' => CurrencySwap::class,

    /*
    |--------------------------------------------------------------------------
    | WalletToWallet Model
    |--------------------------------------------------------------------------
    |
    | This value will be used to across system where model is needed
    */
    'wallet_to_wallet_model' => WalletToWallet::class,

    /*
    |--------------------------------------------------------------------------
    | RequestMoney Model
    |--------------------------------------------------------------------------
    |
    | This value will be used to across system where model is needed
    */
    'request_money_model' => RequestMoney::class,

    //** Model Config Point Do not Remove **//

    /*
    |--------------------------------------------------------------------------
    | Repositories
    |--------------------------------------------------------------------------
    |
    | This value will be used across systems where a repositoy instance is needed
    */

    'repositories' => [
        \Fintech\Reload\Interfaces\DepositRepository::class => DepositRepository::class,
        \Fintech\Reload\Interfaces\CurrencySwapRepository::class => CurrencySwapRepository::class,
        \Fintech\Reload\Interfaces\WalletToWalletRepository::class => WalletToWalletRepository::class,
        \Fintech\Reload\Interfaces\RequestMoneyRepository::class => RequestMoneyRepository::class,

        //** Repository Binding Config Point Do not Remove **//
    ],

];
