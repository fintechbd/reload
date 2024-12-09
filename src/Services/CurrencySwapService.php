<?php

namespace Fintech\Reload\Services;

use Exception;
use Fintech\Auth\Facades\Auth;
use Fintech\Business\Exceptions\BusinessException;
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
use Fintech\Core\Exceptions\UpdateOperationException;
use Fintech\MetaData\Facades\MetaData;
use Fintech\Reload\Interfaces\CurrencySwapRepository;
use Fintech\Transaction\Facades\Transaction;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Class CurrencySwapService
 */
class CurrencySwapService
{
    use \Fintech\Core\Traits\HasFindWhereSearch;

    private CurrencySwapRepository $currencySwapRepository;

    /**
     * CurrencySwapService constructor.
     */
    public function __construct(CurrencySwapRepository $currencySwapRepository)
    {
        $this->currencySwapRepository = $currencySwapRepository;
    }

    public function find($id, $onlyTrashed = false): ?BaseModel
    {
        return $this->currencySwapRepository->find($id, $onlyTrashed);
    }

    public function update($id, array $inputs = []): ?BaseModel
    {
        return $this->currencySwapRepository->update($id, $inputs);
    }

    public function destroy($id)
    {
        return $this->currencySwapRepository->delete($id);
    }

    public function restore($id)
    {
        return $this->currencySwapRepository->restore($id);
    }

    public function export(array $filters): Paginator|Collection
    {
        return $this->currencySwapRepository->list($filters);
    }

    public function list(array $filters = []): Paginator|Collection
    {
        return $this->currencySwapRepository->list($filters);

    }

    public function import(array $filters): ?BaseModel
    {
        return $this->currencySwapRepository->create($filters);
    }

