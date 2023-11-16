<?php

namespace Fintech\Reload\Http\Controllers;

use Exception;
use Fintech\Core\Enums\Transaction\OrderStatus;
use Fintech\Core\Exceptions\StoreOperationException;
use Fintech\Core\Traits\ApiResponseTrait;
use Fintech\Reload\Facades\Reload;
use Fintech\Reload\Http\Requests\CheckDepositRequest;
use Fintech\Reload\Http\Requests\ImportDepositRequest;
use Fintech\Reload\Http\Requests\IndexDepositRequest;
use Fintech\Reload\Http\Requests\StoreDepositRequest;
use Fintech\Reload\Http\Resources\DepositCollection;
use Fintech\Reload\Http\Resources\DepositResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

/**
 * Class DepositController
 *
 * @lrd:start
 * This class handle create, display, update, delete & restore
 * operation related to Deposit
 *
 * @lrd:end
 */
class DepositController extends Controller
{
    use ApiResponseTrait;

    /**
     * @lrd:start
     * Return a listing of the *Deposit* resource as collection.
     *
     * *```paginate=false``` returns all resource as list not pagination*
     *
     * @lrd:end
     */
    public function index(IndexDepositRequest $request): DepositCollection|JsonResponse
    {
        try {
            $inputs = $request->validated();

            $depositPaginate = Reload::deposit()->list($inputs);

            return new DepositCollection($depositPaginate);

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Create a new *Deposit* resource in storage.
     *
     * @lrd:end
     *
     * @throws StoreOperationException
     */
    public function store(StoreDepositRequest $request): JsonResponse
    {
        try {
            $inputs = $request->validated();

            $deposit = Reload::deposit()->create($inputs);

            if (! $deposit) {
                throw (new StoreOperationException)->setModel(config('fintech.reload.deposit_model'));
            }

            return $this->created([
                'message' => __('core::messages.resource.created', ['model' => 'Deposit']),
                'id' => $deposit->id,
            ]);

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Return a specified *Deposit* resource found by id.
     *
     * @lrd:end
     *
     * @throws ModelNotFoundException
     */
    public function show(string|int $id): DepositResource|JsonResponse
    {
        try {

            $deposit = Reload::deposit()->find($id);

            if (! $deposit) {
                throw (new ModelNotFoundException)->setModel(config('fintech.reload.deposit_model'), $id);
            }

            return new DepositResource($deposit);

        } catch (ModelNotFoundException $exception) {

            return $this->notfound($exception->getMessage());

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Reject a  specified *Deposit* resource found by id.
     * if and only if deposit status is processing
     *
     * @lrd:end
     *
     * @throws ModelNotFoundException
     */
    public function reject(CheckDepositRequest $request, string|int $id): JsonResponse
    {
        try {
            $deposit = $this->authenticateDeposit($id, OrderStatus::Processing, OrderStatus::Rejected);

            $rejectedDeposit = $deposit->toArray();
            $rejectedDeposit['status'] = OrderStatus::Rejected->value;
            $rejectedDeposit['parent_id'] = $deposit->getKey();

            if (! Reload::deposit()->create($rejectedDeposit)) {
                throw new Exception(__('reload::messages.status_change_failed', [
                    'current_status' => $deposit->currentStatus(),
                    'target_status' => OrderStatus::Rejected->name,
                ])
                );
            }

            return $this->success(__('reload::messages.deposit.status_change_success', [
                'status' => OrderStatus::Rejected->name,
            ])
            );

        } catch (ModelNotFoundException $exception) {

            return $this->notfound($exception->getMessage());

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Accept a  specified *Deposit* resource found by id.
     * if and only if deposit status is processing
     *
     * @lrd:end
     *
     * @throws ModelNotFoundException
     */
    public function accept(CheckDepositRequest $request, string|int $id): JsonResponse
    {
        try {

            $deposit = $this->authenticateDeposit($id, OrderStatus::Processing, OrderStatus::Accepted);
            $acceptedDeposit = $deposit->toArray();
            $acceptedDeposit['status'] = OrderStatus::Accepted->value;
            $acceptedDeposit['parent_id'] = $deposit->getKey();

            if (! Reload::deposit()->create($acceptedDeposit)) {
                throw new Exception(__('reload::messages.status_change_failed', [
                    'current_status' => $deposit->currentStatus(),
                    'target_status' => OrderStatus::Accepted->name,
                ])
                );
            }

            Reload::deposit()->destroy($deposit->getKey());

            return $this->success(__('reload::messages.deposit.status_change_success', [
                'status' => OrderStatus::Accepted->name,
            ])
            );

        } catch (ModelNotFoundException $exception) {

            return $this->notfound($exception->getMessage());

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Cancel a  specified *Deposit* resource found by id.
     * if and only if deposit status is accepted
     *
     * @lrd:end
     *
     * @throws ModelNotFoundException
     */
    public function cancel(CheckDepositRequest $request, string|int $id): JsonResponse
    {
        try {

            $deposit = $this->authenticateDeposit($id, OrderStatus::Accepted, OrderStatus::Cancelled);

            $acceptedDeposit = $deposit->toArray();
            $acceptedDeposit['status'] = OrderStatus::Cancelled->value;
            $acceptedDeposit['parent_id'] = $deposit->getKey();

            if (! Reload::deposit()->create($acceptedDeposit)) {
                throw new Exception(__('reload::messages.status_change_failed', [
                    'current_status' => $deposit->currentStatus(),
                    'target_status' => OrderStatus::Accepted->name,
                ])
                );
            }

            Reload::deposit()->destroy($deposit->getKey());

            return $this->success(__('reload::messages.deposit.status_change_success', [
                'status' => OrderStatus::Cancelled->name,
            ])
            );

        } catch (ModelNotFoundException $exception) {

            return $this->notfound($exception->getMessage());

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    private function authenticateDeposit(string|int $id, \BackedEnum $requiredStatus, \BackedEnum $targetStatus): \Illuminate\Database\Eloquent\Model|\MongoDB\Laravel\Eloquent\Model
    {
        $deposit = Reload::deposit()->find($id);

        if (! $deposit) {
            throw (new ModelNotFoundException)->setModel(config('fintech.reload.deposit_model'), $id);
        }

        if ($deposit->currentStatus() != $requiredStatus->value) {
            throw new Exception(__('reload::messages.deposit.invalid_status', [
                'current_status' => $deposit->currentStatus(),
                'target_status' => $targetStatus->name,
            ])
            );
        }

        return $deposit;
    }

    /**
     * @lrd:start
     * Create a exportable list of the *Deposit* resource as document.
     * After export job is done system will fire  export completed event
     *
     * @lrd:end
     */
    public function export(IndexDepositRequest $request): JsonResponse
    {
        try {
            $inputs = $request->validated();

            $depositPaginate = Reload::deposit()->export($inputs);

            return $this->exported(__('core::messages.resource.exported', ['model' => 'Deposit']));

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Create a exportable list of the *Deposit* resource as document.
     * After export job is done system will fire  export completed event
     *
     * @lrd:end
     */
    public function import(ImportDepositRequest $request): DepositCollection|JsonResponse
    {
        try {
            $inputs = $request->validated();

            $depositPaginate = Reload::deposit()->list($inputs);

            return new DepositCollection($depositPaginate);

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }
}
