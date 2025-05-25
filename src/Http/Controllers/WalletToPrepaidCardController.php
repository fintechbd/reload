<?php

namespace Fintech\Reload\Http\Controllers;

use Exception;
use Fintech\Auth\Facades\Auth;
use Fintech\Core\Enums\Auth\RiskProfile;
use Fintech\Core\Enums\Auth\SystemRole;
use Fintech\Core\Enums\Transaction\OrderStatus;
use Fintech\Core\Enums\Transaction\OrderStatusConfig;
use Fintech\Core\Enums\Transaction\OrderType;
use Fintech\Core\Exceptions\DeleteOperationException;
use Fintech\Core\Exceptions\RestoreOperationException;
use Fintech\Core\Exceptions\StoreOperationException;
use Fintech\Core\Exceptions\Transaction\CurrencyUnavailableException;
use Fintech\Core\Exceptions\UpdateOperationException;
use Fintech\Reload\Events\BankDepositReceived;
use Fintech\Reload\Http\Requests\ImportWalletToPrepaidCardRequest;
use Fintech\Reload\Http\Requests\IndexWalletToPrepaidCardRequest;
use Fintech\Reload\Http\Requests\StoreWalletToPrepaidCardRequest;
use Fintech\Reload\Http\Requests\UpdateWalletToPrepaidCardRequest;
use Fintech\Reload\Http\Resources\WalletToPrepaidCardCollection;
use Fintech\Reload\Http\Resources\WalletToPrepaidCardResource;
use Fintech\Transaction\Facades\Transaction;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

/**
 * Class WalletToPrepaidCardController
 *
 * @lrd:start
 * This class handle create, display, update, delete & restore
 * operation related to WalletToPrepaidCard
 *
 * @lrd:end
 */
class WalletToPrepaidCardController extends Controller
{
    /**
     * @lrd:start
     * Return a listing of the *WalletToPrepaidCard* resource as collection.
     *
     * *```paginate=false``` returns all resource as list not pagination*
     *
     * @lrd:end
     */
    public function index(IndexWalletToPrepaidCardRequest $request): WalletToPrepaidCardCollection|JsonResponse
    {

        try {
            $inputs = $request->validated();

            $inputs['transaction_form_code'] = 'wallet_prepaid_card';

            if ($request->isAgent()) {
                $inputs['creator_id'] = $request->user('sanctum')->getKey();
            }

            $walletToPrepaidCardPaginate = reload()->walletToPrepaidCard()->list($inputs);

            return new WalletToPrepaidCardCollection($walletToPrepaidCardPaginate);

        } catch (Exception $exception) {

            return response()->failed($exception);
        }
    }

