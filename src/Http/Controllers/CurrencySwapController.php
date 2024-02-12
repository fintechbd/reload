<?php

namespace Fintech\Reload\Http\Controllers;

use Exception;
use Fintech\Auth\Facades\Auth;
use Fintech\Business\Facades\Business;
use Fintech\Core\Enums\Auth\RiskProfile;
use Fintech\Core\Enums\Auth\SystemRole;
use Fintech\Core\Enums\Transaction\OrderStatus;
use Fintech\Core\Exceptions\DeleteOperationException;
use Fintech\Core\Exceptions\RestoreOperationException;
use Fintech\Core\Exceptions\StoreOperationException;
use Fintech\Core\Exceptions\UpdateOperationException;
use Fintech\Core\Traits\ApiResponseTrait;
use Fintech\Reload\Facades\Reload;
use Fintech\Reload\Http\Requests\ImportCurrencySwapRequest;
use Fintech\Reload\Http\Requests\IndexCurrencySwapRequest;
use Fintech\Reload\Http\Requests\StoreCurrencySwapRequest;
use Fintech\Reload\Http\Requests\UpdateCurrencySwapRequest;
use Fintech\Reload\Http\Resources\CurrencySwapCollection;
use Fintech\Reload\Http\Resources\CurrencySwapResource;
use Fintech\Transaction\Facades\Transaction;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

/**
 * Class CurrencySwapController
 *
 * @lrd:start
 * This class handle create, display, update, delete & restore
 * operation related to CurrencySwap
 *
 * @lrd:end
 */
class CurrencySwapController extends Controller
{
    use ApiResponseTrait;

