<?php

namespace Fintech\Reload\Http\Controllers;

use Exception;
use Fintech\Business\Facades\Business;
use Fintech\Core\Enums\Transaction\OrderStatus;
use Fintech\Core\Exceptions\DeleteOperationException;
use Fintech\Core\Exceptions\RestoreOperationException;
use Fintech\Core\Exceptions\StoreOperationException;
use Fintech\Core\Exceptions\UpdateOperationException;
use Fintech\Reload\Facades\Reload;
use Fintech\Reload\Http\Requests\ImportWalletToWalletRequest;
use Fintech\Reload\Http\Requests\IndexWalletToWalletRequest;
use Fintech\Reload\Http\Requests\StoreWalletToWalletRequest;
use Fintech\Reload\Http\Requests\UpdateWalletToWalletRequest;
use Fintech\Reload\Http\Resources\WalletToWalletCollection;
use Fintech\Reload\Http\Resources\WalletToWalletResource;
use Fintech\Transaction\Facades\Transaction;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

/**
 * Class WalletToWalletController
 *
 * @lrd:start
 * This class handle create, display, update, delete & restore
 * operation related to WalletToWallet
 *
 * @lrd:end
 */
class WalletToWalletController extends Controller
{
    public function __construct()
    {
        $this->middleware('imposter', ['only' => ['store']]);
    }

    /**
     * @lrd:start
     * Return a listing of the *WalletToWallet* resource as collection.
     *
     * *```paginate=false``` returns all resource as list not pagination*
     *
     * @lrd:end
     */
    public function index(IndexWalletToWalletRequest $request): WalletToWalletCollection|JsonResponse
    {
        try {
            $inputs = $request->validated();
            $inputs['transaction_form_code'] = 'point_transfer';
            $inputs['parent_id_is_null'] = true;

            if ($request->isAgent()) {
                $inputs['creator_id'] = $request->user('sanctum')->getKey();
            }

            $walletToWalletPaginate = Reload::walletToWallet()->list($inputs);

            return new WalletToWalletCollection($walletToWalletPaginate);

        } catch (Exception $exception) {

            return response()->failed($exception);
        }
    }

    /**
     * @lrd:start
     * Create a new *WalletToWallet* resource in storage.
     *
     * @lrd:end
     */
    public function store(StoreWalletToWalletRequest $request): JsonResponse
    {
        $inputs = $request->validated();

        $inputs['user_id'] = ($request->filled('user_id')) ? $request->input('user_id') : $request->user('sanctum')->getKey();

        try {
            $walletToWallet = Reload::walletToWallet()->create($inputs);

            $service = $walletToWallet->service;

            return response()->created([
                'message' => __('core::messages.transaction.request_created', ['service' => ucwords(strtolower($service->service_name))]),
                'id' => $walletToWallet->getKey(),
                'order_number' => $walletToWallet->order_number ?? $walletToWallet->order_data['purchase_number'],
            ]);

        } catch (Exception $exception) {
            Transaction::orderQueue()->removeFromQueueUserWise($inputs['user_id']);

            return response()->failed($exception);
        }
    }

