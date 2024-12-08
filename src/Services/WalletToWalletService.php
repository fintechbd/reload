<?php

namespace Fintech\Reload\Services;

use Exception;
use Fintech\Auth\Facades\Auth;
use Fintech\Business\Facades\Business;
use Fintech\Core\Abstracts\BaseModel;
use Fintech\Core\Enums\Auth\RiskProfile;
use Fintech\Core\Enums\Auth\SystemRole;
use Fintech\Core\Enums\Transaction\OrderStatus;
use Fintech\Core\Enums\Transaction\OrderType;
use Fintech\Core\Exceptions\Transaction\CurrencyUnavailableException;
use Fintech\Core\Exceptions\Transaction\MasterCurrencyUnavailableException;
use Fintech\Core\Exceptions\Transaction\OrderRequestFailedException;
use Fintech\Core\Exceptions\Transaction\RequestAmountExistsException;
use Fintech\Core\Exceptions\Transaction\RequestOrderExistsException;
use Fintech\MetaData\Facades\MetaData;
use Fintech\Reload\Events\WalletToWalletReceived;
use Fintech\Reload\Interfaces\WalletToWalletRepository;
use Fintech\Transaction\Facades\Transaction;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Class WalletToWalletService
 */
class WalletToWalletService
{
    use \Fintech\Core\Traits\HasFindWhereSearch;

    /**
     * WalletToWalletService constructor.
     */
    public function __construct(private readonly WalletToWalletRepository $walletToWalletRepository) {}

    public function find($id, $onlyTrashed = false): ?BaseModel
    {
        return $this->walletToWalletRepository->find($id, $onlyTrashed);
    }

    public function update($id, array $inputs = []): ?BaseModel
    {
        return $this->walletToWalletRepository->update($id, $inputs);
    }

    public function destroy($id)
    {
        return $this->walletToWalletRepository->delete($id);
    }

    public function restore($id)
    {
        return $this->walletToWalletRepository->restore($id);
    }

    public function export(array $filters): Paginator|Collection
    {
        return $this->walletToWalletRepository->list($filters);
    }

    public function list(array $filters = []): Paginator|Collection
    {
        return $this->walletToWalletRepository->list($filters);

    }

    public function import(array $filters): ?BaseModel
    {
        return $this->walletToWalletRepository->create($filters);
    }

