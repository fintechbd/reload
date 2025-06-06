<?php

namespace Fintech\Reload\Http\Controllers;

use Exception;
use Fintech\Core\Enums\Reload\DepositStatus;
use Fintech\Core\Enums\Transaction\OrderStatusConfig;
use Fintech\Core\Exceptions\StoreOperationException;
use Fintech\Reload\Events\DepositCancelled;
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
    /**
     * @throws ModelNotFoundException
     * @throws Exception
     */
    private function authenticateDeposit(string|int $id, array $requiredStatuses, DepositStatus $targetStatus): \Fintech\Core\Abstracts\BaseModel
    {
        $deposit = reload()->deposit()->find($id);

        if (! $deposit) {
            throw (new ModelNotFoundException)->setModel(config('fintech.reload.deposit_model'), $id);
        }

        $exists = false;

        foreach ($requiredStatuses as $requiredStatus) {
            if ($deposit->status->value === $requiredStatus->value) {
                $exists = true;
                break;
            }
        }

        if (! $exists) {
            throw new Exception(__('reload::messages.deposit.invalid_status', ['current_status' => $deposit->status->label(), 'target_status' => $targetStatus->label()]));
        }

        return $deposit;
    }

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
            $inputs['transaction_form_code'] = 'point_reload';
            $depositPaginate = reload()->deposit()->list($inputs);

            return new DepositCollection($depositPaginate);

        } catch (Exception $exception) {

            return response()->failed($exception);
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
        $inputs = $request->validated();

        $inputs['user_id'] = ($request->filled('user_id')) ? $request->input('user_id') : $request->user('sanctum')->getKey();

        try {
            $deposit = reload()->deposit()->create($inputs);

            $service = $deposit->service;

            return response()->created([
                'message' => __('core::messages.transaction.request_created', ['service' => ucwords(strtolower($service->service_name))]),
                'id' => $deposit->getKey(),
                'order_number' => $deposit->order_number ?? $deposit->order_data['purchase_number'],
            ]);

        } catch (Exception $exception) {
            transaction()->orderQueue()->removeFromQueueUserWise($inputs['user_id']);

            return response()->failed($exception);
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

            $deposit = reload()->deposit()->find($id);

            if (! $deposit) {
                throw (new ModelNotFoundException)->setModel(config('fintech.reload.deposit_model'), $id);
            }

            return new DepositResource($deposit);

        } catch (Exception $exception) {

            return response()->failed($exception);
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
        $inputs = $request->validated();

        try {
            $deposit = $this->authenticateDeposit($id, [DepositStatus::Processing, DepositStatus::Pending, DepositStatus::AdminVerification], DepositStatus::Rejected);

            $inputs['rejector'] = $request->user('sanctum');

            $deposit = reload()->deposit()->reject($deposit, $inputs);

            return response()->success(__('reload::messages.deposit.status_change_success', [
                'status' => DepositStatus::Rejected->name,
            ]));

        } catch (ModelNotFoundException $exception) {
            return response()->notfound($exception);

        } catch (Exception $exception) {
            transaction()->orderQueue()->removeFromQueueOrderWise($id);

            return response()->failed($exception);
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
        $inputs = $request->validated();

        try {
            $deposit = $this->authenticateDeposit($id, [DepositStatus::Processing, DepositStatus::Pending, DepositStatus::AdminVerification], DepositStatus::Accepted);

            $inputs['approver'] = $request->user('sanctum');

            $deposit = reload()->deposit()->accept($deposit, $inputs);

            return response()->success(__('reload::messages.deposit.status_change_success', ['status' => DepositStatus::Accepted->name]));

        } catch (ModelNotFoundException $exception) {
            return response()->notfound($exception);

        } catch (Exception $exception) {
            transaction()->orderQueue()->removeFromQueueOrderWise($id);

            return response()->failed($exception);
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
            if (Transaction::orderQueue()->addToQueueOrderWise($id) > 0) {
                $deposit = $this->authenticateDeposit($id, [DepositStatus::Accepted], DepositStatus::Cancelled);

                $depositor = $deposit->user;

                $approver = $request->user('sanctum');

                $depositedAccount = transaction()->userAccount()->findWhere(['user_id' => $depositor->getKey(), 'country_id' => $deposit->destination_country_id]);

                $updateData = $deposit->toArray();
                $updateData['status'] = DepositStatus::Cancelled->value;
                $updateData['order_data']['cancelled_by'] = $approver->name;
                $updateData['order_data']['cancelled_at'] = now();
                $updateData['order_data']['cancelled_number'] = entry_number($deposit->getKey(), $deposit->sourceCountry->iso3, OrderStatusConfig::Cancelled->value);
                $updateData['order_number'] = entry_number($deposit->getKey(), $deposit->sourceCountry->iso3, OrderStatusConfig::Cancelled->value);
                $updateData['order_data']['cancelled_by_mobile_number'] = $request->user('sanctum')->mobile;

                $updateData['order_data']['previous_amount'] = $updateData['order_data']['current_amount'];
                $updateData['order_data']['current_amount'] = ($updateData['order_data']['current_amount'] - $deposit->amount);

                $service = business()->service()->find($updateData['service_id']);

                $updateData['timeline'][] = [
                    'message' => "{$service->service_name} deposit cancelled by ({$approver->name}).",
                    'flag' => 'error',
                    'timestamp' => now(),
                ];

                if (! reload()->deposit()->update($deposit->getKey(), $updateData)) {
                    throw new Exception(__('reload::messages.status_change_failed', [
                        'current_status' => $deposit->status->label(),
                        'target_status' => DepositStatus::Cancelled->label(),
                    ]));
                }

                $transactionOrder = reload()->deposit()->find($deposit->getKey());
                $updatedUserBalance = reload()->deposit()->cancel($transactionOrder);

                // update User Account
                $depositedUpdatedAccount = $depositedAccount->toArray();
                $depositedUpdatedAccount['user_account_data']['deposit_amount'] = (float) $depositedUpdatedAccount['user_account_data']['deposit_amount'] + (float) $updatedUserBalance['deposit_amount'];
                $depositedUpdatedAccount['user_account_data']['available_amount'] = (float) $updatedUserBalance['current_amount'];

                if (! transaction()->userAccount()->update($depositedAccount->getKey(), $depositedUpdatedAccount)) {
                    throw new Exception(__('reload::messages.status_change_failed', [
                        'current_status' => $deposit->status->label(),
                        'target_status' => DepositStatus::Accepted->label(),
                    ]));
                }

                transaction()->orderQueue()->removeFromQueueOrderWise($id);

                event(new DepositCancelled($deposit));

                return response()->success(__('reload::messages.deposit.status_change_success', [
                    'status' => DepositStatus::Cancelled->label(),
                ]));
            } else {
                throw new Exception('Your another order is in process...!');
            }
        } catch (ModelNotFoundException $exception) {
            transaction()->orderQueue()->removeFromQueueOrderWise($id);

            return response()->notfound($exception);

        } catch (Exception $exception) {
            transaction()->orderQueue()->removeFromQueueOrderWise($id);

            return response()->failed($exception);
        }
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

            $depositPaginate = reload()->deposit()->export($inputs);

            return response()->exported(__('core::messages.resource.exported', ['model' => 'Deposit']));

        } catch (Exception $exception) {

            return response()->failed($exception);
        }
    }

    /**
     * @lrd:start
     * Create an exportable list of the *Deposit* resource as document.
     * After export job is done system will fire  export completed event
     *
     * @lrd:end
     */
    public function import(ImportDepositRequest $request): DepositCollection|JsonResponse
    {
        try {
            $inputs = $request->validated();

            $depositPaginate = reload()->deposit()->list($inputs);

            return new DepositCollection($depositPaginate);

        } catch (Exception $exception) {

            return response()->failed($exception);
        }
    }
}
