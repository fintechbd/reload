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
     * @return \Fintech\Reload\Services\WalletTransferService
     */
    public function walletTransfer()
    {
        return app(\Fintech\Reload\Services\WalletTransferService::class);
    }

    //** Crud Service Method Point Do not Remove **//



}
