<?php

namespace Fintech\Reload\Repositories\Eloquent;

use Fintech\Reload\Interfaces\WalletToAtmRepository as InterfacesWalletToAtmRepository;
use Fintech\Reload\Models\WalletToAtm;
use Fintech\Transaction\Repositories\Eloquent\OrderRepository;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Class WalletToAtmRepository
 */
class WalletToAtmRepository extends OrderRepository implements InterfacesWalletToAtmRepository
{
    public function __construct()
    {
        parent::__construct(config('fintech.reload.wallet_to_atm_model', WalletToAtm::class));
    }

    /**
     * return a list or pagination of items from
     * filtered options
     *
     * @return Paginator|Collection
     *
     * @throws BindingResolutionException
     */
    public function list(array $filters = [])
    {
        return parent::list($filters);

    }
}