    /**
     * @lrd:start
     * Create a new *WalletToPrepaidCard* resource in storage.
     *
     * @lrd:end
     *
     * @throws StoreOperationException
     */
    public function store(StoreWalletToPrepaidCardRequest $request): JsonResponse
    {
        try {
            $inputs = $request->validated();

            if (isset($inputs['user_id']) && $request->input('user_id') > 0) {
                $user_id = $request->input('user_id');
            }
            $walletUser = $request->user('sanctum');

            if (Transaction::orderQueue()->addToQueueUserWise(($user_id ?? $walletUser->getKey())) > 0) {

                $walletUserAccount = transaction()->userAccount()->findWhere(['user_id' => $user_id ?? $walletUser->getKey(), 'country_id' => $request->input('source_country_id', $walletUser->profile?->country_id)]);

                if (! $walletUserAccount) {
                    throw new CurrencyUnavailableException($request->input('source_country_id', $walletUser->profile?->present_country_id));
                }

                $masterUser = Auth::user()->findWhere(['role_name' => SystemRole::MasterUser->value, 'country_id' => $request->input('source_country_id', $walletUser->profile?->country_id)]);

                if (! $masterUser) {
                    throw new Exception('Master User Account not found for '.$request->input('source_country_id', $walletUser->profile?->country_id).' country');
                }

                // set pre defined conditions of deposit
                $inputs['transaction_form_id'] = transaction()->transactionForm()->findWhere(['code' => 'wallet_prepaid_card'])->getKey();
                $inputs['user_id'] = $user_id ?? $walletUser->getKey();
                $delayCheck = transaction()->order()->transactionDelayCheck($inputs);
                if ($delayCheck['countValue'] > 0) {
                    throw new Exception('Your Request For This Amount Is Already Submitted. Please Wait For Update');
                }
                $inputs['sender_receiver_id'] = $masterUser->getKey();
                $inputs['is_refunded'] = false;
                $inputs['status'] = OrderStatus::Success->value;
                $inputs['risk'] = RiskProfile::Low->value;
                $inputs['order_data']['created_by'] = $walletUser->name;
                $inputs['order_data']['created_by_mobile_number'] = $walletUser->mobile;
                $inputs['order_data']['created_at'] = now();
                $inputs['order_data']['current_amount'] = ($walletUserAccount->user_account_data['available_amount'] ?? 0) - $inputs['amount'];
                $inputs['order_data']['previous_amount'] = $walletUserAccount->user_account_data['available_amount'] ?? 0;
                $inputs['converted_amount'] = $inputs['amount'];
                $inputs['converted_currency'] = $inputs['currency'];
                $inputs['order_data']['master_user_name'] = $masterUser['name'];
                $inputs['order_data']['order_type'] = OrderType::WalletToPrepaidCard;
                unset($inputs['pin'], $inputs['password']);

                $walletToPrepaidCard = reload()->walletToPrepaidCard()->create($inputs);

                if (! $walletToPrepaidCard) {
                    throw (new StoreOperationException)->setModel(config('fintech.reload.wallet_to_prepaid_card_model'));
                }

                $order_data = $walletToPrepaidCard->order_data;
                $order_data['purchase_number'] = entry_number($walletToPrepaidCard->getKey(), $walletToPrepaidCard->sourceCountry->iso3, OrderStatusConfig::Purchased->value);

                reload()->walletToPrepaidCard()->update($walletToPrepaidCard->getKey(), ['order_data' => $order_data, 'order_number' => $order_data['purchase_number']]);

                transaction()->orderQueue()->removeFromQueueUserWise($user_id);

                $prepaidCard = card()->prepaidCard()->find($request->input('instant_card_id'));

                $prepaidCard->balance = $prepaidCard->balance + $inputs['converted_amount'];

                $prepaidCard->save();

                event(new BankDepositReceived($walletToPrepaidCard));

                return response()->created([
                    'message' => __('core::messages.resource.created', ['model' => 'Wallet To Prepaid Card']),
                    'id' => $walletToPrepaidCard->id,
                    'order_number' => $walletToPrepaidCard->order_number ?? $walletToPrepaidCard->order_data['purchase_number'],
                ]);

            } else {
                throw new Exception('Your another order is in process...!');
            }

        } catch (Exception $exception) {

            transaction()->orderQueue()->removeFromQueueUserWise($user_id);

            return response()->failed($exception);
        }
    }

    /**
     * @lrd:start
     * Update a specified *WalletToPrepaidCard* resource using id.
     *
     * @lrd:end
     *
     * @throws ModelNotFoundException
     * @throws UpdateOperationException
     */
    public function update(UpdateWalletToPrepaidCardRequest $request, string|int $id): JsonResponse
    {
        try {

            $walletToPrepaidCard = reload()->walletToPrepaidCard()->find($id);

            if (! $walletToPrepaidCard) {
                throw (new ModelNotFoundException)->setModel(config('fintech.reload.wallet_to_prepaid_card_model'), $id);
            }

            $inputs = $request->validated();

            if (! reload()->walletToPrepaidCard()->update($id, $inputs)) {

                throw (new UpdateOperationException)->setModel(config('fintech.reload.wallet_to_prepaid_card_model'), $id);
            }

            return response()->updated(__('core::messages.resource.updated', ['model' => 'Wallet To Prepaid Card']));

        } catch (Exception $exception) {

            return response()->failed($exception);
        }
    }

