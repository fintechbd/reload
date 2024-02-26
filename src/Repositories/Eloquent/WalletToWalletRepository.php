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
        $model = app(config('fintech.reload.wallet_to_wallet_model', WalletToWallet::class));

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
     *
     * @throws BindingResolutionException
     */
    public function list(array $filters = [])
    {
        return parent::list($filters);

    }
}
