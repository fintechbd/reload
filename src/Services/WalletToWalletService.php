<?php

namespace Fintech\Reload\Services;


use Fintech\Reload\Interfaces\WalletToWalletRepository;

/**
 * Class WalletToWalletService
 * @package Fintech\Reload\Services
 *
 */
class WalletToWalletService
{
    /**
     * WalletToWalletService constructor.
     * @param WalletToWalletRepository $walletToWalletRepository
     */
    public function __construct(WalletToWalletRepository $walletToWalletRepository) {
        $this->walletToWalletRepository = $walletToWalletRepository;
    }

    /**
     * @param array $filters
     * @return mixed
     */
    public function list(array $filters = [])
    {
        return $this->walletToWalletRepository->list($filters);

    }

    public function create(array $inputs = [])
    {
        return $this->walletToWalletRepository->create($inputs);
    }

    public function find($id, $onlyTrashed = false)
    {
        return $this->walletToWalletRepository->find($id, $onlyTrashed);
    }

    public function update($id, array $inputs = [])
    {
        return $this->walletToWalletRepository->update($id, $inputs);
    }

    public function destroy($id)
    {
        return $this->walletToWalletRepository->delete($id);
    }

    public function restore($id)
    {
        return $this->walletToWalletRepository->restore($id);
    }

    public function export(array $filters)
    {
        return $this->walletToWalletRepository->list($filters);
    }

    public function import(array $filters)
    {
        return $this->walletToWalletRepository->create($filters);
    }
}
