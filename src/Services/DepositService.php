<?php

namespace Fintech\Reload\Services;

use Fintech\Reload\Interfaces\DepositRepository;

/**
 * Class DepositService
 */
class DepositService
{
    /**
     * DepositService constructor.
     */
    public function __construct(DepositRepository $depositRepository)
    {
        $this->depositRepository = $depositRepository;
    }

    /**
     * @return mixed
     */
    public function list(array $filters = [])
    {
        return $this->depositRepository->list($filters);

    }

    public function create(array $inputs = [])
    {
        return $this->depositRepository->create($inputs);
    }

    public function find($id, $onlyTrashed = false)
    {
        return $this->depositRepository->find($id, $onlyTrashed);
    }

    public function update($id, array $inputs = [])
    {
        return $this->depositRepository->update($id, $inputs);
    }

    public function destroy($id)
    {
        return $this->depositRepository->delete($id);
    }

    public function restore($id)
    {
        return $this->depositRepository->restore($id);
    }

    public function export(array $filters)
    {
        return $this->depositRepository->list($filters);
    }

    public function import(array $filters)
    {
        return $this->depositRepository->create($filters);
    }
}
