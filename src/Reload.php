<?php

namespace Fintech\Reload;

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
    public function deposit()
    {
        return app(DepositService::class);
    }

    /**
     * @return CurrencySwapService
     */
    public function currencySwap()
    {
        return app(CurrencySwapService::class);
    }

    /**
     * @return WalletToWalletService
     */
    public function walletToWallet()
    {
        return app(WalletToWalletService::class);
    }

    /**
     * @return RequestMoneyService
     */
    public function requestMoney()
    {
        return app(RequestMoneyService::class);
    }

    /**
     * @return WalletToBankService
     */
    public function walletToBank()
    {
        return app(WalletToBankService::class);
    }

    /**
     * @return WalletToAtmService
     */
    public function walletToAtm()
    {
        return app(WalletToAtmService::class);
    }

    /**
     * @return WalletToPrepaidCardService
     */
    public function walletToPrepaidCard()
    {
        return app(WalletToPrepaidCardService::class);
    }

    //** Crud Service Method Point Do not Remove **//

}
