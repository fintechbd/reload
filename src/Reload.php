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
    public function deposit($filters = null)
    {
        return \singleton(DepositService::class, $filters);
    }

    public function currencySwap($filters = null)
    {
        return \singleton(CurrencySwapService::class, $filters);
    }

    public function walletToWallet($filters = null)
    {
        return \singleton(WalletToWalletService::class, $filters);
    }

    public function requestMoney($filters = null)
    {
        return \singleton(RequestMoneyService::class, $filters);
    }

    public function walletToBank($filters = null)
    {
        return \singleton(WalletToBankService::class, $filters);
    }

    public function walletToAtm($filters = null)
    {
        return \singleton(WalletToAtmService::class, $filters);
    }

    public function walletToPrepaidCard($filters = null)
    {
        return \singleton(WalletToPrepaidCardService::class, $filters);
    }

    public function assignVendor()
    {
        return \app(AssignVendorService::class);
    }

    //** Crud Service Method Point Do not Remove **//

}
