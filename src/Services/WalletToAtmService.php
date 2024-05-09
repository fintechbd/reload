<?php

namespace Fintech\Reload\Services;

use Fintech\Reload\Interfaces\WalletToAtmRepository;

/**
 * Class WalletToAtmService
 */
class WalletToAtmService
{
    /**
     * WalletToAtmService constructor.
     */
    public function __construct(WalletToAtmRepository $walletToAtmRepository)
    {
        $this->walletToAtmRepository = $walletToAtmRepository;
    }

    public function find($id, $onlyTrashed = false)
    {
        return $this->walletToAtmRepository->find($id, $onlyTrashed);
    }

    public function update($id, array $inputs = [])
    {
        return $this->walletToAtmRepository->update($id, $inputs);
    }

    public function destroy($id)
    {
        return $this->walletToAtmRepository->delete($id);
    }

    public function restore($id)
    {
        return $this->walletToAtmRepository->restore($id);
    }

    public function export(array $filters)
    {
        return $this->walletToAtmRepository->list($filters);
    }

    /**
     * @return mixed
     */
    public function list(array $filters = [])
    {
        return $this->walletToAtmRepository->list($filters);

    }

    public function import(array $filters)
    {
        return $this->walletToAtmRepository->create($filters);
    }

    public function create(array $inputs = [])
    {
        return $this->walletToAtmRepository->create($inputs);
    }
}
