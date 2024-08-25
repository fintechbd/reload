<?php

namespace Fintech\Reload\Repositories\Eloquent;

use Fintech\Reload\Interfaces\WalletToPrepaidCardRepository as InterfacesWalletToPrepaidCardRepository;
use Fintech\Reload\Models\WalletToPrepaidCard;
use Fintech\Transaction\Repositories\Eloquent\OrderRepository;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Class WalletToPrepaidCardRepository
 */
class WalletToPrepaidCardRepository extends OrderRepository implements InterfacesWalletToPrepaidCardRepository
{
    public function __construct()
    {
        parent::__construct(config('fintech.reload.wallet_to_prepaid_card_model', WalletToPrepaidCard::class));
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