    /**
     * @throws UpdateOperationException
     * @throws RequestAmountExistsException
     * @throws BusinessException
     * @throws CurrencyUnavailableException
     * @throws RequestOrderExistsException
     * @throws OrderRequestFailedException
     * @throws MasterCurrencyUnavailableException
     */
    public function create(array $inputs = []): ?BaseModel
    {
        $user_id = $inputs['user_id'] ?? null;

        $orderType = OrderType::CurrencySwap;

        $user = Auth::user()->find($user_id);
        if (! $user) {
            throw (new ModelNotFoundException)->setModel(config('fintech.auth.auth_model'), $user_id);
        }
        $inputs['source_country_id'] = $inputs['source_country_id'] ?? $user->profile?->present_country_id;

        $senderAccount = Transaction::userAccount()->findWhere(['user_id' => $user->getKey(), 'country_id' => $inputs['source_country_id']]);
        if (! $senderAccount) {
            throw new CurrencyUnavailableException($inputs['source_country_id']);
        }

        $recipientAccount = Transaction::userAccount()->findWhere(['user_id' => $user->getKey(), 'country_id' => $inputs['destination_country_id']]);
        if (! $recipientAccount) {
            throw new CurrencyUnavailableException($inputs['destination_country_id']);
        }

        // Sender System User
        $senderMasterUser = Auth::user()->findWhere(['role_name' => SystemRole::MasterUser->value, 'country_id' => $inputs['source_country_id']]);
        if (! $senderMasterUser) {
            throw new MasterCurrencyUnavailableException($inputs['source_country_id']);
        }

        //        // Recipient System User
        //        $recipientMasterUser = Auth::user()->findWhere(['role_name' => SystemRole::MasterUser->value, 'country_id' => $inputs['destination_country_id']]);
        //        if (! $recipientMasterUser) {
        //            throw new MasterCurrencyUnavailableException($inputs['destination_country_id']);
        //        }

        if (Transaction::orderQueue()->addToQueueUserWise($user_id) == 0) {
            throw new RequestOrderExistsException;
        }

        $inputs['transaction_form_id'] = Transaction::transactionForm()->findWhere(['code' => 'currency_swap'])->getKey();

        if (Transaction::order()->transactionDelayCheck($inputs)['countValue'] > 0) {
            throw new RequestAmountExistsException;
        }

        $role = $user->roles?->first() ?? null;
        $inputs['order_data']['role_id'] = $role->id;
        $inputs['order_data']['order_type'] = $orderType;
        $inputs['description'] = "Currency Swap from {$inputs['currency']} to {$inputs['converted_currency']}";
        $inputs['notes'] = $inputs['notes'] ?? "Currency Swap from {$inputs['currency']} to {$inputs['converted_currency']}";
        $inputs['status'] = OrderStatus::Success;
        $inputs['sender_receiver_id'] = $senderMasterUser->getKey();
        $inputs['is_refunded'] = false;
        $inputs['risk'] = RiskProfile::Low;
        $inputs['order_data']['is_reverse'] = boolval($inputs['reverse'] ?? 0);
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

        $inputs['order_data']['created_by'] = $user->name ?? 'N/A';
        $inputs['order_data']['user_name'] = $user->name;
        $inputs['order_data']['created_by_mobile_number'] = $user->mobile ?? 'N/A';
        $inputs['order_data']['created_by_email'] = $user->email ?? 'N/A';
        $inputs['order_data']['created_at'] = now();
        $inputs['order_data']['sender_id'] = $user->id ?? 'N/A';
        $inputs['order_data']['sender_by'] = $user->name ?? 'N/A';
        $inputs['order_data']['sender_mobile'] = $user->mobile ?? 'N/A';
        $inputs['order_data']['sender_email'] = $user->email ?? 'N/A';
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
        $inputs['order_data']['system_notification_variable_success'] = 'currency_swap_success';
        $inputs['order_data']['system_notification_variable_failed'] = 'currency_swap_failed';
        $inputs['order_data']['purchase_number'] = next_purchase_number(MetaData::country()->find($inputs['source_country_id'])->iso3);
        $inputs['order_number'] = $inputs['order_data']['purchase_number'];

        $service = Business::service()->find($inputs['service_id']);
        $vendor = $service->serviceVendor;
        $inputs['service_vendor_id'] = $vendor?->getKey() ?? null;
        $inputs['vendor'] = $vendor?->service_vendor_slug ?? null;

        $inputs['timeline'][] = [
            'message' => 'Currency Swap entry created successfully',
            'flag' => 'create',
            'timestamp' => now(),
        ];
        $inputs['parent_id'] = null;

        DB::beginTransaction();

        try {
            $currencySwap = $this->currencySwapRepository->create($inputs);
            DB::commit();
            $senderAccounting = Transaction::accounting($currencySwap, $user->getKey());
            $currencySwap = $senderAccounting->debitTransaction();
            $senderAccounting->debitBalanceFromUserAccount();

            $recipientAccounting = Transaction::accounting($currencySwap, $user->getKey());
            $currencySwap = $recipientAccounting->creditTransaction();
            $recipientAccounting->creditBalanceToUserAccount();

            Transaction::orderQueue()->removeFromQueueUserWise($user_id);

            $currencySwap->refresh();
            //@TODO not working on double entry need fix ;-(
            //            event(new WalletToWalletReceived($senderWalletToWallet));

            return $currencySwap;

        } catch (Exception $e) {

            DB::rollBack();

            Transaction::orderQueue()->removeFromQueueUserWise($user_id);

            throw new OrderRequestFailedException($orderType->value, 0, $e);
        }
    }