    /**
     * @throws ModelNotFoundException
     * @throws CurrencyUnavailableException
     * @throws MasterCurrencyUnavailableException
     * @throws RequestAmountExistsException
     * @throws \Exception
     */
    public function create(array $inputs = []): BaseModel
    {
        $user_id = $inputs['user_id'] ?? null;
        $orderType = OrderType::WalletToWallet;

        $sender = Auth::user()->find($user_id);
        if (! $sender) {
            throw (new ModelNotFoundException)->setModel(config('fintech.auth.auth_model'), $user_id);
        }
        $inputs['source_country_id'] = $inputs['source_country_id'] ?? $sender->profile?->present_country_id;
        $senderAccount = Transaction::userAccount()->findWhere(['user_id' => $sender->getKey(), 'country_id' => $inputs['source_country_id']]);
        if (! $senderAccount) {
            throw new CurrencyUnavailableException($inputs['source_country_id']);
        }

        //Receiver
        $recipient = Auth::user()->find($inputs['order_data']['recipient_id']);
        if (! $recipient) {
            throw (new ModelNotFoundException)->setModel(config('fintech.auth.auth_model'), $inputs['order_data']['recipient_id']);
        }
        $recipientAccount = Transaction::userAccount()->findWhere(['user_id' => $recipient->getKey(), 'country_id' => $inputs['destination_country_id']]);
        if (! $recipientAccount) {
            throw new CurrencyUnavailableException($inputs['destination_country_id']);
        }

        // Sender System User
        $senderMasterUser = Auth::user()->findWhere(['role_name' => SystemRole::MasterUser->value, 'country_id' => $inputs['source_country_id']]);
        if (! $senderMasterUser) {
            throw new MasterCurrencyUnavailableException($inputs['source_country_id']);
        }

        // Recipient System User
        $recipientMasterUser = Auth::user()->findWhere(['role_name' => SystemRole::MasterUser->value, 'country_id' => $inputs['destination_country_id']]);
        if (! $recipientMasterUser) {
            throw new MasterCurrencyUnavailableException($inputs['destination_country_id']);
        }

        if (Transaction::orderQueue()->addToQueueUserWise($user_id) == 0) {
            throw new RequestOrderExistsException;
        }

        $inputs['transaction_form_id'] = Transaction::transactionForm()->findWhere(['code' => 'point_transfer'])->getKey();

        if (Transaction::order()->transactionDelayCheck($inputs)['countValue'] > 0) {
            throw new RequestAmountExistsException;
        }

        $role = $sender->roles?->first() ?? null;
        $inputs['order_data']['role_id'] = $role->id;
        $inputs['order_data']['order_type'] = $orderType;
        $inputs['description'] = "Wallet To Wallet transfer sent to {$recipient->name} [{$recipientAccount->account_no}]";
        $inputs['notes'] = $inputs['notes'] ?? "Wallet To Wallet transfer sent to {$recipient->name} [{$recipientAccount->account_no}]";
        $inputs['status'] = OrderStatus::Success;
        $inputs['sender_receiver_id'] = $senderMasterUser->getKey();
        $inputs['is_refunded'] = false;
        $inputs['risk'] = RiskProfile::Low;
        $inputs['order_data']['is_reverse'] = $inputs['reverse'] ?? false;
        $inputs['order_data']['is_reload'] = false;
        $currencyConversion = Business::currencyRate()->convert([
            'role_id' => $inputs['order_data']['role_id'],
            'reverse' => $inputs['order_data']['is_reverse'],
            'source_country_id' => $inputs['source_country_id'],
            'destination_country_id' => $inputs['destination_country_id'],
            'amount' => $inputs['amount'],
            'service_id' => $inputs['service_id'],
        ]);
        if ($inputs['order_data']['is_reverse']) {
            $inputs['amount'] = $currencyConversion['converted'];
            $inputs['converted_amount'] = $currencyConversion['amount'];
        } else {
            $inputs['amount'] = $currencyConversion['amount'];
            $inputs['converted_amount'] = $currencyConversion['converted'];
        }
        $inputs['order_data']['currency_convert_rate'] = $currencyConversion;
        unset($inputs['reverse']);
        $inputs['order_data']['sending_amount'] = $inputs['converted_amount'];

        $inputs['order_data']['created_by'] = $sender->name ?? 'N/A';
        $inputs['order_data']['user_name'] = $sender->name;
        $inputs['order_data']['created_by_mobile_number'] = $sender->mobile ?? 'N/A';
        $inputs['order_data']['created_by_email'] = $sender->email ?? 'N/A';
        $inputs['order_data']['created_at'] = now();

        $inputs['order_data']['recipient_id'] = $recipient->id ?? 'N/A';
        $inputs['order_data']['recipient_by'] = $recipient->name ?? 'N/A';
        $inputs['order_data']['recipient_mobile'] = $recipient->mobile ?? 'N/A';
        $inputs['order_data']['recipient_email'] = $recipient->email ?? 'N/A';

        $inputs['order_data']['sender_id'] = $sender->id ?? 'N/A';
        $inputs['order_data']['sender_by'] = $sender->name ?? 'N/A';
        $inputs['order_data']['sender_mobile'] = $sender->mobile ?? 'N/A';
        $inputs['order_data']['sender_email'] = $sender->email ?? 'N/A';
        $inputs['order_data']['serving_country_id'] = $inputs['source_country_id'];
        $inputs['order_data']['receiving_country_id'] = $inputs['destination_country_id'];
        $inputs['order_data']['service_stat_data'] = Business::serviceStat()->serviceStateData([
            'role_id' => $inputs['order_data']['role_id'],
            'reload' => $inputs['order_data']['is_reload'],
            'reverse' => $inputs['order_data']['is_reverse'],
            'source_country_id' => $inputs['source_country_id'],
            'destination_country_id' => $inputs['destination_country_id'],
            'amount' => $inputs['amount'],
            'service_id' => $inputs['service_id'],
        ]);
        $inputs['order_data']['previous_amount'] = $senderAccount->user_account_data['available_amount'] ?? 0;
        $inputs['order_data']['current_amount'] = $inputs['order_data']['previous_amount'] - $inputs['order_data']['service_stat_data']['total_amount'];
        $inputs['order_data']['master_user_name'] = $senderMasterUser->name;
        $inputs['order_data']['system_notification_variable_success'] = 'wallet_to_wallet_success';
        $inputs['order_data']['system_notification_variable_failed'] = 'wallet_to_wallet_failed';
        $inputs['order_data']['purchase_number'] = next_purchase_number(MetaData::country()->find($inputs['source_country_id'])->iso3);
        $inputs['order_number'] = $inputs['order_data']['purchase_number'];

        $service = Business::service()->find($inputs['service_id']);
        $vendor = $service->serviceVendor;
        $inputs['service_vendor_id'] = $vendor?->getKey() ?? null;
        $inputs['vendor'] = $vendor?->service_vendor_slug ?? null;

        $inputs['timeline'][] = [
            'message' => 'Wallet to Wallet entry created successfully',
            'flag' => 'create',
            'timestamp' => now(),
        ];

        DB::beginTransaction();

        try {
            $receiverInputs = $inputs;
            $senderWalletToWallet = $this->walletToWalletRepository->create($inputs);
            DB::commit();

            $senderAccounting = Transaction::accounting($senderWalletToWallet, $sender->getKey());
            $senderWalletToWallet = $senderAccounting->debitTransaction();
            $senderAccounting->debitBalanceFromUserAccount();
            unset($inputs);
            $receiverInputs['parent_id'] = $senderWalletToWallet->getKey();
            $receiverInputs['user_id'] = $recipient->getKey();
            $receiverInputs['description'] = "Wallet To Wallet transfer received from {$sender->name} [{$senderAccount->account_no}]";
            $receiverInputs['notes'] = $receiverInputs['notes'] ?? "Wallet To Wallet transfer received from {$sender->name} [{$senderAccount->account_no}]";
            $receiverInputs['sender_receiver_id'] = $recipientMasterUser->getKey();
            $receiverInputs['order_data']['master_user_name'] = $recipientMasterUser->name;
            $receiverInputs['order_data']['user_name'] = $recipient->name;
            $recipientWalletToWallet = $this->walletToWalletRepository->create($receiverInputs);

            $recipientAccounting = Transaction::accounting($recipientWalletToWallet, $recipient->getKey());
            $recipientWalletToWallet = $recipientAccounting->creditTransaction();
            $recipientAccounting->creditBalanceToUserAccount();

            Transaction::orderQueue()->removeFromQueueUserWise($user_id);

            WalletToWalletReceived::dispatch($senderWalletToWallet);

            return $senderWalletToWallet;

        } catch (Exception $e) {

            DB::rollBack();

            Transaction::orderQueue()->removeFromQueueUserWise($user_id);

            throw new OrderRequestFailedException($orderType->value, 0, $e);
        }
    }

