<?php

namespace Fintech\Reload\Repositories\Eloquent;

use Fintech\Reload\Interfaces\WalletToWalletRepository as InterfacesWalletToWalletRepository;
use Fintech\Reload\Models\WalletToWallet;
use Fintech\Transaction\Repositories\Eloquent\OrderRepository;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;

/**
 * Class WalletToWalletRepository
 */
class WalletToWalletRepository extends OrderRepository implements InterfacesWalletToWalletRepository
{
    public function __construct()
    {
        parent::__construct(config('fintech.reload.wallet_to_wallet_model', WalletToWallet::class));
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