    /**
     * @return int[]
     */
    public function debitTransaction($data): array
    {
        $userAccountData = [
            'previous_amount' => null,
            'current_amount' => null,
            'spent_amount' => null,
        ];

        //Collect Current Balance as Previous Balance
        $userAccountData['previous_amount'] = Transaction::orderDetail()->list([
            'get_order_detail_amount_sum' => true,
            'user_id' => $data->user_id,
            'order_detail_currency' => $data->converted_currency,
        ]);

        $serviceStatData = $data->order_data['service_stat_data'];
        $master_user_name = $data->order_data['master_user_name'];
        $user_name = $data->order_data['user_name'];

        $amount = $data->amount;
        $converted_amount = $data->converted_amount;
        $data->amount = -$amount;
        $data->converted_amount = -$converted_amount;
        $data->order_detail_cause_name = 'cash_withdraw';
        $data->order_detail_number = $data->order_data['purchase_number'];
        $data->order_detail_response_id = $data->order_data['purchase_number'];
        $data->notes = 'Currency Swap '.$data->amount.' '.$data->currency.' to '.$data->converted_amount.' '.$data->converted_currency.' Payment Send to '.$master_user_name;
        $orderDetailStore = Transaction::orderDetail()->create(Transaction::orderDetail()->orderDetailsDataArrange($data));
        $orderDetailStore->order_detail_parent_id = $data->order_detail_parent_id = $orderDetailStore->getKey();
        $orderDetailStore->save();
        $orderDetailStore->fresh();
        $orderDetailStoreForMaster = $orderDetailStore->replicate();
        $orderDetailStoreForMaster->user_id = $data->sender_receiver_id;
        $orderDetailStoreForMaster->sender_receiver_id = $data->user_id;
        $orderDetailStoreForMaster->order_detail_amount = $amount;
        $orderDetailStoreForMaster->converted_amount = $converted_amount;
        $orderDetailStoreForMaster->step = 2;
        $orderDetailStoreForMaster->notes = 'Currency Swap '.$data->amount.' '.$data->currency.' to '.$data->converted_amount.' '.$data->converted_currency.' Payment Receive From'.$user_name;
        $orderDetailStoreForMaster->save();

        //For Charge
        $data->amount = calculate_flat_percent($amount, $serviceStatData['charge']);
        $data->converted_amount = calculate_flat_percent($converted_amount, $serviceStatData['charge']);
        $data->order_detail_cause_name = 'charge';
        $data->order_detail_parent_id = $orderDetailStore->getKey();
        $data->notes = 'Currency Swap '.$data->amount.' '.$data->currency.' to '.$data->converted_amount.' '.$data->converted_currency.' Charge Send to '.$master_user_name;
        $data->step = 3;
        $data->order_detail_parent_id = $orderDetailStore->getKey();
        $orderDetailStoreForCharge = Transaction::orderDetail()->create(Transaction::orderDetail()->orderDetailsDataArrange($data));
        $orderDetailStoreForChargeForMaster = $orderDetailStoreForCharge->replicate();
        $orderDetailStoreForChargeForMaster->user_id = $data->sender_receiver_id;
        $orderDetailStoreForChargeForMaster->sender_receiver_id = $data->user_id;
        $orderDetailStoreForChargeForMaster->order_detail_amount = -calculate_flat_percent($amount, $serviceStatData['charge']);
        $orderDetailStoreForChargeForMaster->converted_amount = -calculate_flat_percent($converted_amount, $serviceStatData['charge']);
        $orderDetailStoreForChargeForMaster->order_detail_cause_name = 'charge';
        $orderDetailStoreForChargeForMaster->notes = 'Currency Swap '.$data->amount.' '.$data->currency.' to '.$data->converted_amount.' '.$data->converted_currency.' Charge Receive from '.$user_name;
        $orderDetailStoreForChargeForMaster->step = 4;
        $orderDetailStoreForChargeForMaster->save();

        //For Discount
        $data->amount = -calculate_flat_percent($amount, $serviceStatData['discount']);
        $data->converted_amount = -calculate_flat_percent($converted_amount, $serviceStatData['discount']);
        $data->order_detail_cause_name = 'discount';
        $data->notes = 'Currency Swap '.$data->amount.' '.$data->currency.' to '.$data->converted_amount.' '.$data->converted_currency.' Discount form '.$master_user_name;
        $data->step = 5;
        //$data->order_detail_parent_id = $orderDetailStore->getKey();
        //$updateData['order_data']['previous_amount'] = 0;
        $orderDetailStoreForDiscount = Transaction::orderDetail()->create(Transaction::orderDetail()->orderDetailsDataArrange($data));
        $orderDetailStoreForDiscountForMaster = $orderDetailStoreForDiscount->replicate();
        $orderDetailStoreForDiscountForMaster->user_id = $data->sender_receiver_id;
        $orderDetailStoreForDiscountForMaster->sender_receiver_id = $data->user_id;
        $orderDetailStoreForDiscountForMaster->order_detail_amount = calculate_flat_percent($amount, $serviceStatData['discount']);
        $orderDetailStoreForDiscountForMaster->converted_amount = calculate_flat_percent($converted_amount, $serviceStatData['discount']);
        $orderDetailStoreForDiscountForMaster->order_detail_cause_name = 'discount';
        $orderDetailStoreForDiscountForMaster->notes = 'Currency Swap '.$data->amount.' '.$data->currency.' to '.$data->converted_amount.' '.$data->converted_currency.' Discount to '.$user_name;
        $orderDetailStoreForDiscountForMaster->step = 6;
        $orderDetailStoreForDiscountForMaster->save();

        //'Point Transfer Commission Send to ' . $masterUser->name;
        //'Point Transfer Commission Receive from ' . $receiver->name;

        $userAccountData['current_amount'] = Transaction::orderDetail()->list([
            'get_order_detail_amount_sum' => true,
            'user_id' => $data->user_id,
            'converted_currency' => $data->converted_currency,
        ]);

        $userAccountData['spent_amount'] = Transaction::orderDetail()->list([
            'get_order_detail_amount_sum' => true,
            'user_id' => $data->user_id,
            'order_id' => $data->getKey(),
            'converted_currency' => $data->converted_currency,
        ]);

        return $userAccountData;

    }

