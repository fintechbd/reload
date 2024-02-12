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
use Illuminate\Support\Facades\DB;

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
    use ApiResponseTrait;

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
            //$inputs['transaction_form_id'] = Transaction::transactionForm()->list(['code' => 'point_transfer'])->first()->getKey();
            $inputs['transaction_form_code'] = 'point_transfer';
            //$inputs['service_id'] = Business::serviceType()->list(['service_type_slug'=>'wallet_to_wallet']);
            //$inputs['service_type_slug'] = 'wallet_to_wallet';
            $walletToWalletPaginate = Reload::walletToWallet()->list($inputs);

            return new WalletToWalletCollection($walletToWalletPaginate);

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
        }
    }

    /**
     * @lrd:start
     * Create a new *WalletToWallet* resource in storage.
     *
     * @lrd:end
     *
     * @throws StoreOperationException
     */
    public function store(StoreWalletToWalletRequest $request): JsonResponse
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
                    'currency' => $request->input('currency', $depositor->profile?->presentCountry?->currency),
                ])->first();

                if (! $depositAccount) {
                    throw new Exception("User don't have account deposit balance");
                }

                $receiver = Auth::user()->find($inputs['order_data']['sender_receiver_id']);
                $receiverDepositAccount = Transaction::userAccount()->list([
                    'user_id' => $inputs['order_data']['sender_receiver_id'],
                    'currency' => $request->input('currency', $receiver->profile?->presentCountry?->currency),
                ])->first();

                if (! $receiverDepositAccount) {
                    throw new Exception("Receiver don't have account deposit balance");
                }

                $masterUser = Auth::user()->list([
                    'role_name' => SystemRole::MasterUser->value,
                    'country_id' => $request->input('source_country_id', $depositor->profile?->present_country_id),
                ])->first();

                if (! $masterUser) {
                    throw new Exception('Master User Account not found for '.$request->input('source_country_id', $depositor->profile?->country_id).' country');
                }

                //set pre defined conditions of deposit
                $inputs['transaction_form_id'] = Transaction::transactionForm()->list(['code' => 'point_transfer'])->first()->getKey();
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
                $inputs['converted_currency'] = $inputs['currency'];
                $inputs['order_data']['sender_receiver_name'] = $receiver->name;
                $inputs['order_data']['sender_receiver_mobile_number'] = $receiver->mobile;
                $inputs['order_data']['currency_convert_rate'] = Business::currencyRate()->convert($inputs);
                unset($inputs['reverse']);
                $inputs['converted_amount'] = $inputs['order_data']['currency_convert_rate']['converted'];
                $inputs['converted_currency'] = $inputs['order_data']['currency_convert_rate']['output'];
                $inputs['notes'] = 'Wallet to wallet transfer to '.$receiver->name;
                $inputs['order_data']['sender_id'] = $depositor->getKey();
                $inputs['order_data']['sender_name'] = $depositor->name;
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
                $walletToWallet = Reload::walletToWallet()->create($inputs);

                if (! $walletToWallet) {
                    throw (new StoreOperationException)->setModel(config('fintech.reload.wallet_to_wallet_model'));
                }

                $order_data = $walletToWallet->order_data;
                $order_data['purchase_number'] = entry_number($walletToWallet->getKey(), $walletToWallet->sourceCountry->iso3, OrderStatus::Successful->value);

                $order_data['service_stat_data'] = Business::serviceStat()->serviceStateData($walletToWallet);
                $order_data['user_name'] = $walletToWallet->user->name;
                $walletToWallet->order_data = $order_data;
                $userUpdatedBalance = Reload::walletToWallet()->debitTransaction($walletToWallet);
                //source country or destination country change to currency name
                $depositedAccount = Transaction::userAccount()->list([
                    'user_id' => $depositor->getKey(),
                    'currency' => $walletToWallet->converted_currency,
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

                Reload::walletToWallet()->update($walletToWallet->getKey(), ['order_data' => $order_data, 'order_number' => $order_data['purchase_number']]);
                $this->__receiverStore($walletToWallet->getKey());
                Transaction::orderQueue()->removeFromQueueUserWise($user_id ?? $depositor->getKey());
                DB::commit();

                return $this->created([
                    'message' => __('core::messages.resource.created', ['model' => 'Currency Swap']),
                    'id' => $walletToWallet->id,
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

            return $this->notfound($exception->getMessage());

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
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

            return $this->updated(__('core::messages.resource.updated', ['model' => 'Wallet To Wallet']));

        } catch (ModelNotFoundException $exception) {

            return $this->notfound($exception->getMessage());

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
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

                throw (new DeleteOperationException())->setModel(config('fintech.reload.wallet_to_wallet_model'), $id);
            }

            return $this->deleted(__('core::messages.resource.deleted', ['model' => 'Wallet To Wallet']));

        } catch (ModelNotFoundException $exception) {

            return $this->notfound($exception->getMessage());

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
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

                throw (new RestoreOperationException())->setModel(config('fintech.reload.wallet_to_wallet_model'), $id);
            }

            return $this->restored(__('core::messages.resource.restored', ['model' => 'Wallet To Wallet']));

        } catch (ModelNotFoundException $exception) {

            return $this->notfound($exception->getMessage());

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
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

            return $this->exported(__('core::messages.resource.exported', ['model' => 'Wallet To Wallet']));

        } catch (Exception $exception) {

            return $this->failed($exception->getMessage());
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

        $receiverInputs['user_id'] = $deposit['order_data']['sender_receiver_id'];
        $receiverInputs['order_data']['sender_receiver_id'] = $deposit['user_id'];

        $depositAccount = Transaction::userAccount()->list([
            'user_id' => $receiverInputs['user_id'],
            'currency' => $receiverInputs['converted_currency'],
        ])->first();

        if (! $depositAccount) {
            throw new Exception("User don't have account deposit balance");
        }

        //set pre defined conditions of deposit
        $receiverInputs['transaction_form_id'] = Transaction::transactionForm()->list(['code' => 'point_reload'])->first()->getKey();
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
        //source country or destination country change to currency name
        $depositedAccount = Transaction::userAccount()->list([
            'user_id' => $walletToWallet->user_id,
            'currency' => $walletToWallet->converted_currency,
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
        Reload::walletToWallet()->update($walletToWallet->getKey(), ['order_data' => $order_data, 'order_number' => $order_data['purchase_number']]);

        return true;
    }
}
