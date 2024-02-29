<?php

namespace Fintech\Reload\Repositories\Eloquent;

use Fintech\Reload\Interfaces\CurrencySwapRepository as InterfacesCurrencySwapRepository;
use Fintech\Reload\Models\CurrencySwap;
use Fintech\Transaction\Repositories\Eloquent\OrderRepository;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;

/**
 * Class CurrencySwapRepository
 */
class CurrencySwapRepository extends OrderRepository implements InterfacesCurrencySwapRepository
{
    public function __construct()
    {
        parent::__construct(config('fintech.reload.currency_swap_model', CurrencySwap::class));
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
