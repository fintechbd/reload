<?php

namespace Fintech\Reload\Repositories\Eloquent;

use Fintech\Reload\Interfaces\RequestMoneyRepository as InterfacesRequestMoneyRepository;
use Fintech\Reload\Models\RequestMoney;
use Fintech\Transaction\Repositories\Eloquent\OrderRepository;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Class RequestMoneyRepository
 */
class RequestMoneyRepository extends OrderRepository implements InterfacesRequestMoneyRepository
{
    public function __construct()
    {
        parent::__construct(config('fintech.reload.request_money_model', RequestMoney::class));
    }

    /**
     * return a list or pagination of items from
     * filtered options
     *
     * @return Paginator|Collection
     * @throws BindingResolutionException
     */
    public function list(array $filters = [])
    {
        return parent::list($filters);

    }
}
