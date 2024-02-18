<?php

namespace Fintech\Reload\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Fintech\Reload\Services\DepositService deposit()
 * @method static \Fintech\Reload\Services\CurrencySwapService currencySwap()
 * @method static \Fintech\Reload\Services\WalletToWalletService walletToWallet()
 * @method static \Fintech\Reload\Services\RequestMoneyService requestMoney()
 *                                                                            // Crud Service Method Point Do not Remove //
 *
 * @see \Fintech\Reload\Reload
 */
class Reload extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Fintech\Reload\Reload::class;
    }
}
