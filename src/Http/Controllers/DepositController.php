<?php

namespace Fintech\Reload\Http\Controllers;

use Exception;
use Fintech\Business\Facades\Business;
use Fintech\Core\Enums\Auth\RiskProfile;
use Fintech\Core\Enums\Auth\SystemRole;
use Fintech\Core\Enums\Reload\DepositStatus;
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

            $depositor = $request->user('sanctum');

            $depositAccount = \Fintech\Transaction\Facades\Transaction::userAccount()->list([
                'user_id' => $depositor->getKey(),
                'country_id' => $request->input('source_country_id', $depositor->profile?->country_id)
            ])->first();

            if (!$depositAccount) {
                throw new Exception("User don't have account deposit balance");
            }

            $masterUser = \Fintech\Auth\Facades\Auth::user()->list([
                'role_name' => SystemRole::MasterUser->value,
                'country_id' => $request->input('source_country_id', $depositor->profile?->country_id)
            ])->first();

            if (!$masterUser) {
                throw new Exception("Master User Account not found for " . $request->input('source_country_id', $depositor->profile?->country_id) . ' country');
            }

            //set pre defined conditions of deposit
            $inputs['transaction_form_id'] = 1;
            $inputs['user_id'] = $request->input('user_id', $depositor->getKey());
            $inputs['sender_receiver_id'] = $masterUser->getKey();
            $inputs['is_refunded'] = false;
            $inputs['status'] = DepositStatus::Processing->value;
            $inputs['risk'] = RiskProfile::Low->value;
            $inputs['order_data']['created_by'] = $depositor->name;
            $inputs['order_data']['created_by_mobile_number'] = $depositor->mobile;
            $inputs['order_data']['created_at'] = now();
            $inputs['order_data']['current_amount'] = ($depositAccount->user_account_data['available_amount'] ?? 0) + $inputs['amount'];
            $inputs['order_data']['previous_amount'] = $depositAccount->user_account_data['available_amount'] ?? 0;
            $inputs['converted_amount'] = $inputs['amount'];
            $inputs['converted_currency'] = $inputs['currency'];

            $deposit = Reload::deposit()->create($inputs);

            if (!$deposit) {
                throw (new StoreOperationException)->setModel(config('fintech.reload.deposit_model'));
            }

            $order_data = $deposit->order_data;
            $order_data['purchase_number'] = entry_number($deposit->getKey(), $deposit->sourceCountry->iso3, OrderStatusConfig::Purchased->value);

            Reload::deposit()->update($deposit->getKey(), ['order_data' => $order_data, 'order_number' => $order_data['purchase_number']]);

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

            if (!$deposit) {
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
            $deposit = $this->authenticateDeposit($id, DepositStatus::Processing, DepositStatus::Rejected);

            $approver = $request->user('sanctum');

            $updateData = $deposit->toArray();
            $updateData['status'] = DepositStatus::Rejected->value;
            $updateData['order_data']['rejected_by'] = $approver->name;
            $updateData['order_data']['rejected_at'] = now();
            $updateData['order_data']['rejected_number'] = entry_number($deposit->getKey(), $deposit->sourceCountry->iso3, OrderStatusConfig::Rejected->value);
            $updateData['order_number'] = entry_number($deposit->getKey(), $deposit->sourceCountry->iso3, OrderStatusConfig::Rejected->value);
            $updateData['order_data']['rejected_by_mobile_number'] = $approver->mobile;
            $updateData['order_data']['previous_amount'] = $depositAccount->user_account_data['available_amount'] ?? 0;
            $updateData['order_data']['current_amount'] = $updateData['order_data']['previous_amount'] - $updateData['amount'];

            if (!Reload::deposit()->update($deposit->getKey(), $updateData)) {
                throw new Exception(__('reload::messages.status_change_failed', [
                        'current_status' => $deposit->currentStatus(),
                        'target_status' => DepositStatus::Rejected->name,
                    ])
                );
            }

            return $this->success(__('reload::messages.deposit.status_change_success', [
                    'status' => DepositStatus::Rejected->name,
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

            $deposit = $this->authenticateDeposit($id, DepositStatus::Processing, DepositStatus::Accepted);

            $depositor = $deposit->user;

            $depositedAccount = \Fintech\Transaction\Facades\Transaction::userAccount()->list([
                'user_id' => $depositor->getKey(),
                'country_id' => $deposit->destination_country_id
            ])->first();


            $updateData = $deposit->toArray();
            $updateData['status'] = DepositStatus::Accepted->value;
            $updateData['order_data']['accepted_by'] = $request->user('sanctum')->name;
            $updateData['order_data']['accepted_at'] = now();
            $updateData['order_data']['accepted_number'] = entry_number($deposit->getKey(), $deposit->sourceCountry->iso3, OrderStatusConfig::Accepted->value);
            $updateData['order_number'] = entry_number($deposit->getKey(), $deposit->sourceCountry->iso3, OrderStatusConfig::Accepted->value);
            $updateData['order_data']['accepted_by_mobile_number'] = $request->user('sanctum')->mobile;
            $updateData['order_data']['service_stat_data'] = Business::serviceStat()->serviceStateData($deposit);

            //TODO Coming from UserAccount
            $updateData['order_data']['previous_amount'] = $depositedAccount->user_account_data['available_amount'];
            $updateData['order_data']['current_amount'] = ($updateData['order_data']['previous_amount'] + $updateData['amount']);

            if (!Reload::deposit()->update($deposit->getKey(), $updateData)) {
                throw new Exception(__('reload::messages.status_change_failed', [
                        'current_status' => $deposit->currentStatus(),
                        'target_status' => DepositStatus::Accepted->name,
                    ])
                );
            }

            $transactionOrder = Transaction::order()->find($deposit->getKey());
            $get_some_data = Reload::deposit()->depositAccept($transactionOrder);

            return $this->success(__('reload::messages.deposit.status_change_success', [
                    'status' => DepositStatus::Accepted->name,
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

            $deposit = $this->authenticateDeposit($id, DepositStatus::Accepted, DepositStatus::Cancelled);

            $updateData = $deposit->toArray();
            $updateData['status'] = DepositStatus::Cancelled->value;
            $updateData['order_data']['cancelled_by'] = $request->user('sanctum')->name;
            $updateData['order_data']['cancelled_at'] = now();
            $updateData['order_data']['cancelled_number'] = entry_number($deposit->getKey(), $deposit->sourceCountry->iso3, OrderStatusConfig::Cancelled->value);
            $updateData['order_number'] = entry_number($deposit->getKey(), $deposit->sourceCountry->iso3, OrderStatusConfig::Cancelled->value);
            $updateData['order_data']['cancelled_by_mobile_number'] = $request->user('sanctum')->mobile;
            $updateData['order_data']['previous_amount'] = $updateData['order_data']['current_amount'];
            $updateData['order_data']['current_amount'] = ($updateData['order_data']['current_amount'] - $deposit->amount);

            if (!Reload::deposit()->update($deposit->getKey(), $updateData)) {
                throw new Exception(__('reload::messages.status_change_failed', [
                        'current_status' => $deposit->currentStatus(),
                        'target_status' => DepositStatus::Cancelled->name,
                    ])
                );
            }

            $transactionOrder = Transaction::order()->find($deposit->getKey());
            $get_some_data = Reload::deposit()->depositCancel($transactionOrder);

            return $this->success(__('reload::messages.deposit.status_change_success', [
                    'status' => DepositStatus::Cancelled->name,
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

        if (!$deposit) {
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
