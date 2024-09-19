<?php

namespace Fintech\Reload;

use Fintech\Reload\Services\AssignVendorService;
use Fintech\Reload\Services\CurrencySwapService;
use Fintech\Reload\Services\DepositService;
use Fintech\Reload\Services\RequestMoneyService;
use Fintech\Reload\Services\WalletToAtmService;
use Fintech\Reload\Services\WalletToBankService;
use Fintech\Reload\Services\WalletToPrepaidCardService;
use Fintech\Reload\Services\WalletToWalletService;

class Reload
{
    /**
     * @return DepositService
     */
    public function deposit($filters = null)
    {
        return \singleton(DepositService::class, $filters);
    }

    /**
     * @return CurrencySwapService
     */
    public function currencySwap($filters = null)
    {
        return \singleton(CurrencySwapService::class, $filters);
    }

    /**
     * @return WalletToWalletService
     */
    public function walletToWallet($filters = null)
    {
        return \singleton(WalletToWalletService::class, $filters);
    }

    /**
     * @return RequestMoneyService
     */
    public function requestMoney($filters = null)
    {
        return \singleton(RequestMoneyService::class, $filters);
    }

    /**
     * @return WalletToBankService
     */
    public function walletToBank($filters = null)
    {
        return \singleton(WalletToBankService::class, $filters);
    }

    /**
     * @return WalletToAtmService
     */
    public function walletToAtm($filters = null)
    {
        return \singleton(WalletToAtmService::class, $filters);
    }

    /**
     * @return WalletToPrepaidCardService
     */
    public function walletToPrepaidCard($filters = null)
    {
        return \singleton(WalletToPrepaidCardService::class, $filters);
    }

    /**
     * @return AssignVendorService
     */
    public function assignVendor($filters = null)
    {
        return \singleton(AssignVendorService::class, $filters);
    }

    //** Crud Service Method Point Do not Remove **//

}