    /**
     * @return int[]
     */
    public function creditTransaction($data): array
    {
        $userAccountData = [
            'previous_amount' => null,
            'current_amount' => null,
            'spent_amount' => null,
        ];

        //Collect Current Balance as Previous Balance
        $userAccountData['previous_amount'] = Transaction::orderDetail()->list([
            'get_order_detail_amount_sum' => true,
            'user_id' => $data->user_id,
            'converted_currency' => $data->converted_currency,
        ]);

        $serviceStatData = $data->order_data['service_stat_data'];
        $master_user_name = $data->order_data['master_user_name'];
        $user_name = $data->order_data['user_name'];

        $data->order_detail_cause_name = 'cash_withdraw';
        //$data->order_detail_number = $data->order_data['accepted_number'];
        $data->order_detail_response_id = $data->order_data['purchase_number'];
        $data->notes = 'Currency Swap '.$data->amount.' '.$data->currency.' to '.$data->converted_amount.' '.$data->converted_currency.' Refund From '.$master_user_name;
        $orderDetailStore = Transaction::orderDetail()->create(Transaction::orderDetail()->orderDetailsDataArrange($data));
        $orderDetailStore->order_detail_parent_id = $data->order_detail_parent_id = $orderDetailStore->getKey();
        $orderDetailStore->save();
        $orderDetailStore->fresh();
        $amount = $data->amount;
        $converted_amount = $data->converted_amount;
        $orderDetailStoreForMaster = $orderDetailStore->replicate();
        $orderDetailStoreForMaster->user_id = $data->sender_receiver_id;
        $orderDetailStoreForMaster->sender_receiver_id = $data->user_id;
        $orderDetailStoreForMaster->order_detail_amount = -$amount;
        $orderDetailStoreForMaster->converted_amount = -$converted_amount;
        $orderDetailStoreForMaster->step = 2;
        $orderDetailStoreForMaster->notes = 'Currency Swap '.$data->amount.' '.$data->currency.' to '.$data->converted_amount.' '.$data->converted_currency.' Send to '.$user_name;
        $orderDetailStoreForMaster->save();

        //For Charge
        $data->amount = -calculate_flat_percent($amount, $serviceStatData['charge']);
        $data->converted_amount = -calculate_flat_percent($converted_amount, $serviceStatData['charge']);
        $data->order_detail_cause_name = 'charge';
        $data->order_detail_parent_id = $orderDetailStore->getKey();
        $data->notes = 'Currency Swap '.$data->amount.' '.$data->currency.' to '.$data->converted_amount.' '.$data->converted_currency.' Charge Receive from '.$master_user_name;
        $data->step = 3;
        $data->order_detail_parent_id = $orderDetailStore->getKey();
        $orderDetailStoreForCharge = Transaction::orderDetail()->create(Transaction::orderDetail()->orderDetailsDataArrange($data));
        $orderDetailStoreForChargeForMaster = $orderDetailStoreForCharge->replicate();
        $orderDetailStoreForChargeForMaster->user_id = $data->sender_receiver_id;
        $orderDetailStoreForChargeForMaster->sender_receiver_id = $data->user_id;
        $orderDetailStoreForChargeForMaster->order_detail_amount = calculate_flat_percent($amount, $serviceStatData['charge']);
        $orderDetailStoreForChargeForMaster->converted_amount = calculate_flat_percent($converted_amount, $serviceStatData['charge']);
        $orderDetailStoreForChargeForMaster->order_detail_cause_name = 'charge';
        $orderDetailStoreForChargeForMaster->notes = 'Currency Swap '.$data->amount.' '.$data->currency.' to '.$data->converted_amount.' '.$data->converted_currency.' Charge Send to '.$user_name;
        $orderDetailStoreForChargeForMaster->step = 4;
        $orderDetailStoreForChargeForMaster->save();

        //For Discount
        $data->amount = calculate_flat_percent($amount, $serviceStatData['discount']);
        $data->converted_amount = calculate_flat_percent($converted_amount, $serviceStatData['discount']);
        $data->order_detail_cause_name = 'discount';
        $data->notes = 'Currency Swap '.$data->amount.' '.$data->currency.' to '.$data->converted_amount.' '.$data->converted_currency.' Discount form '.$master_user_name;
        $data->step = 5;
        //$data->order_detail_parent_id = $orderDetailStore->getKey();
        $updateData['order_data']['previous_amount'] = 0;
        $orderDetailStoreForDiscount = Transaction::orderDetail()->create(Transaction::orderDetail()->orderDetailsDataArrange($data));
        $orderDetailStoreForDiscountForMaster = $orderDetailStoreForDiscount->replicate();
        $orderDetailStoreForDiscountForMaster->user_id = $data->sender_receiver_id;
        $orderDetailStoreForDiscountForMaster->sender_receiver_id = $data->user_id;
        $orderDetailStoreForDiscountForMaster->order_detail_amount = -calculate_flat_percent($amount, $serviceStatData['discount']);
        $orderDetailStoreForDiscountForMaster->converted_amount = -calculate_flat_percent($converted_amount, $serviceStatData['discount']);
        $orderDetailStoreForDiscountForMaster->order_detail_cause_name = 'discount';
        $orderDetailStoreForDiscountForMaster->notes = 'Currency Swap '.$data->amount.' '.$data->currency.' to '.$data->converted_amount.' '.$data->converted_currency.' Discount to '.$user_name;
        $orderDetailStoreForDiscountForMaster->step = 6;
        $orderDetailStoreForDiscountForMaster->save();

        //'Point Transfer Commission Send to ' . $masterUser->name;
        //'Point Transfer Commission Receive from ' . $receiver->name;

        $userAccountData['current_amount'] = Transaction::orderDetail()->list([
            'get_order_detail_amount_sum' => true,
            'user_id' => $data->user_id,
            'converted_currency' => $data->converted_currency,
        ]);

        $userAccountData['spent_amount'] = Transaction::orderDetail()->list([
            'get_order_detail_amount_sum' => true,
            'user_id' => $data->user_id,
            'order_id' => $data->getKey(),
            'converted_currency' => $data->converted_currency,
        ]);

        return $userAccountData;

    }

