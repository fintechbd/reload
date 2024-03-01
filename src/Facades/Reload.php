<?php

namespace Fintech\Reload\Facades;

use Fintech\Reload\Services\CurrencySwapService;
use Fintech\Reload\Services\DepositService;
use Fintech\Reload\Services\RequestMoneyService;
use Fintech\Reload\Services\WalletToWalletService;
use Illuminate\Support\Facades\Facade;

/**
 * @method static DepositService deposit()
 * @method static CurrencySwapService currencySwap()
 * @method static WalletToWalletService walletToWallet()
 * @method static RequestMoneyService requestMoney()
 * @method static \Fintech\Reload\Services\WalletToBankService walletToBank()
 * @method static \Fintech\Reload\Services\WalletToAtmService walletToAtm()
 * @method static \Fintech\Reload\Services\WalletToPrepaidCardService walletToPrepaidCard()
 *                                                                                          // Crud Service Method Point Do not Remove //
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