    /**
     * @lrd:start
     * Update a specified *WalletToWallet* resource using id.
     *
     * @lrd:end
     *
     * @throws ModelNotFoundException
     */
    public function update(UpdateWalletToWalletRequest $request, string|int $id): JsonResponse
    {
        try {

            $walletToWallet = Reload::walletToWallet()->find($id);

            if (! $walletToWallet) {
                throw (new ModelNotFoundException)->setModel(config('fintech.reload.wallet_to_wallet_model'), $id);
            }

            $inputs = $request->validated();

            if (! Reload::walletToWallet()->update($id, $inputs)) {

                throw (new UpdateOperationException)->setModel(config('fintech.reload.wallet_to_wallet_model'), $id);
            }

            return response()->updated(__('core::messages.resource.updated', ['model' => 'Wallet To Wallet']));

        } catch (ModelNotFoundException $exception) {

            return response()->notfound($exception->getMessage());

        } catch (Exception $exception) {

            return response()->failed($exception);
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

        $receiverInputs['user_id'] = $deposit['order_data']['sender_receiver_id'];
        $receiverInputs['order_data']['sender_receiver_id'] = $deposit['user_id'];

        $depositAccount = Transaction::userAccount()->findWhere(['user_id' => $receiverInputs['user_id'], 'currency' => $receiverInputs['converted_currency']]);

        if (! $depositAccount) {
            throw new Exception("User don't have account deposit balance");
        }

        // set pre defined conditions of deposit
        $receiverInputs['transaction_form_id'] = Transaction::transactionForm()->findWhere(['code' => 'point_reload'])->getKey();
        $receiverInputs['notes'] = 'Wallet to Wallet receive from '.$deposit['order_data']['sender_name'];
        $receiverInputs['parent_id'] = $id;

        $walletToWallet = Reload::walletToWallet()->create($receiverInputs);

        if (! $walletToWallet) {
            throw (new StoreOperationException)->setModel(config('fintech.reload.wallet_to_wallet_model'));
        }

        $order_data = $walletToWallet->order_data;
        $order_data['purchase_number'] = entry_number($walletToWallet->getKey(), $walletToWallet->sourceCountry->iso3, OrderStatus::Successful->value);

        $order_data['service_stat_data'] = Business::serviceStat()->serviceStateData($walletToWallet);
        $order_data['user_name'] = $walletToWallet->user->name;
        $walletToWallet->order_data = $order_data;
        $userUpdatedBalance = Reload::walletToWallet()->walletToWalletAccept($walletToWallet);
        // source country or destination country change to currency name
        $depositedAccount = Transaction::userAccount()->findWhere(['user_id' => $walletToWallet->user_id, 'currency' => $walletToWallet->converted_currency]);

        // update User Account
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
        Reload::walletToWallet()->update($walletToWallet->getKey(), ['order_data' => $order_data, 'order_number' => $order_data['purchase_number']]);

        return true;
    }

    /**
     * @lrd:start
     * Return a specified *WalletToWallet* resource found by id.
     *
     * @lrd:end
     *
     * @throws ModelNotFoundException
     */
    public function show(string|int $id): WalletToWalletResource|JsonResponse
    {
        try {

            $walletToWallet = Reload::walletToWallet()->find($id);

            if (! $walletToWallet) {
                throw (new ModelNotFoundException)->setModel(config('fintech.reload.wallet_to_wallet_model'), $id);
            }

            return new WalletToWalletResource($walletToWallet);

        } catch (ModelNotFoundException $exception) {

            return response()->notfound($exception->getMessage());

        } catch (Exception $exception) {

            return response()->failed($exception);
        }
    }

    /**
     * @lrd:start
     * Soft delete a specified *WalletToWallet* resource using id.
     *
     * @lrd:end
     */
    public function destroy(string|int $id): JsonResponse
    {
        try {

            $walletToWallet = Reload::walletToWallet()->find($id);

            if (! $walletToWallet) {
                throw (new ModelNotFoundException)->setModel(config('fintech.reload.wallet_to_wallet_model'), $id);
            }

            if (! Reload::walletToWallet()->destroy($id)) {

                throw (new DeleteOperationException)->setModel(config('fintech.reload.wallet_to_wallet_model'), $id);
            }

            return response()->deleted(__('core::messages.resource.deleted', ['model' => 'Wallet To Wallet']));

        } catch (ModelNotFoundException $exception) {

            return response()->notfound($exception->getMessage());

        } catch (Exception $exception) {

            return response()->failed($exception);
        }
    }

    /**
     * @lrd:start
     * Restore the specified *WalletToWallet* resource from trash.
     * ** ```Soft Delete``` needs to enabled to use this feature**
     *
     * @lrd:end
     */
    public function restore(string|int $id): JsonResponse
    {
        try {

            $walletToWallet = Reload::walletToWallet()->find($id, true);

            if (! $walletToWallet) {
                throw (new ModelNotFoundException)->setModel(config('fintech.reload.wallet_to_wallet_model'), $id);
            }

            if (! Reload::walletToWallet()->restore($id)) {

                throw (new RestoreOperationException)->setModel(config('fintech.reload.wallet_to_wallet_model'), $id);
            }

            return response()->restored(__('core::messages.resource.restored', ['model' => 'Wallet To Wallet']));

        } catch (ModelNotFoundException $exception) {

            return response()->notfound($exception->getMessage());

        } catch (Exception $exception) {

            return response()->failed($exception);
        }
    }

    /**
     * @lrd:start
     * Create an exportable list of the *WalletToWallet* resource as document.
     * After export job is done system will fire  export completed event
     *
     * @lrd:end
     */
    public function export(IndexWalletToWalletRequest $request): JsonResponse
    {
        try {
            $inputs = $request->validated();

            $walletToWalletPaginate = Reload::walletToWallet()->export($inputs);

            return response()->exported(__('core::messages.resource.exported', ['model' => 'Wallet To Wallet']));

        } catch (Exception $exception) {

            return response()->failed($exception);
        }
    }

    /**
     * @lrd:start
     * Create an exportable list of the *WalletToWallet* resource as document.
     * After export job is done system will fire  export completed event
     *
     * @lrd:end
     */
    public function import(ImportWalletToWalletRequest $request): JsonResponse|WalletToWalletCollection
    {
        try {
            $inputs = $request->validated();

            $walletToWalletPaginate = Reload::walletToWallet()->list($inputs);

            return new WalletToWalletCollection($walletToWalletPaginate);

        } catch (Exception $exception) {

            return response()->failed($exception);
        }
    }
}