    /**
     * @return int[]
     */
    public function currencySwapAccept($deposit): array
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
        $deposit->notes = 'Currency Swap receive from '.$master_user_name;
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
        $orderDetailStoreForMaster->notes = 'Currency Swap send to '.$user_name;
        $orderDetailStoreForMaster->save();

        //For Charge
        /*$deposit->amount = -calculate_flat_percent($amount, $serviceStatData['charge']);
        $deposit->converted_amount = -calculate_flat_percent($converted_amount, $serviceStatData['charge']);
        $deposit->order_detail_cause_name = 'charge';
        $deposit->order_detail_parent_id = $orderDetailStore->getKey();
        $deposit->notes = 'Currency Swap Deposit Charge Receive from '.$master_user_name;
        $deposit->step = 3;
        $deposit->order_detail_parent_id = $orderDetailStore->getKey();
        $orderDetailStoreForCharge = Transaction::orderDetail()->create(Transaction::orderDetail()->orderDetailsDataArrange($deposit));
        $orderDetailStoreForChargeForMaster = $orderDetailStoreForCharge->replicate();
        $orderDetailStoreForChargeForMaster->user_id = $deposit->sender_receiver_id;
        $orderDetailStoreForChargeForMaster->sender_receiver_id = $deposit->user_id;
        $orderDetailStoreForChargeForMaster->order_detail_amount = calculate_flat_percent($amount, $serviceStatData['charge']);
        $orderDetailStoreForChargeForMaster->converted_amount = calculate_flat_percent($converted_amount, $serviceStatData['charge']);
        $orderDetailStoreForChargeForMaster->order_detail_cause_name = 'charge';
        $orderDetailStoreForChargeForMaster->notes = 'Currency Swap Deposit Charge Send to '.$user_name;
        $orderDetailStoreForChargeForMaster->step = 4;
        $orderDetailStoreForChargeForMaster->save();*/

