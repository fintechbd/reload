<?php

namespace Fintech\Reload\Http\Controllers;

use Exception;
use Fintech\Business\Facades\Business;
use Fintech\Core\Enums\Auth\RiskProfile;
use Fintech\Core\Enums\Transaction\OrderStatus;
use Fintech\Core\Enums\Transaction\OrderStatusConfig;
use Fintech\Core\Exceptions\StoreOperationException;
use Fintech\Core\Traits\ApiResponseTrait;
use Fintech\Reload\Facades\Reload;
use Fintech\Reload\Http\Requests\CheckDepositRequest;
use Fintech\Reload\Http\Requests\ImportDepositRequest;
use Fintech\Reload\Http\Requests\IndexDepositRequest;
use Fintech\Reload\Http\Requests\StoreDepositRequest;
use Fintech\Reload\Http\Resources\DepositCollection;
use Fintech\Reload\Http\Resources\DepositResource;
use Fintech\Transaction\Facades\Transaction;
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

            //set pre defined conditions of deposit
            $inputs['transaction_form_id'] = 1;
            $inputs['user_id'] = $inputs['user_id'] ?? auth()->user()->getKey();
            //TODO Find Master User ID for this serving country
            $inputs['sender_receiver_id'] = 1;
            ///
            $inputs['is_refunded'] = false;
            $inputs['status'] = OrderStatus::Processing->value;
            $inputs['risk'] = RiskProfile::Low->value;
            $inputs['order_data']['created_by'] = $request->user()->name;
            $inputs['order_data']['created_by_mobile_number'] = $request->user()->mobile;
            $inputs['order_data']['created_at'] = now();
            $inputs['order_data']['current_amount'] = 0;
            $inputs['order_data']['previous_amount'] = 0;
            $inputs['converted_amount'] = $inputs['amount'];
            $inputs['converted_currency'] = $inputs['currency'];

            $deposit = Reload::deposit()->create($inputs);

            if (! $deposit) {
                throw (new StoreOperationException)->setModel(config('fintech.reload.deposit_model'));
            }

            $order_data = $deposit->order_data;
            $order_data['purchase_number'] = entry_number($deposit->getKey(), $deposit->sourceCountry->iso3, OrderStatusConfig::Purchased->value);

            Reload::deposit()->update($deposit->getKey(), ['order_data' => $order_data, 'order_number'=>$order_data['purchase_number']]);

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

            $updateData = $deposit->toArray();
            $updateData['status'] = OrderStatus::Rejected->value;
            $updateData['order_data']['rejected_by'] = $request->user()->name;
            $updateData['order_data']['rejected_at'] = now();
            $updateData['order_data']['rejected_number'] = entry_number($deposit->getKey(), $deposit->sourceCountry->iso3, OrderStatusConfig::Rejected->value);
            $updateData['order_number'] = entry_number($deposit->getKey(), $deposit->sourceCountry->iso3, OrderStatusConfig::Rejected->value);
            $updateData['order_data']['rejected_by_mobile_number'] = $request->user()->mobile;

            if (! Reload::deposit()->update($deposit->getKey(), $updateData)) {
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
            $updateData = $deposit->toArray();
            $updateData['status'] = OrderStatus::Accepted->value;
            $updateData['order_data']['accepted_by'] = $request->user()->name;
            $updateData['order_data']['accepted_at'] = now();
            $updateData['order_data']['accepted_number'] = entry_number($deposit->getKey(), $deposit->sourceCountry->iso3, OrderStatusConfig::Accepted->value);
            $updateData['order_number'] = entry_number($deposit->getKey(), $deposit->sourceCountry->iso3, OrderStatusConfig::Accepted->value);
            $updateData['order_data']['accepted_by_mobile_number'] = $request->user()->mobile;
            $updateData['order_data']['service_stat_data'] = Business::serviceStat()->serviceStateData($deposit);

            //TODO Coming from UserAccount
            $updateData['order_data']['previous_amount'] = 0;
            $updateData['order_data']['current_amount'] = ($updateData['order_data']['previous_amount'] + $deposit->amount);
            if (! Reload::deposit()->update($deposit->getKey(), $updateData)) {
                throw new Exception(__('reload::messages.status_change_failed', [
                    'current_status' => $deposit->currentStatus(),
                    'target_status' => OrderStatus::Accepted->name,
                ])
                );
            }
            $transactionOrder = Transaction::order()->find($deposit->getKey());
            $get_some_data = Reload::deposit()->depositAccept($transactionOrder);

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

            $updateData = $deposit->toArray();
            $updateData['status'] = OrderStatus::Cancelled->value;
            $updateData['order_data']['cancelled_by'] = $request->user()->name;
            $updateData['order_data']['cancelled_at'] = now();
            $updateData['order_data']['cancelled_number'] = entry_number($deposit->getKey(), $deposit->sourceCountry->iso3, OrderStatusConfig::Cancelled->value);
            $updateData['order_number'] = entry_number($deposit->getKey(), $deposit->sourceCountry->iso3, OrderStatusConfig::Cancelled->value);
            $updateData['order_data']['cancelled_by_mobile_number'] = $request->user()->mobile;
            $updateData['order_data']['previous_amount'] = $updateData['order_data']['current_amount'];
            $updateData['order_data']['current_amount'] = ($updateData['order_data']['current_amount'] - $deposit->amount);

            if (! Reload::deposit()->update($deposit->getKey(), $updateData)) {
                throw new Exception(__('reload::messages.status_change_failed', [
                    'current_status' => $deposit->currentStatus(),
                    'target_status' => OrderStatus::Cancelled->name,
                ])
                );
            }

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