    //    /**
    //     * @return int[]
    //     */
    //    public function debitTransaction($walletToWallet): array
    //    {
    //        $userAccountData = [
    //            'previous_amount' => null,
    //            'current_amount' => null,
    //            'spent_amount' => null,
    //        ];
    //
    //        //Collect Current Balance as Previous Balance
    //        $userAccountData['previous_amount'] = Transaction::orderDetail()->list([
    //            'get_order_detail_amount_sum' => true,
    //            'user_id' => $walletToWallet->user_id,
    //            'order_detail_currency' => $walletToWallet->currency,
    //        ]);
    //
    //        $serviceStatData = $walletToWallet->order_data['service_stat_data'];
    //        $master_user_name = $walletToWallet->order_data['master_user_name'];
    //        $user_name = $walletToWallet->order_data['user_name'];
    //
    //        $amount = $walletToWallet->amount;
    //        $converted_amount = $walletToWallet->converted_amount;
    //        $walletToWallet->amount = -$amount;
    //        $walletToWallet->converted_amount = -$converted_amount;
    //        $walletToWallet->order_detail_cause_name = 'cash_withdraw';
    //        $walletToWallet->order_detail_number = $walletToWallet->order_data['purchase_number'];
    //        $walletToWallet->order_detail_response_id = $walletToWallet->order_data['purchase_number'];
    //        $walletToWallet->notes = 'Wallet To Wallet Payment Send to ' . $master_user_name;
    //        $orderDetailStore = Transaction::orderDetail()->create(Transaction::orderDetail()->orderDetailsDataArrange($walletToWallet));
    //        $orderDetailStore->order_detail_parent_id = $walletToWallet->order_detail_parent_id = $orderDetailStore->getKey();
    //        $orderDetailStore->save();
    //        $orderDetailStore->fresh();
    //        $orderDetailStoreForMaster = $orderDetailStore->replicate();
    //        $orderDetailStoreForMaster->user_id = $walletToWallet->sender_receiver_id;
    //        $orderDetailStoreForMaster->sender_receiver_id = $walletToWallet->user_id;
    //        $orderDetailStoreForMaster->order_detail_amount = $amount;
    //        $orderDetailStoreForMaster->converted_amount = $converted_amount;
    //        $orderDetailStoreForMaster->step = 2;
    //        $orderDetailStoreForMaster->notes = 'Wallet To Wallet Payment Receive From' . $user_name;
    //        $orderDetailStoreForMaster->save();
    //
    //        //For Charge
    //        $walletToWallet->amount = -calculate_flat_percent($amount, $serviceStatData['charge']);
    //        $walletToWallet->converted_amount = -calculate_flat_percent($converted_amount, $serviceStatData['charge']);
    //        $walletToWallet->order_detail_cause_name = 'charge';
    //        $walletToWallet->order_detail_parent_id = $orderDetailStore->getKey();
    //        $walletToWallet->notes = 'Wallet To Wallet Charge Send to ' . $master_user_name;
    //        $walletToWallet->step = 3;
    //        $walletToWallet->order_detail_parent_id = $orderDetailStore->getKey();
    //        $orderDetailStoreForCharge = Transaction::orderDetail()->create(Transaction::orderDetail()->orderDetailsDataArrange($walletToWallet));
    //        $orderDetailStoreForChargeForMaster = $orderDetailStoreForCharge->replicate();
    //        $orderDetailStoreForChargeForMaster->user_id = $walletToWallet->sender_receiver_id;
    //        $orderDetailStoreForChargeForMaster->sender_receiver_id = $walletToWallet->user_id;
    //        $orderDetailStoreForChargeForMaster->order_detail_amount = calculate_flat_percent($amount, $serviceStatData['charge']);
    //        $orderDetailStoreForChargeForMaster->converted_amount = calculate_flat_percent($converted_amount, $serviceStatData['charge']);
    //        $orderDetailStoreForChargeForMaster->order_detail_cause_name = 'charge';
    //        $orderDetailStoreForChargeForMaster->notes = 'Wallet To Wallet Charge Receive from ' . $user_name;
    //        $orderDetailStoreForChargeForMaster->step = 4;
    //        $orderDetailStoreForChargeForMaster->save();
    //
    //        //For Discount
    //        $walletToWallet->amount = calculate_flat_percent($amount, $serviceStatData['discount']);
    //        $walletToWallet->converted_amount = calculate_flat_percent($converted_amount, $serviceStatData['discount']);
    //        $walletToWallet->order_detail_cause_name = 'discount';
    //        $walletToWallet->notes = 'Wallet To Wallet Discount form ' . $master_user_name;
    //        $walletToWallet->step = 5;
    //        //$data->order_detail_parent_id = $orderDetailStore->getKey();
    //        //$updateData['order_data']['previous_amount'] = 0;
    //        $orderDetailStoreForDiscount = Transaction::orderDetail()->create(Transaction::orderDetail()->orderDetailsDataArrange($walletToWallet));
    //        $orderDetailStoreForDiscountForMaster = $orderDetailStoreForCharge->replicate();
    //        $orderDetailStoreForDiscountForMaster->user_id = $walletToWallet->sender_receiver_id;
    //        $orderDetailStoreForDiscountForMaster->sender_receiver_id = $walletToWallet->user_id;
    //        $orderDetailStoreForDiscountForMaster->order_detail_amount = -calculate_flat_percent($amount, $serviceStatData['discount']);
    //        $orderDetailStoreForDiscountForMaster->converted_amount = -calculate_flat_percent($converted_amount, $serviceStatData['discount']);
    //        $orderDetailStoreForDiscountForMaster->order_detail_cause_name = 'discount';
    //        $orderDetailStoreForDiscountForMaster->notes = 'Wallet To Wallet Discount to ' . $user_name;
    //        $orderDetailStoreForDiscountForMaster->step = 6;
    //        $orderDetailStoreForDiscountForMaster->save();
    //
    //        //'Point Transfer Commission Send to ' . $masterUser->name;
    //        //'Point Transfer Commission Receive from ' . $receiver->name;
    //
    //        $userAccountData['current_amount'] = Transaction::orderDetail()->list([
    //            'get_order_detail_amount_sum' => true,
    //            'user_id' => $walletToWallet->user_id,
    //            'order_detail_currency' => $walletToWallet->currency,
    //        ]);
    //
    //        $userAccountData['spent_amount'] = Transaction::orderDetail()->list([
    //            'get_order_detail_amount_sum' => true,
    //            'user_id' => $walletToWallet->user_id,
    //            'order_id' => $walletToWallet->getKey(),
    //            'order_detail_currency' => $walletToWallet->currency,
    //        ]);
    //
    //        logger('User Account Data', $userAccountData);
    //
    //        return $userAccountData;
    //
    //    }
    //
    //    /**
    //     * @return int[]
    //     */
    //    public function creditTransaction($walletToWallet): array
    //    {
    //        $userAccountData = [
    //            'previous_amount' => null,
    //            'current_amount' => null,
    //            'spent_amount' => null,
    //        ];
    //
    //        //Collect Current Balance as Previous Balance
    //        $userAccountData['previous_amount'] = Transaction::orderDetail()->list([
    //            'get_order_detail_amount_sum' => true,
    //            'user_id' => $walletToWallet->user_id,
    //            'converted_currency' => $walletToWallet->converted_currency,
    //        ]);
    //
    //        $serviceStatData = $walletToWallet->order_data['service_stat_data'];
    //        $master_user_name = $walletToWallet->order_data['master_user_name'];
    //        $user_name = $walletToWallet->order_data['user_name'];
    //
    //        $walletToWallet->order_detail_cause_name = 'cash_withdraw';
    //        //$data->order_detail_number = $data->order_data['accepted_number'];
    //        $walletToWallet->order_detail_response_id = $walletToWallet->order_data['purchase_number'];
    //        $walletToWallet->notes = 'Wallet To Wallet send to ' . $walletToWallet->amount . ' ' . $walletToWallet->currency . ' to ' . $walletToWallet->converted_amount . ' ' . $walletToWallet->converted_currency . ' Refund From ' . $master_user_name;
    //        $orderDetailStore = Transaction::orderDetail()->create(Transaction::orderDetail()->orderDetailsDataArrange($walletToWallet));
    //        $orderDetailStore->order_detail_parent_id = $walletToWallet->order_detail_parent_id = $orderDetailStore->getKey();
    //        $orderDetailStore->save();
    //        $orderDetailStore->fresh();
    //        $amount = $walletToWallet->amount;
    //        $converted_amount = $walletToWallet->converted_amount;
    //        $orderDetailStoreForMaster = $orderDetailStore->replicate();
    //        $orderDetailStoreForMaster->user_id = $walletToWallet->sender_receiver_id;
    //        $orderDetailStoreForMaster->sender_receiver_id = $walletToWallet->user_id;
    //        $orderDetailStoreForMaster->order_detail_amount = -$amount;
    //        $orderDetailStoreForMaster->converted_amount = -$converted_amount;
    //        $orderDetailStoreForMaster->step = 2;
    //        $orderDetailStoreForMaster->notes = 'Wallet To Wallet receive from ' . $walletToWallet->amount . ' ' . $walletToWallet->currency . ' to ' . $walletToWallet->converted_amount . ' ' . $walletToWallet->converted_currency . ' Send to ' . $user_name;
    //        $orderDetailStoreForMaster->save();
    //
    //        //For Charge
    //        //        $walletToWallet->amount = -calculate_flat_percent($amount, $serviceStatData['charge']);
    //        //        $walletToWallet->converted_amount = -calculate_flat_percent($converted_amount, $serviceStatData['charge']);
    //        //        $walletToWallet->order_detail_cause_name = 'charge';
    //        //        $walletToWallet->order_detail_parent_id = $orderDetailStore->getKey();
    //        //        $walletToWallet->notes = 'Wallet To Wallet send to '.$walletToWallet->amount.' '.$walletToWallet->currency.' to '.$walletToWallet->converted_amount.' '.$walletToWallet->converted_currency.' Charge Receive from '.$master_user_name;
    //        //        $walletToWallet->step = 3;
    //        //        $walletToWallet->order_detail_parent_id = $orderDetailStore->getKey();
    //        //        $orderDetailStoreForCharge = Transaction::orderDetail()->create(Transaction::orderDetail()->orderDetailsDataArrange($walletToWallet));
    //        //        $orderDetailStoreForChargeForMaster = $orderDetailStoreForCharge->replicate();
    //        //        $orderDetailStoreForChargeForMaster->user_id = $walletToWallet->sender_receiver_id;
    //        //        $orderDetailStoreForChargeForMaster->sender_receiver_id = $walletToWallet->user_id;
    //        //        $orderDetailStoreForChargeForMaster->order_detail_amount = calculate_flat_percent($amount, $serviceStatData['charge']);
    //        //        $orderDetailStoreForChargeForMaster->converted_amount = calculate_flat_percent($converted_amount, $serviceStatData['charge']);
    //        //        $orderDetailStoreForChargeForMaster->order_detail_cause_name = 'charge';
    //        //        $orderDetailStoreForChargeForMaster->notes = 'Wallet To Wallet receive from '.$walletToWallet->amount.' '.$walletToWallet->currency.' to '.$walletToWallet->converted_amount.' '.$walletToWallet->converted_currency.' Charge Send to '.$user_name;
    //        //        $orderDetailStoreForChargeForMaster->step = 4;
    //        //        $orderDetailStoreForChargeForMaster->save();
    //
    //        //For Discount
    //        //        $walletToWallet->amount = calculate_flat_percent($amount, $serviceStatData['discount']);
    //        //        $walletToWallet->converted_amount = calculate_flat_percent($converted_amount, $serviceStatData['discount']);
    //        //        $walletToWallet->order_detail_cause_name = 'discount';
    //        //        $walletToWallet->notes = 'Wallet To Wallet receive from '.$walletToWallet->amount.' '.$walletToWallet->currency.' to '.$walletToWallet->converted_amount.' '.$walletToWallet->converted_currency.' Discount form '.$master_user_name;
    //        //        $walletToWallet->step = 5;
    //        //        //$data->order_detail_parent_id = $orderDetailStore->getKey();
    //        //        $updateData['order_data']['previous_amount'] = 0;
    //        //        $orderDetailStoreForDiscount = Transaction::orderDetail()->create(Transaction::orderDetail()->orderDetailsDataArrange($walletToWallet));
    //        //        $orderDetailStoreForDiscountForMaster = $orderDetailStoreForDiscount->replicate();
    //        //        $orderDetailStoreForDiscountForMaster->user_id = $walletToWallet->sender_receiver_id;
    //        //        $orderDetailStoreForDiscountForMaster->sender_receiver_id = $walletToWallet->user_id;
    //        //        $orderDetailStoreForDiscountForMaster->order_detail_amount = -calculate_flat_percent($amount, $serviceStatData['discount']);
    //        //        $orderDetailStoreForDiscountForMaster->converted_amount = -calculate_flat_percent($converted_amount, $serviceStatData['discount']);
    //        //        $orderDetailStoreForDiscountForMaster->order_detail_cause_name = 'discount';
    //        //        $orderDetailStoreForDiscountForMaster->notes = 'Wallet To Wallet send to '.$walletToWallet->amount.' '.$walletToWallet->currency.' to '.$walletToWallet->converted_amount.' '.$walletToWallet->converted_currency.' Discount to '.$user_name;
    //        //        $orderDetailStoreForDiscountForMaster->step = 6;
    //        //        $orderDetailStoreForDiscountForMaster->save();
    //
    //        //'Point Transfer Commission Send to ' . $masterUser->name;
    //        //'Point Transfer Commission Receive from ' . $receiver->name;
    //
    //        $userAccountData['current_amount'] = Transaction::orderDetail()->list([
    //            'get_order_detail_amount_sum' => true,
    //            'user_id' => $walletToWallet->user_id,
    //            'converted_currency' => $walletToWallet->converted_currency,
    //        ]);
    //
    //        $userAccountData['spent_amount'] = Transaction::orderDetail()->list([
    //            'get_order_detail_amount_sum' => true,
    //            'user_id' => $walletToWallet->user_id,
    //            'order_id' => $walletToWallet->getKey(),
    //            'converted_currency' => $walletToWallet->converted_currency,
    //        ]);
    //
    //        return $userAccountData;
    //
    //    }