        /*$deposit->amount = calculate_flat_percent($amount, $serviceStatData['discount']);
        $deposit->converted_amount = calculate_flat_percent($converted_amount, $serviceStatData['discount']);
        $deposit->order_detail_cause_name = 'discount';
        $deposit->notes = 'Currency Swap Discount form '.$master_user_name;
        $deposit->step = 5;*/
        //$data->order_detail_parent_id = $orderDetailStore->getKey();
        //$updateData['order_data']['previous_amount'] = 0;
        /*$orderDetailStoreForDiscount = Transaction::orderDetail()->create(Transaction::orderDetail()->orderDetailsDataArrange($deposit));
        $orderDetailStoreForDiscountForMaster = $orderDetailStoreForCharge->replicate();
        $orderDetailStoreForDiscountForMaster->user_id = $deposit->sender_receiver_id;
        $orderDetailStoreForDiscountForMaster->sender_receiver_id = $deposit->user_id;
        $orderDetailStoreForDiscountForMaster->order_detail_amount = -calculate_flat_percent($amount, $serviceStatData['discount']);
        $orderDetailStoreForDiscountForMaster->converted_amount = -calculate_flat_percent($converted_amount, $serviceStatData['discount']);
        $orderDetailStoreForDiscountForMaster->order_detail_cause_name = 'discount';
        $orderDetailStoreForDiscountForMaster->notes = 'Currency Swap Deposit Discount to '.$user_name;
        $orderDetailStoreForDiscountForMaster->step = 6;
        $orderDetailStoreForDiscountForMaster->save();*/

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
