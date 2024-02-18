<?php

namespace Fintech\Reload;

class Reload
{
    /**
     * @return \Fintech\Reload\Services\DepositService
     */
    public function deposit()
    {
        return app(\Fintech\Reload\Services\DepositService::class);
    }

    /**
     * @return \Fintech\Reload\Services\CurrencySwapService
     */
    public function currencySwap()
    {
        return app(\Fintech\Reload\Services\CurrencySwapService::class);
    }

    /**
     * @return \Fintech\Reload\Services\WalletToWalletService
     */
    public function walletToWallet()
    {
        return app(\Fintech\Reload\Services\WalletToWalletService::class);
    }

    /**
     * @return \Fintech\Reload\Services\RequestMoneyService
     */
    public function requestMoney()
    {
        return app(\Fintech\Reload\Services\RequestMoneyService::class);
    }

    //** Crud Service Method Point Do not Remove **//


}