    /**
     * @lrd:start
     * Return a specified *WalletToPrepaidCard* resource found by id.
     *
     * @lrd:end
     *
     * @throws ModelNotFoundException
     */
    public function show(string|int $id): WalletToPrepaidCardResource|JsonResponse
    {
        try {

            $walletToPrepaidCard = reload()->walletToPrepaidCard()->find($id);

            if (! $walletToPrepaidCard) {
                throw (new ModelNotFoundException)->setModel(config('fintech.reload.wallet_to_prepaid_card_model'), $id);
            }

            return new WalletToPrepaidCardResource($walletToPrepaidCard);

        } catch (Exception $exception) {

            return response()->failed($exception);
        }
    }

    /**
     * @lrd:start
     * Soft delete a specified *WalletToPrepaidCard* resource using id.
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

            $walletToPrepaidCard = reload()->walletToPrepaidCard()->find($id);

            if (! $walletToPrepaidCard) {
                throw (new ModelNotFoundException)->setModel(config('fintech.reload.wallet_to_prepaid_card_model'), $id);
            }

            if (! reload()->walletToPrepaidCard()->destroy($id)) {

                throw (new DeleteOperationException)->setModel(config('fintech.reload.wallet_to_prepaid_card_model'), $id);
            }

            return response()->deleted(__('core::messages.resource.deleted', ['model' => 'Wallet To Prepaid Card']));

        } catch (Exception $exception) {

            return response()->failed($exception);
        }
    }

    /**
     * @lrd:start
     * Restore the specified *WalletToPrepaidCard* resource from trash.
     * ** ```Soft Delete``` needs to enabled to use this feature**
     *
     * @lrd:end
     *
     * @return JsonResponse
     */
    public function restore(string|int $id)
    {
        try {

            $walletToPrepaidCard = reload()->walletToPrepaidCard()->find($id, true);

            if (! $walletToPrepaidCard) {
                throw (new ModelNotFoundException)->setModel(config('fintech.reload.wallet_to_prepaid_card_model'), $id);
            }

            if (! reload()->walletToPrepaidCard()->restore($id)) {

                throw (new RestoreOperationException)->setModel(config('fintech.reload.wallet_to_prepaid_card_model'), $id);
            }

            return response()->restored(__('core::messages.resource.restored', ['model' => 'Wallet To Prepaid Card']));

        } catch (Exception $exception) {

            return response()->failed($exception);
        }
    }

    /**
     * @lrd:start
     * Create a exportable list of the *WalletToPrepaidCard* resource as document.
     * After export job is done system will fire  export completed event
     *
     * @lrd:end
     */
    public function export(IndexWalletToPrepaidCardRequest $request): JsonResponse
    {
        try {
            $inputs = $request->validated();

            $walletToPrepaidCardPaginate = reload()->walletToPrepaidCard()->export($inputs);

            return response()->exported(__('core::messages.resource.exported', ['model' => 'Wallet To Prepaid Card']));

        } catch (Exception $exception) {

            return response()->failed($exception);
        }
    }

    /**
     * @lrd:start
     * Create a exportable list of the *WalletToPrepaidCard* resource as document.
     * After export job is done system will fire  export completed event
     *
     * @lrd:end
     *
     * @return WalletToPrepaidCardCollection|JsonResponse
     */
    public function import(ImportWalletToPrepaidCardRequest $request): JsonResponse
    {
        try {
            $inputs = $request->validated();

            $walletToPrepaidCardPaginate = reload()->walletToPrepaidCard()->list($inputs);

            return new WalletToPrepaidCardCollection($walletToPrepaidCardPaginate);

        } catch (Exception $exception) {

            return response()->failed($exception);
        }
    }
}
