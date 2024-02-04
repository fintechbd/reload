<?php

namespace Fintech\Reload\Http\Controllers;

use Exception;
use Fintech\Core\Enums\Auth\RiskProfile;
use Fintech\Core\Enums\Auth\SystemRole;
use Fintech\Core\Enums\Reload\DepositStatus;
use Fintech\Core\Enums\Transaction\OrderStatusConfig;
use Fintech\Core\Exceptions\DeleteOperationException;
use Fintech\Core\Exceptions\RestoreOperationException;
use Fintech\Core\Exceptions\StoreOperationException;
use Fintech\Core\Exceptions\UpdateOperationException;
use Fintech\Core\Traits\ApiResponseTrait;
use Fintech\Reload\Events\CurrencySwapped;
use Fintech\Reload\Events\DepositReceived;
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
        try {
            $inputs = $request->validated();

            if (isset($inputs['user_id']) && $request->input('user_id') > 0) {
                $user_id = $request->input('user_id');
            }
            $depositor = $request->user('sanctum');

            if (Transaction::orderQueue()->addToQueueUserWise(($user_id ?? $depositor->getKey())) > 0) {

                $depositAccount = \Fintech\Transaction\Facades\Transaction::userAccount()->list([
                    'user_id' => $user_id ?? $depositor->getKey(),
                    'country_id' => $request->input('source_country_id', $depositor->profile?->country_id),
                ])->first();

                if (! $depositAccount) {
                    throw new Exception("User don't have account deposit balance");
                }

                $masterUser = \Fintech\Auth\Facades\Auth::user()->list([
                    'role_name' => SystemRole::MasterUser->value,
                    'country_id' => $request->input('source_country_id', $depositor->profile?->country_id),
                ])->first();

                if (! $masterUser) {
                    throw new Exception('Master User Account not found for '.$request->input('source_country_id', $depositor->profile?->country_id).' country');
                }

                //set pre defined conditions of deposit
                $inputs['transaction_form_id'] = Transaction::transactionForm()->list(['code' => 'point_reload'])->first()->getKey();
                $inputs['user_id'] = $user_id ?? $depositor->getKey();
                $delayCheck = Transaction::order()->transactionDelayCheck($inputs);
                if ($delayCheck['countValue'] > 0) {
                    throw new Exception('Your Request For This Amount Is Already Submitted. Please Wait For Update');
                }
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
                $inputs['order_data']['master_user_name'] = $masterUser['name'];
                unset($inputs['pin'], $inputs['password']);

                $deposit = Reload::deposit()->create($inputs);

                if (! $deposit) {
                    throw (new StoreOperationException)->setModel(config('fintech.reload.deposit_model'));
                }

                $order_data = $deposit->order_data;
                $order_data['purchase_number'] = entry_number($deposit->getKey(), $deposit->sourceCountry->iso3, OrderStatusConfig::Purchased->value);

                Reload::deposit()->update($deposit->getKey(), ['order_data' => $order_data, 'order_number' => $order_data['purchase_number']]);

                Transaction::orderQueue()->removeFromQueueUserWise($user_id);

                event(new DepositReceived($deposit));

                return $this->created([
                    'message' => __('core::messages.resource.created', ['model' => 'Deposit']),
                    'id' => $deposit->id,
                ]);

            } else {
                throw new Exception('Your another order is in process...!');
            }

        } catch (Exception $exception) {
            Transaction::orderQueue()->removeFromQueueUserWise($user_id);

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
     * @throws UpdateOperationException
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
     *
     * @return JsonResponse
     *
     * @throws ModelNotFoundException
     * @throws DeleteOperationException
     */
    public function destroy(string|int $id)
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
     *
     * @return JsonResponse
     */
    public function restore(string|int $id)
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
     * Create a exportable list of the *CurrencySwap* resource as document.
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
     * Create a exportable list of the *CurrencySwap* resource as document.
     * After export job is done system will fire  export completed event
     *
     * @lrd:end
     *
     * @return CurrencySwapCollection|JsonResponse
     */
    public function import(ImportCurrencySwapRequest $request): JsonResponse
    {
        try {
            $inputs = $request->validated();

            $currencySwapPaginate = Reload::currencySwap()->list($inputs);

            return new CurrencySwapCollection($currencySwapPaginate);

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }
}
