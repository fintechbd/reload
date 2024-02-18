<?php

namespace Fintech\Reload\Services;


use Fintech\Reload\Interfaces\RequestMoneyRepository;

/**
 * Class RequestMoneyService
 * @package Fintech\Reload\Services
 *
 */
class RequestMoneyService
{
    /**
     * RequestMoneyService constructor.
     * @param RequestMoneyRepository $requestMoneyRepository
     */
    public function __construct(RequestMoneyRepository $requestMoneyRepository) {
        $this->requestMoneyRepository = $requestMoneyRepository;
    }

    /**
     * @param array $filters
     * @return mixed
     */
    public function list(array $filters = [])
    {
        return $this->requestMoneyRepository->list($filters);

    }

    public function create(array $inputs = [])
    {
        return $this->requestMoneyRepository->create($inputs);
    }

    public function find($id, $onlyTrashed = false)
    {
        return $this->requestMoneyRepository->find($id, $onlyTrashed);
    }

    public function update($id, array $inputs = [])
    {
        return $this->requestMoneyRepository->update($id, $inputs);
    }

    public function destroy($id)
    {
        return $this->requestMoneyRepository->delete($id);
    }

    public function restore($id)
    {
        return $this->requestMoneyRepository->restore($id);
    }

    public function export(array $filters)
    {
        return $this->requestMoneyRepository->list($filters);
    }

    public function import(array $filters)
    {
        return $this->requestMoneyRepository->create($filters);
    }
}
