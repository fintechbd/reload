<?php

namespace Fintech\Reload;

use Fintech\Reload\Services\CurrencySwapService;
use Fintech\Reload\Services\DepositService;
use Fintech\Reload\Services\RequestMoneyService;
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
     * @return \Fintech\Reload\Services\WalletToBankService
     */
    public function walletToBank()
    {
        return app(\Fintech\Reload\Services\WalletToBankService::class);
    }

    /**
     * @return \Fintech\Reload\Services\WalletToAtmService
     */
    public function walletToAtm()
    {
        return app(\Fintech\Reload\Services\WalletToAtmService::class);
    }

    /**
     * @return \Fintech\Reload\Services\WalletToPrepaidCardService
     */
    public function walletToPrepaidCard()
    {
        return app(\Fintech\Reload\Services\WalletToPrepaidCardService::class);
    }

    //** Crud Service Method Point Do not Remove **//




}