    /**
     * @lrd:start
     * Return a listing of the *CurrencySwap* resource as collection.
     *
     * *```paginate=false``` returns all resource as list not pagination*
     *
     * @lrd:end
     */
    public function index(IndexCurrencySwapRequest $request): CurrencySwapCollection|JsonResponse
    {
        try {
            $inputs = $request->validated();
            //$inputs['transaction_form_id'] = Transaction::transactionForm()->list(['code' => 'currency_swap'])->first()->getKey();
            $inputs['transaction_form_code'] = 'currency_swap';
            //$inputs['service_id'] = Business::serviceType()->list(['service_type_slug'=>'currency_swap']);
            //$inputs['service_type_slug'] = 'currency_swap';

            $currencySwapPaginate = Reload::currencySwap()->list($inputs);

            return new CurrencySwapCollection($currencySwapPaginate);

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Create a new *CurrencySwap* resource in storage.
     *
     * @lrd:end
     *
     * @throws StoreOperationException
     */
    public function store(StoreCurrencySwapRequest $request): JsonResponse
    {
        DB::beginTransaction();
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
                    'currency' => $request->input('converted_currency', $depositor->profile?->presentCountry?->currency),
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

                //set pre defined conditions of deposit
                $inputs['transaction_form_id'] = Transaction::transactionForm()->list(['code' => 'currency_swap'])->first()->getKey();
                $inputs['user_id'] = $user_id ?? $depositor->getKey();
                $delayCheck = Transaction::order()->transactionDelayCheck($inputs);
                if ($delayCheck['countValue'] > 0) {
                    throw new Exception('Your Request For This Amount Is Already Submitted. Please Wait For Update');
                }
                $inputs['sender_receiver_id'] = $masterUser->getKey();
                $inputs['is_refunded'] = false;
                $inputs['status'] = OrderStatus::Successful->value;
                $inputs['risk'] = RiskProfile::Low->value;
                //$inputs['reverse'] = true;

                $inputs['order_data']['currency_convert_rate'] = Business::currencyRate()->convert($inputs);
                unset($inputs['reverse']);
                $inputs['converted_amount'] = $inputs['order_data']['currency_convert_rate']['converted'];
                $inputs['converted_currency'] = $inputs['order_data']['currency_convert_rate']['output'];
                $inputs['notes'] = 'Currency Swap transfer '.$inputs['amount'].' '.$inputs['currency'].' to '.$inputs['converted_amount'].' '.$inputs['converted_currency'];
                $inputs['order_data']['created_by'] = $depositor->name;
                $inputs['order_data']['created_by_mobile_number'] = $depositor->mobile;
                $inputs['order_data']['created_at'] = now();
                $inputs['order_data']['master_user_name'] = $masterUser['name'];
                //$inputs['order_data']['operator_short_code'] = $request->input('operator_short_code', null);
                $inputs['order_data']['system_notification_variable_success'] = 'currency_swap_success';
                $inputs['order_data']['system_notification_variable_failed'] = 'currency_swap_failed';
                $inputs['order_data']['source_country_id'] = $inputs['source_country_id'];
                $inputs['order_data']['destination_country_id'] = $inputs['destination_country_id'];

                //new concept add
                $inputs['source_country_id'] = $inputs['order_data']['serving_country_id'];
                $inputs['destination_country_id'] = $inputs['order_data']['serving_country_id'];

                unset($inputs['pin'], $inputs['password']);
                $currencySwap = Reload::currencySwap()->create($inputs);

                if (! $currencySwap) {
                    throw (new StoreOperationException)->setModel(config('fintech.airtime.currency_swap_model'));
                }

                $order_data = $currencySwap->order_data;
                $order_data['purchase_number'] = entry_number($currencySwap->getKey(), $currencySwap->sourceCountry->iso3, OrderStatus::Successful->value);

                $order_data['service_stat_data'] = Business::serviceStat()->serviceStateData($currencySwap);
                $order_data['user_name'] = $currencySwap->user->name;
                $currencySwap->order_data = $order_data;
                $userUpdatedBalance = Reload::currencySwap()->debitTransaction($currencySwap);
                //source country or destination country change to currency name
                $depositedAccount = Transaction::userAccount()->list([
                    'user_id' => $depositor->getKey(),
                    'currency' => $currencySwap->converted_currency,
                ])->first();

                //update User Account
                $depositedUpdatedAccount = $depositedAccount->toArray();
                $depositedUpdatedAccount['user_account_data']['spent_amount'] = (float) $depositedUpdatedAccount['user_account_data']['spent_amount'] + (float) $userUpdatedBalance['spent_amount'];
                $depositedUpdatedAccount['user_account_data']['available_amount'] = (float) $userUpdatedBalance['current_amount'];

                if (((float) $depositedUpdatedAccount['user_account_data']['available_amount']) < ((float) config('fintech.transaction.minimum_balance'))) {
                    throw new Exception(__('Insufficient balance!', [
                        'previous_amount' => ((float) $depositedUpdatedAccount['user_account_data']['available_amount']),
                        'current_amount' => ((float) $userUpdatedBalance['spent_amount']),
                    ]));
                }
                $order_data['order_data']['previous_amount'] = (float) $depositedAccount->user_account_data['available_amount'];
                $order_data['order_data']['current_amount'] = (float) $userUpdatedBalance['current_amount'];
                if (! Transaction::userAccount()->update($depositedAccount->getKey(), $depositedUpdatedAccount)) {
                    throw new Exception(__('User Account Balance does not update', [
                        'previous_amount' => ((float) $depositedUpdatedAccount['user_account_data']['available_amount']),
                        'current_amount' => ((float) $userUpdatedBalance['spent_amount']),
                    ]));
                }

                Reload::currencySwap()->update($currencySwap->getKey(), ['order_data' => $order_data, 'order_number' => $order_data['purchase_number']]);
                $this->__receiverStore($currencySwap->getKey());
                Transaction::orderQueue()->removeFromQueueUserWise($user_id ?? $depositor->getKey());
                DB::commit();

                return $this->created([
                    'message' => __('core::messages.resource.created', ['model' => 'Currency Swap']),
                    'id' => $currencySwap->id,
                    'spent' => $userUpdatedBalance['spent_amount'],
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
     * Return a specified *CurrencySwap* resource found by id.
     *
     * @lrd:end
     *
     * @throws ModelNotFoundException
     */
    public function show(string|int $id): CurrencySwapResource|JsonResponse
    {
        try {

            $currencySwap = Reload::currencySwap()->find($id);

            if (! $currencySwap) {
                throw (new ModelNotFoundException)->setModel(config('fintech.reload.currency_swap_model'), $id);
            }

            return new CurrencySwapResource($currencySwap);

        } catch (ModelNotFoundException $exception) {

            return $this->notfound($exception->getMessage());

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Update a specified *CurrencySwap* resource using id.
     *
     * @lrd:end
     *
     * @throws ModelNotFoundException
     */
    public function update(UpdateCurrencySwapRequest $request, string|int $id): JsonResponse
    {
        try {

            $currencySwap = Reload::currencySwap()->find($id);

            if (! $currencySwap) {
                throw (new ModelNotFoundException)->setModel(config('fintech.reload.currency_swap_model'), $id);
            }

            $inputs = $request->validated();

            if (! Reload::currencySwap()->update($id, $inputs)) {

                throw (new UpdateOperationException)->setModel(config('fintech.reload.currency_swap_model'), $id);
            }

            return $this->updated(__('core::messages.resource.updated', ['model' => 'Currency Swap']));

        } catch (ModelNotFoundException $exception) {

            return $this->notfound($exception->getMessage());

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Soft delete a specified *CurrencySwap* resource using id.
     *
     * @lrd:end
     */
    public function destroy(string|int $id): JsonResponse
    {
        try {

            $currencySwap = Reload::currencySwap()->find($id);

            if (! $currencySwap) {
                throw (new ModelNotFoundException)->setModel(config('fintech.reload.currency_swap_model'), $id);
            }

            if (! Reload::currencySwap()->destroy($id)) {

                throw (new DeleteOperationException())->setModel(config('fintech.reload.currency_swap_model'), $id);
            }

            return $this->deleted(__('core::messages.resource.deleted', ['model' => 'Currency Swap']));

        } catch (ModelNotFoundException $exception) {

            return $this->notfound($exception->getMessage());

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Restore the specified *CurrencySwap* resource from trash.
     * ** ```Soft Delete``` needs to enabled to use this feature**
     *
     * @lrd:end
     */
    public function restore(string|int $id): JsonResponse
    {
        try {

            $currencySwap = Reload::currencySwap()->find($id, true);

            if (! $currencySwap) {
                throw (new ModelNotFoundException)->setModel(config('fintech.reload.currency_swap_model'), $id);
            }

            if (! Reload::currencySwap()->restore($id)) {

                throw (new RestoreOperationException())->setModel(config('fintech.reload.currency_swap_model'), $id);
            }

            return $this->restored(__('core::messages.resource.restored', ['model' => 'Currency Swap']));

        } catch (ModelNotFoundException $exception) {

            return $this->notfound($exception->getMessage());

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Create an exportable list of the *CurrencySwap* resource as document.
     * After export job is done system will fire  export completed event
     *
     * @lrd:end
     */
    public function export(IndexCurrencySwapRequest $request): JsonResponse
    {
        try {
            $inputs = $request->validated();

            $currencySwapPaginate = Reload::currencySwap()->export($inputs);

            return $this->exported(__('core::messages.resource.exported', ['model' => 'Currency Swap']));

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Create an exportable list of the *CurrencySwap* resource as document.
     * After export job is done system will fire  export completed event
     *
     * @lrd:end
     */
    public function import(ImportCurrencySwapRequest $request): JsonResponse|CurrencySwapCollection
    {
        try {
            $inputs = $request->validated();

            $currencySwapPaginate = Reload::currencySwap()->list($inputs);

            return new CurrencySwapCollection($currencySwapPaginate);

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
        $deposit = Reload::deposit()->find($id);
        $receiverInputs = $deposit->toArray();

        $receiverInputs['amount'] = $deposit['converted_amount'];
        $receiverInputs['currency'] = $deposit['converted_currency'];
        $receiverInputs['converted_amount'] = $deposit['amount'];
        $receiverInputs['converted_currency'] = $deposit['currency'];

        $depositAccount = Transaction::userAccount()->list([
            'user_id' => $deposit->user_id,
            'currency' => $receiverInputs['converted_currency'],
        ])->first();

        if (! $depositAccount) {
            throw new Exception("User don't have account deposit balance");
        }

        //set pre defined conditions of deposit
        $receiverInputs['transaction_form_id'] = Transaction::transactionForm()->list(['code' => 'point_reload'])->first()->getKey();
        $receiverInputs['notes'] = 'Currency Swap receive from '.$receiverInputs['amount'].' '.$receiverInputs['currency'].' to '.$receiverInputs['converted_amount'].' '.$receiverInputs['converted_currency'];
        $receiverInputs['parent_id'] = $id;

        $currencySwap = Reload::currencySwap()->create($receiverInputs);

        if (! $currencySwap) {
            throw (new StoreOperationException)->setModel(config('fintech.airtime.currency_swap_model'));
        }

        $order_data = $currencySwap->order_data;
        $order_data['purchase_number'] = entry_number($currencySwap->getKey(), $currencySwap->sourceCountry->iso3, OrderStatus::Successful->value);

        $order_data['service_stat_data'] = Business::serviceStat()->serviceStateData($currencySwap);
        $order_data['user_name'] = $currencySwap->user->name;
        $currencySwap->order_data = $order_data;
        $userUpdatedBalance = Reload::currencySwap()->currencySwapAccept($currencySwap);
        //source country or destination country change to currency name
        $depositedAccount = Transaction::userAccount()->list([
            'user_id' => $currencySwap->user_id,
            'currency' => $currencySwap->converted_currency,
        ])->first();

        //update User Account
        $depositedUpdatedAccount = $depositedAccount->toArray();
        $depositedUpdatedAccount['user_account_data']['deposit_amount'] = (float) $depositedUpdatedAccount['user_account_data']['deposit_amount'] + (float) $userUpdatedBalance['deposit_amount'];
        $depositedUpdatedAccount['user_account_data']['available_amount'] = (float) $userUpdatedBalance['current_amount'];

        $order_data['order_data']['previous_amount'] = (float) $depositedAccount->user_account_data['available_amount'];
        $order_data['order_data']['current_amount'] = (float) $userUpdatedBalance['current_amount'];
        if (! Transaction::userAccount()->update($depositedAccount->getKey(), $depositedUpdatedAccount)) {
            throw new Exception(__('User Account Balance does not update', [
                'previous_amount' => ((float) $depositedUpdatedAccount['user_account_data']['available_amount']),
                'current_amount' => ((float) $userUpdatedBalance['spent_amount']),
            ]));
        }
        Reload::currencySwap()->update($currencySwap->getKey(), ['order_data' => $order_data, 'order_number' => $order_data['purchase_number']]);

        return true;
    }
}
