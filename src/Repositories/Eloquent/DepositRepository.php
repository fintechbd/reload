<?php

namespace Fintech\Reload\Repositories\Eloquent;

use Fintech\Reload\Interfaces\DepositRepository as InterfacesDepositRepository;
use Fintech\Reload\Models\Deposit;
use Fintech\Transaction\Repositories\Eloquent\OrderRepository;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Class DepositRepository
 */
class DepositRepository extends OrderRepository implements InterfacesDepositRepository
{
    public function __construct()
    {
        parent::__construct(config('fintech.reload.deposit_model', Deposit::class));
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