    /**
     * @return int[]
     */
    public function walletToWalletAccept($deposit): array
    {
        $userAccountData = [
            'previous_amount' => null,
            'current_amount' => null,
            'deposit_amount' => null,
        ];

        //Collect Current Balance as Previous Balance
        $userAccountData['previous_amount'] = Transaction::orderDetail()->list([
            'get_order_detail_amount_sum' => true,
            'user_id' => $deposit->user_id,
            'converted_currency' => $deposit->converted_currency,
        ]);

        $serviceStatData = $deposit->order_data['service_stat_data'];
        $master_user_name = $deposit->order_data['master_user_name'];
        $user_name = $deposit->order_data['user_name'];

        $deposit->order_detail_cause_name = 'cash_deposit';
        //$deposit->order_detail_number = $deposit->order_data['accepted_number'];
        $deposit->order_detail_response_id = $deposit->order_data['purchase_number'];
        $deposit->notes = 'Wallet To Wallet receive from '.$master_user_name;
        $orderDetailStore = Transaction::orderDetail()->create(Transaction::orderDetail()->orderDetailsDataArrange($deposit));
        $orderDetailStore->order_detail_parent_id = $deposit->order_detail_parent_id = $orderDetailStore->getKey();
        $orderDetailStore->save();
        $orderDetailStore->fresh();
        $amount = $deposit->amount;
        $converted_amount = $deposit->converted_amount;
        $orderDetailStoreForMaster = $orderDetailStore->replicate();
        $orderDetailStoreForMaster->user_id = $deposit->sender_receiver_id;
        $orderDetailStoreForMaster->sender_receiver_id = $deposit->user_id;
        $orderDetailStoreForMaster->order_detail_amount = -$amount;
        $orderDetailStoreForMaster->converted_amount = -$converted_amount;
        $orderDetailStoreForMaster->step = 2;
        $orderDetailStoreForMaster->notes = 'Wallet To Wallet send to '.$user_name;
        $orderDetailStoreForMaster->save();

        //For Charge
        /*$deposit->amount = -calculate_flat_percent($amount, $serviceStatData['charge']);
        $deposit->converted_amount = -calculate_flat_percent($converted_amount, $serviceStatData['charge']);
        $deposit->order_detail_cause_name = 'charge';
        $deposit->order_detail_parent_id = $orderDetailStore->getKey();
        $deposit->notes = 'Wallet to Wallet Deposit Charge Receive from '.$master_user_name;
        $deposit->step = 3;
        $deposit->order_detail_parent_id = $orderDetailStore->getKey();
        $orderDetailStoreForCharge = Transaction::orderDetail()->create(Transaction::orderDetail()->orderDetailsDataArrange($deposit));
        $orderDetailStoreForChargeForMaster = $orderDetailStoreForCharge->replicate();
        $orderDetailStoreForChargeForMaster->user_id = $deposit->sender_receiver_id;
        $orderDetailStoreForChargeForMaster->sender_receiver_id = $deposit->user_id;
        $orderDetailStoreForChargeForMaster->order_detail_amount = calculate_flat_percent($amount, $serviceStatData['charge']);
        $orderDetailStoreForChargeForMaster->converted_amount = calculate_flat_percent($converted_amount, $serviceStatData['charge']);
        $orderDetailStoreForChargeForMaster->order_detail_cause_name = 'charge';
        $orderDetailStoreForChargeForMaster->notes = 'Wallet to Wallet Deposit Charge Send to '.$user_name;
        $orderDetailStoreForChargeForMaster->step = 4;
        $orderDetailStoreForChargeForMaster->save();*/

        //discount
        $deposit->amount = calculate_flat_percent($amount, $serviceStatData['discount']);
        $deposit->converted_amount = calculate_flat_percent($converted_amount, $serviceStatData['discount']);
        $deposit->order_detail_cause_name = 'discount';
        $deposit->notes = 'Wallet to Wallet Discount form '.$master_user_name;
        $deposit->step = 5;
        //$data->order_detail_parent_id = $orderDetailStore->getKey();
        //$updateData['order_data']['previous_amount'] = 0;
        $orderDetailStoreForDiscount = Transaction::orderDetail()->create(Transaction::orderDetail()->orderDetailsDataArrange($deposit));
        $orderDetailStoreForDiscountForMaster = $orderDetailStoreForDiscount->replicate();
        $orderDetailStoreForDiscountForMaster->user_id = $deposit->sender_receiver_id;
        $orderDetailStoreForDiscountForMaster->sender_receiver_id = $deposit->user_id;
        $orderDetailStoreForDiscountForMaster->order_detail_amount = -calculate_flat_percent($amount, $serviceStatData['discount']);
        $orderDetailStoreForDiscountForMaster->converted_amount = -calculate_flat_percent($converted_amount, $serviceStatData['discount']);
        $orderDetailStoreForDiscountForMaster->order_detail_cause_name = 'discount';
        $orderDetailStoreForDiscountForMaster->notes = 'Wallet to Wallet Deposit Discount to '.$user_name;
        $orderDetailStoreForDiscountForMaster->step = 6;
        $orderDetailStoreForDiscountForMaster->save();

        //For commission
        $deposit->amount = -calculate_flat_percent($amount, $serviceStatData['commission']);
        $deposit->converted_amount = -calculate_flat_percent($converted_amount, $serviceStatData['commission']);
        $deposit->order_detail_cause_name = 'commission';
        $deposit->order_detail_parent_id = $orderDetailStore->getKey();
        $deposit->notes = 'Wallet to Wallet Deposit Commission Receive from '.$master_user_name;
        $deposit->step = 3;
        $deposit->order_detail_parent_id = $orderDetailStore->getKey();
        $orderDetailStoreForCommission = Transaction::orderDetail()->create(Transaction::orderDetail()->orderDetailsDataArrange($deposit));
        $orderDetailStoreForCommissionForMaster = $orderDetailStoreForCommission->replicate();
        $orderDetailStoreForCommissionForMaster->user_id = $deposit->sender_receiver_id;
        $orderDetailStoreForCommissionForMaster->sender_receiver_id = $deposit->user_id;
        $orderDetailStoreForCommissionForMaster->order_detail_amount = calculate_flat_percent($amount, $serviceStatData['commission']);
        $orderDetailStoreForCommissionForMaster->converted_amount = calculate_flat_percent($converted_amount, $serviceStatData['commission']);
        $orderDetailStoreForCommissionForMaster->order_detail_cause_name = 'commission';
        $orderDetailStoreForCommissionForMaster->notes = 'Wallet to Wallet Deposit Commission Send to '.$user_name;
        $orderDetailStoreForCommissionForMaster->step = 4;
        $orderDetailStoreForCommissionForMaster->save();

        $userAccountData['current_amount'] = Transaction::orderDetail()->list([
            'get_order_detail_amount_sum' => true,
            'user_id' => $deposit->user_id,
            'converted_currency' => $deposit->converted_currency,
        ]);

        $userAccountData['deposit_amount'] = Transaction::orderDetail()->list([
            'get_order_detail_amount_sum' => true,
            'user_id' => $deposit->user_id,
            'order_id' => $deposit->getKey(),
            'converted_currency' => $deposit->converted_currency,
        ]);

        //'Point Transfer Commission Send to ' . $masterUser->name;
        //'Point Transfer Commission Receive from ' . $receiver->name;
        return $userAccountData;

    }
}
