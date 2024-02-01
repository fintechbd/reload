<?php

namespace Fintech\Reload\Services;

use Fintech\Reload\Interfaces\CurrencySwapRepository;

/**
 * Class CurrencySwapService
 */
class CurrencySwapService
{
    /**
     * CurrencySwapService constructor.
     */
    public function __construct(CurrencySwapRepository $currencySwapRepository)
    {
        $this->currencySwapRepository = $currencySwapRepository;
    }

    /**
     * @return mixed
     */
    public function list(array $filters = [])
    {
        return $this->currencySwapRepository->list($filters);

    }

    public function create(array $inputs = [])
    {
        return $this->currencySwapRepository->create($inputs);
    }

    public function find($id, $onlyTrashed = false)
    {
        return $this->currencySwapRepository->find($id, $onlyTrashed);
    }

    public function update($id, array $inputs = [])
    {
        return $this->currencySwapRepository->update($id, $inputs);
    }

    public function destroy($id)
    {
        return $this->currencySwapRepository->delete($id);
    }

    public function restore($id)
    {
        return $this->currencySwapRepository->restore($id);
    }

    public function export(array $filters)
    {
        return $this->currencySwapRepository->list($filters);
    }

    public function import(array $filters)
    {
        return $this->currencySwapRepository->create($filters);
    }
}
