<?php

namespace Fintech\Reload\Services;

use Fintech\Reload\Interfaces\WalletToBankRepository;

/**
 * Class WalletToBankService
 */
class WalletToBankService
{
    /**
     * WalletToBankService constructor.
     */
    public function __construct(WalletToBankRepository $walletToBankRepository)
    {
        $this->walletToBankRepository = $walletToBankRepository;
    }

    public function find($id, $onlyTrashed = false)
    {
        return $this->walletToBankRepository->find($id, $onlyTrashed);
    }

    public function update($id, array $inputs = [])
    {
        return $this->walletToBankRepository->update($id, $inputs);
    }

    public function destroy($id)
    {
        return $this->walletToBankRepository->delete($id);
    }

    public function restore($id)
    {
        return $this->walletToBankRepository->restore($id);
    }

    public function export(array $filters)
    {
        return $this->walletToBankRepository->list($filters);
    }

    /**
     * @return mixed
     */
    public function list(array $filters = [])
    {
        return $this->walletToBankRepository->list($filters);

    }

    public function import(array $filters)
    {
        return $this->walletToBankRepository->create($filters);
    }

    public function create(array $inputs = [])
    {
        return $this->walletToBankRepository->create($inputs);
    }
}
