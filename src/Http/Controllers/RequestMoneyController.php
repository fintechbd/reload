<?php

namespace Fintech\Reload\Http\Controllers;

use BackedEnum;
use Exception;
use Fintech\Auth\Facades\Auth;
use Fintech\Business\Facades\Business;
use Fintech\Core\Enums\Auth\RiskProfile;
use Fintech\Core\Enums\Auth\SystemRole;
use Fintech\Core\Enums\Reload\DepositStatus;
use Fintech\Core\Enums\Transaction\OrderStatus;
use Fintech\Core\Enums\Transaction\OrderStatusConfig;
use Fintech\Core\Exceptions\DeleteOperationException;
use Fintech\Core\Exceptions\RestoreOperationException;
use Fintech\Core\Exceptions\StoreOperationException;
use Fintech\Core\Exceptions\UpdateOperationException;
use Fintech\Core\Traits\ApiResponseTrait;
use Fintech\Reload\Facades\Reload;
use Fintech\Reload\Http\Requests\CheckDepositRequest;
use Fintech\Reload\Http\Requests\ImportRequestMoneyRequest;
use Fintech\Reload\Http\Requests\IndexRequestMoneyRequest;
use Fintech\Reload\Http\Requests\StoreRequestMoneyRequest;
use Fintech\Reload\Http\Requests\UpdateRequestMoneyRequest;
use Fintech\Reload\Http\Resources\RequestMoneyCollection;
use Fintech\Reload\Http\Resources\RequestMoneyResource;
use Fintech\Transaction\Facades\Transaction;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

/**
 * Class RequestMoneyController
 *
 * @lrd:start
 * This class handle create, display, update, delete & restore
 * operation related to RequestMoney
 *
 * @lrd:end
 */
class RequestMoneyController extends Controller
{
    use ApiResponseTrait;

