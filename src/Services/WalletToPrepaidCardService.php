<?php

namespace Fintech\Reload\Services;

use Fintech\Reload\Interfaces\WalletToPrepaidCardRepository;

/**
 * Class WalletToPrepaidCardService
 */
class WalletToPrepaidCardService extends \Fintech\Core\Abstracts\Service
{
    /**
     * WalletToPrepaidCardService constructor.
     */
    public function __construct(WalletToPrepaidCardRepository $walletToPrepaidCardRepository)
    {
        $this->walletToPrepaidCardRepository = $walletToPrepaidCardRepository;
    }

    public function find($id, $onlyTrashed = false)
    {
        return $this->walletToPrepaidCardRepository->find($id, $onlyTrashed);
    }

    public function update($id, array $inputs = [])
    {
        return $this->walletToPrepaidCardRepository->update($id, $inputs);
    }

    public function destroy($id)
    {
        return $this->walletToPrepaidCardRepository->delete($id);
    }

    public function restore($id)
    {
        return $this->walletToPrepaidCardRepository->restore($id);
    }

    public function export(array $filters)
    {
        return $this->walletToPrepaidCardRepository->list($filters);
    }

    /**
     * @return mixed
     */
    public function list(array $filters = [])
    {
        return $this->walletToPrepaidCardRepository->list($filters);

    }

    public function import(array $filters)
    {
        return $this->walletToPrepaidCardRepository->create($filters);
    }

    public function create(array $inputs = [])
    {
        return $this->walletToPrepaidCardRepository->create($inputs);
    }
}
