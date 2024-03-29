<?php

// config for Fintech/Reload

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
    'deposit_model' => \Fintech\Reload\Models\Deposit::class,

    /*
    |--------------------------------------------------------------------------
    | CurrencySwap Model
    |--------------------------------------------------------------------------
    |
    | This value will be used to across system where model is needed
    */
    'currency_swap_model' => \Fintech\Reload\Models\CurrencySwap::class,

    /*
    |--------------------------------------------------------------------------
    | WalletToWallet Model
    |--------------------------------------------------------------------------
    |
    | This value will be used to across system where model is needed
    */
    'wallet_to_wallet_model' => \Fintech\Reload\Models\WalletToWallet::class,

    /*
    |--------------------------------------------------------------------------
    | RequestMoney Model
    |--------------------------------------------------------------------------
    |
    | This value will be used to across system where model is needed
    */
    'request_money_model' => \Fintech\Reload\Models\RequestMoney::class,

    /*
    |--------------------------------------------------------------------------
    | WalletToBank Model
    |--------------------------------------------------------------------------
    |
    | This value will be used to across system where model is needed
    */
    'wallet_to_bank_model' => \Fintech\Reload\Models\WalletToBank::class,

    /*
    |--------------------------------------------------------------------------
    | WalletToAtm Model
    |--------------------------------------------------------------------------
    |
    | This value will be used to across system where model is needed
    */
    'wallet_to_atm_model' => \Fintech\Reload\Models\WalletToAtm::class,

    /*
    |--------------------------------------------------------------------------
    | WalletToPrepaidCard Model
    |--------------------------------------------------------------------------
    |
    | This value will be used to across system where model is needed
    */
    'wallet_to_prepaid_card_model' => \Fintech\Reload\Models\WalletToPrepaidCard::class,

    //** Model Config Point Do not Remove **//

    /*
    |--------------------------------------------------------------------------
    | Repositories
    |--------------------------------------------------------------------------
    |
    | This value will be used across systems where a repositoy instance is needed
    */

    'repositories' => [
        \Fintech\Reload\Interfaces\DepositRepository::class => \Fintech\Reload\Repositories\Eloquent\DepositRepository::class,
        \Fintech\Reload\Interfaces\CurrencySwapRepository::class => \Fintech\Reload\Repositories\Eloquent\CurrencySwapRepository::class,
        \Fintech\Reload\Interfaces\WalletToWalletRepository::class => \Fintech\Reload\Repositories\Eloquent\WalletToWalletRepository::class,
        \Fintech\Reload\Interfaces\RequestMoneyRepository::class => \Fintech\Reload\Repositories\Eloquent\RequestMoneyRepository::class,

        \Fintech\Reload\Interfaces\WalletToBankRepository::class => \Fintech\Reload\Repositories\Eloquent\WalletToBankRepository::class,

        \Fintech\Reload\Interfaces\WalletToAtmRepository::class => \Fintech\Reload\Repositories\Eloquent\WalletToAtmRepository::class,

        \Fintech\Reload\Interfaces\WalletToPrepaidCardRepository::class => \Fintech\Reload\Repositories\Eloquent\WalletToPrepaidCardRepository::class,

        //** Repository Binding Config Point Do not Remove **//
    ],

];
