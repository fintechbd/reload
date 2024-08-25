<?php

namespace Fintech\Reload\Repositories\Eloquent;

use Fintech\Core\Repositories\EloquentRepository;
use Fintech\Reload\Interfaces\WalletToBankRepository as InterfacesWalletToBankRepository;
use Fintech\Reload\Models\WalletToBank;
use Fintech\Transaction\Repositories\Eloquent\OrderRepository;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Class WalletToBankRepository
 */
class WalletToBankRepository extends OrderRepository implements InterfacesWalletToBankRepository
{
    public function __construct()
    {
        parent::__construct(config('fintech.reload.wallet_to_bank_model', WalletToBank::class));
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
