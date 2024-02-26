<?php

namespace Fintech\Reload\Repositories\Eloquent;

use Fintech\Reload\Interfaces\RequestMoneyRepository as InterfacesRequestMoneyRepository;
use Fintech\Reload\Models\RequestMoney;
use Fintech\Transaction\Repositories\Mongodb\OrderRepository;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;

/**
 * Class RequestMoneyRepository
 */
class RequestMoneyRepository extends OrderRepository implements InterfacesRequestMoneyRepository
{
    public function __construct()
    {
        $model = app(config('fintech.reload.request_money_model', RequestMoney::class));

        if (!$model instanceof Model) {
            throw new InvalidArgumentException("Eloquent repository require model class to be `Illuminate\Database\Eloquent\Model` instance.");
        }

        $this->model = $model;
    }

    /**
     * return a list or pagination of items from
     * filtered options
     *
     * @return Paginator|Collection
     */
    public function list(array $filters = [])
    {
        return parent::list($filters);

    }
}
