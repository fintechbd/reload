<?php

namespace Fintech\Reload\Services;


use Fintech\Reload\Interfaces\WalletTransferRepository;

/**
 * Class WalletTransferService
 * @package Fintech\Reload\Services
 *
 */
class WalletTransferService
{
    /**
     * WalletTransferService constructor.
     * @param WalletTransferRepository $walletTransferRepository
     */
    public function __construct(WalletTransferRepository $walletTransferRepository) {
        $this->walletTransferRepository = $walletTransferRepository;
    }

    /**
     * @param array $filters
     * @return mixed
     */
    public function list(array $filters = [])
    {
        return $this->walletTransferRepository->list($filters);

    }

    public function create(array $inputs = [])
    {
        return $this->walletTransferRepository->create($inputs);
    }

    public function find($id, $onlyTrashed = false)
    {
        return $this->walletTransferRepository->find($id, $onlyTrashed);
    }

    public function update($id, array $inputs = [])
    {
        return $this->walletTransferRepository->update($id, $inputs);
    }

    public function destroy($id)
    {
        return $this->walletTransferRepository->delete($id);
    }

    public function restore($id)
    {
        return $this->walletTransferRepository->restore($id);
    }

    public function export(array $filters)
    {
        return $this->walletTransferRepository->list($filters);
    }

    public function import(array $filters)
    {
        return $this->walletTransferRepository->create($filters);
    }
}