    /**
     * @lrd:start
     * Return a listing of the *RequestMoney* resource as collection.
     *
     * *```paginate=false``` returns all resource as list not pagination*
     *
     * @lrd:end
     */
    public function index(IndexRequestMoneyRequest $request): RequestMoneyCollection|JsonResponse
    {
        try {
            $inputs = $request->validated();
            //$inputs['transaction_form_id'] = Transaction::transactionForm()->list(['code' => 'request_money'])->first()->getKey();
            $inputs['transaction_form_code'] = 'request_money';
            //$inputs['service_id'] = Business::serviceType()->list(['service_type_slug'=>'request_money']);
            //$inputs['service_type_slug'] = 'request_money';

            $requestMoneyPaginate = Reload::requestMoney()->list($inputs);

            return new RequestMoneyCollection($requestMoneyPaginate);

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Create a new *RequestMoney* resource in storage.
     *
     * @lrd:end
     */
    public function store(StoreRequestMoneyRequest $request): JsonResponse
    {
        try {
            $inputs = $request->validated();
            if ($request->input('user_id') > 0) {
                $user_id = $request->input('user_id');
                $depositor = Auth::user()->find($request->input('user_id'));
            } else {
                $depositor = $request->user('sanctum');
            }

            if (Transaction::orderQueue()->addToQueueUserWise(($user_id ?? $depositor->getKey())) > 0) {

                $depositAccount = Transaction::userAccount()->list([
                    'user_id' => $user_id ?? $depositor->getKey(),
                    'currency' => $request->input('currency', $depositor->profile?->presentCountry?->currency),
                ])->first();

                if (! $depositAccount) {
                    throw new Exception("User don't have account deposit balance");
                }

                $masterUser = Auth::user()->list([
                    'role_name' => SystemRole::MasterUser->value,
                    'country_id' => $request->input('source_country_id', $depositor->profile?->present_country_id),
                ])->first();

                if (! $masterUser) {
                    throw new Exception('Master User Account not found for '.$request->input('source_country_id', $depositor->profile?->country_id).' country');
                }

                $receiver = Auth::user()->find($inputs['sender_receiver_id']);
                $receiverDepositAccount = Transaction::userAccount()->list([
                    'user_id' => $inputs['sender_receiver_id'],
                    'currency' => $request->input('currency', $receiver->profile?->presentCountry?->currency),
                ])->first();

                if (! $receiverDepositAccount) {
                    throw new Exception("Receiver don't have account deposit balance");
                }

                //set pre defined conditions of deposit
                $inputs['transaction_form_id'] = Transaction::transactionForm()->list(['code' => 'request_money'])->first()->getKey();
                $inputs['user_id'] = $receiver ?? $receiverDepositAccount->getKey();
                $delayCheck = Transaction::order()->transactionDelayCheck($inputs);
                if ($delayCheck['countValue'] > 0) {
                    throw new Exception('Your Request For This Amount Is Already Submitted. Please Wait For Update');
                }

                $inputs['user_id'] = $receiver->getKey();
                $inputs['sender_receiver_id'] = $user_id ?? $depositor->getKey(); //$masterUser->getKey();
                $inputs['order_data']['sender_receiver_id'] = $user_id ?? $depositor->getKey();
                $inputs['is_refunded'] = false;
                $inputs['status'] = DepositStatus::Processing->value;
                $inputs['risk'] = RiskProfile::Low->value;
                $inputs['converted_currency'] = $inputs['currency'];
                $inputs['notes'] = 'Request Money for wallet to wallet transfer to '.$depositor->name;
                $inputs['order_data']['created_by'] = $depositor->name;
                $inputs['order_data']['created_by_mobile_number'] = $depositor->mobile;
                $inputs['order_data']['created_at'] = now();
                $inputs['order_data']['master_user_name'] = $masterUser['name'];
                //$inputs['order_data']['operator_short_code'] = $request->input('operator_short_code', null);
                $inputs['order_data']['system_notification_variable_success'] = 'request_money_success';
                $inputs['order_data']['system_notification_variable_failed'] = 'request_money_failed';
                $inputs['order_data']['source_country_id'] = $inputs['source_country_id'];
                $inputs['order_data']['destination_country_id'] = $inputs['destination_country_id'];
                $inputs['converted_amount'] = $inputs['amount'];
                $inputs['converted_currency'] = $inputs['currency'];
                unset($inputs['pin'], $inputs['password']);
                $requestMoney = Reload::requestMoney()->create($inputs);

                if (! $requestMoney) {
                    throw (new StoreOperationException)->setModel(config('fintech.reload.request_money_model'));
                }
                $order_data = $requestMoney->order_data;
                $order_data['purchase_number'] = entry_number($requestMoney->getKey(), $requestMoney->sourceCountry->iso3, OrderStatus::Successful->value);
                $order_data['service_stat_data'] = Business::serviceStat()->serviceStateData($requestMoney);
                $order_data['user_name'] = $requestMoney->user->name;
                Reload::requestMoney()->update($requestMoney->getKey(), ['order_data' => $order_data, 'order_number' => $order_data['purchase_number']]);
                $this->__receiverStore($requestMoney->getKey());
                Transaction::orderQueue()->removeFromQueueUserWise($user_id ?? $depositor->getKey());
                DB::commit();

                return $this->created([
                    'message' => __('core::messages.resource.created', ['model' => 'Request Money']),
                    'id' => $requestMoney->id,
                ]);
            } else {
                throw new Exception('Your another order is in process...!');
            }
        } catch (Exception $exception) {
            Transaction::orderQueue()->removeFromQueueUserWise($user_id ?? $depositor->getKey());
            DB::rollBack();

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Return a specified *RequestMoney* resource found by id.
     *
     * @lrd:end
     *
     * @throws ModelNotFoundException
     */
    public function show(string|int $id): RequestMoneyResource|JsonResponse
    {
        try {

            $requestMoney = Reload::requestMoney()->find($id);

            if (! $requestMoney) {
                throw (new ModelNotFoundException)->setModel(config('fintech.reload.request_money_model'), $id);
            }

            return new RequestMoneyResource($requestMoney);

        } catch (ModelNotFoundException $exception) {

            return $this->notfound($exception->getMessage());

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Update a specified *RequestMoney* resource using id.
     *
     * @lrd:end
     *
     * @throws ModelNotFoundException
     */
    public function update(UpdateRequestMoneyRequest $request, string|int $id): JsonResponse
    {
        try {

            $requestMoney = Reload::requestMoney()->find($id);

            if (! $requestMoney) {
                throw (new ModelNotFoundException)->setModel(config('fintech.reload.request_money_model'), $id);
            }

            $inputs = $request->validated();

            if (! Reload::requestMoney()->update($id, $inputs)) {

                throw (new UpdateOperationException)->setModel(config('fintech.reload.request_money_model'), $id);
            }

            return $this->updated(__('core::messages.resource.updated', ['model' => 'Request Money']));

        } catch (ModelNotFoundException $exception) {

            return $this->notfound($exception->getMessage());

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Soft delete a specified *RequestMoney* resource using id.
     *
     * @lrd:end
     */
    public function destroy(string|int $id): JsonResponse
    {
        try {

            $requestMoney = Reload::requestMoney()->find($id);

            if (! $requestMoney) {
                throw (new ModelNotFoundException)->setModel(config('fintech.reload.request_money_model'), $id);
            }

            if (! Reload::requestMoney()->destroy($id)) {

                throw (new DeleteOperationException())->setModel(config('fintech.reload.request_money_model'), $id);
            }

            return $this->deleted(__('core::messages.resource.deleted', ['model' => 'Request Money']));

        } catch (ModelNotFoundException $exception) {

            return $this->notfound($exception->getMessage());

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @throws StoreOperationException
     * @throws Exception
     */
    private function __receiverStore($id): bool
    {
        $requestMoney = Reload::requestMoney()->find($id);
        $receiverInputs = $requestMoney->toArray();

        $receiverInputs['user_id'] = $requestMoney['sender_receiver_id'];
        $receiverInputs['sender_receiver_id'] = $requestMoney['user_id'];

        $requestMoneyAccount = Transaction::userAccount()->list([
            'user_id' => $receiverInputs['user_id'],
            'currency' => $receiverInputs['converted_currency'],
        ])->first();

        if (! $requestMoneyAccount) {
            throw new Exception("User don't have account deposit balance");
        }

        //set pre defined conditions of deposit
        $receiverInputs['transaction_form_id'] = Transaction::transactionForm()->list(['code' => 'request_money'])->first()->getKey();
        $receiverInputs['notes'] = 'Wallet to Wallet receive request from '.$requestMoney['order_data']['user_name'];
        $receiverInputs['parent_id'] = $id;

        $requestMoney = Reload::requestMoney()->create($receiverInputs);

        if (! $requestMoney) {
            throw (new StoreOperationException)->setModel(config('fintech.reload.request_money_model'));
        }

        $order_data = $requestMoney->order_data;
        $order_data['purchase_number'] = entry_number($requestMoney->getKey(), $requestMoney->sourceCountry->iso3, OrderStatus::Successful->value);

        $order_data['service_stat_data'] = Business::serviceStat()->serviceStateData($requestMoney);
        $order_data['user_name'] = $requestMoney->user->name;
        $requestMoney->order_data = $order_data;
        Reload::requestMoney()->update($requestMoney->getKey(), ['order_data' => $order_data, 'order_number' => $order_data['purchase_number']]);

        return true;
    }

    /**
     * @lrd:start
     * Restore the specified *RequestMoney* resource from trash.
     * ** ```Soft Delete``` needs to enabled to use this feature**
     *
     * @lrd:end
     */
    public function restore(string|int $id): JsonResponse
    {
        try {

            $requestMoney = Reload::requestMoney()->find($id, true);

            if (! $requestMoney) {
                throw (new ModelNotFoundException)->setModel(config('fintech.reload.request_money_model'), $id);
            }

            if (! Reload::requestMoney()->restore($id)) {

                throw (new RestoreOperationException())->setModel(config('fintech.reload.request_money_model'), $id);
            }

            return $this->restored(__('core::messages.resource.restored', ['model' => 'Request Money']));

        } catch (ModelNotFoundException $exception) {

            return $this->notfound($exception->getMessage());

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Create an exportable list of the *RequestMoney* resource as document.
     * After export job is done system will fire  export completed event
     *
     * @lrd:end
     */
    public function export(IndexRequestMoneyRequest $request): JsonResponse
    {
        try {
            $inputs = $request->validated();

            //$requestMoneyPaginate = Reload::requestMoney()->export($inputs);
            Reload::requestMoney()->export($inputs);

            return $this->exported(__('core::messages.resource.exported', ['model' => 'Request Money']));

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    private function authenticateDeposit(string|int $id, BackedEnum $requiredStatus, BackedEnum $targetStatus): \Fintech\Core\Abstracts\BaseModel
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
     * Create an exportable list of the *RequestMoney* resource as document.
     * After export job is done system will fire  export completed event
     *
     * @lrd:end
     */
    public function import(ImportRequestMoneyRequest $request): RequestMoneyCollection|JsonResponse
    {
        try {
            $inputs = $request->validated();

            $requestMoneyPaginate = Reload::requestMoney()->list($inputs);

            return new RequestMoneyCollection($requestMoneyPaginate);

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
            if (Transaction::orderQueue()->addToQueueOrderWise($id) > 0) {
                $deposit = $this->authenticateDeposit($id, DepositStatus::Processing, DepositStatus::Rejected);

                $approver = $request->user('sanctum');

                $updateData = $deposit->toArray();
                $updateData['status'] = DepositStatus::Rejected->value;
                $updateData['order_data']['rejected_by'] = $approver->name;
                $updateData['order_data']['rejected_at'] = now();
                $updateData['order_data']['rejected_number'] = entry_number($deposit->getKey(), $deposit->sourceCountry->iso3, OrderStatusConfig::Rejected->value);
                $updateData['order_number'] = entry_number($deposit->getKey(), $deposit->sourceCountry->iso3, OrderStatusConfig::Rejected->value);
                $updateData['order_data']['rejected_by_mobile_number'] = $approver->mobile;

                if (! Reload::requestMoney()->update($deposit->getKey(), $updateData)) {
                    throw new Exception(__('reload::messages.status_change_failed', [
                        'current_status' => $deposit->currentStatus(),
                        'target_status' => DepositStatus::Rejected->name,
                    ]));
                }

                $this->__receiverReject($id);
                Transaction::orderQueue()->removeFromQueueOrderWise($id);

                return $this->success(__('reload::messages.deposit.status_change_success', [
                    'status' => DepositStatus::Rejected->name,
                ]));
            } else {
                throw new Exception('Your another order is in process...!');
            }

        } catch (ModelNotFoundException $exception) {
            Transaction::orderQueue()->removeFromQueueOrderWise($id);

            return $this->notfound($exception->getMessage());

        } catch (Exception $exception) {
            Transaction::orderQueue()->removeFromQueueOrderWise($id);

            return $this->failed($exception->getMessage());
        }
    }

    private function __receiverReject($id): JsonResponse
    {
        try {
            $requestMoneyActual = Reload::requestMoney()->find($id);
            $requestMoneyChild = Reload::requestMoney()->list(['parent_id' => $id])->first();
            $requestMoney = Reload::requestMoney()->find($requestMoneyChild->id);
            $receiverInputs = $requestMoney->toArray();
            $deposit = $this->authenticateDeposit($requestMoneyChild->id, DepositStatus::Processing, DepositStatus::Rejected);

            $updateData = $deposit->toArray();
            $updateData['status'] = DepositStatus::Rejected->value;
            $updateData['order_data']['rejected_by'] = $requestMoneyActual['order_data']['rejected_by'];
            $updateData['order_data']['rejected_at'] = now();
            $updateData['order_data']['rejected_number'] = entry_number($deposit->getKey(), $deposit->sourceCountry->iso3, OrderStatusConfig::Rejected->value);
            $updateData['order_number'] = entry_number($deposit->getKey(), $deposit->sourceCountry->iso3, OrderStatusConfig::Rejected->value);
            $updateData['order_data']['rejected_by_mobile_number'] = $requestMoneyActual['order_data']['rejected_by_mobile_number'];

            if (! Reload::requestMoney()->update($deposit->getKey(), $updateData)) {
                throw new Exception(__('reload::messages.status_change_failed', [
                    'current_status' => $deposit->currentStatus(),
                    'target_status' => DepositStatus::Rejected->name,
                ]));
            }

            return $this->success(__('reload::messages.deposit.status_change_success', [
                'status' => DepositStatus::Rejected->name,
            ]));

        } catch (ModelNotFoundException $exception) {
            Transaction::orderQueue()->removeFromQueueOrderWise($id);

            return $this->notfound($exception->getMessage());

        } catch (Exception $exception) {
            Transaction::orderQueue()->removeFromQueueOrderWise($id);

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
            if (Transaction::orderQueue()->addToQueueOrderWise($id) > 0) {
                $deposit = $this->authenticateDeposit($id, DepositStatus::Processing, DepositStatus::Accepted);

                return $this->success(__('reload::messages.deposit.status_change_success', [
                    'status' => DepositStatus::Accepted->name,
                ]));
            } else {
                throw new Exception('Your another order is in process...!');
            }

        } catch (ModelNotFoundException $exception) {
            Transaction::orderQueue()->removeFromQueueOrderWise($id);

            return $this->notfound($exception->getMessage());

        } catch (Exception $exception) {
            Transaction::orderQueue()->removeFromQueueOrderWise($id);

            return $this->failed($exception->getMessage());
        }
    }
}
