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

    //** Crud Service Method Point Do not Remove **//

}
