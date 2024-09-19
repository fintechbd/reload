<?php

namespace Fintech\Reload\Facades;

use Fintech\Reload\Services\AssignVendorService;
use Fintech\Reload\Services\CurrencySwapService;
use Fintech\Reload\Services\DepositService;
use Fintech\Reload\Services\RequestMoneyService;
use Fintech\Reload\Services\WalletToAtmService;
use Fintech\Reload\Services\WalletToBankService;
use Fintech\Reload\Services\WalletToPrepaidCardService;
use Fintech\Reload\Services\WalletToWalletService;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \Illuminate\Contracts\Pagination\Paginator|\Illuminate\Support\Collection|DepositService deposit(array $filters = null)
 * @method static \Illuminate\Contracts\Pagination\Paginator|\Illuminate\Support\Collection|CurrencySwapService currencySwap(array $filters = null)
 * @method static \Illuminate\Contracts\Pagination\Paginator|\Illuminate\Support\Collection|WalletToWalletService walletToWallet(array $filters = null)
 * @method static \Illuminate\Contracts\Pagination\Paginator|\Illuminate\Support\Collection|RequestMoneyService requestMoney(array $filters = null)
 * @method static \Illuminate\Contracts\Pagination\Paginator|\Illuminate\Support\Collection|WalletToBankService walletToBank(array $filters = null)
 * @method static \Illuminate\Contracts\Pagination\Paginator|\Illuminate\Support\Collection|WalletToAtmService walletToAtm(array $filters = null)
 * @method static \Illuminate\Contracts\Pagination\Paginator|\Illuminate\Support\Collection|WalletToPrepaidCardService walletToPrepaidCard(array $filters = null)
 * @method static \Illuminate\Contracts\Pagination\Paginator|\Illuminate\Support\Collection|AssignVendorService assignVendor(array $filters = null)
 *
 * // Crud Service Method Point Do not Remove //
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
