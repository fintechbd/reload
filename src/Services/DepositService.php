<?php

namespace Fintech\Reload\Services;

use Exception;
use Fintech\Auth\Facades\Auth;
use Fintech\Business\Exceptions\BusinessException;
use Fintech\Business\Facades\Business;
use Fintech\Core\Abstracts\BaseModel;
use Fintech\Core\Enums\Auth\RiskProfile;
use Fintech\Core\Enums\Auth\SystemRole;
use Fintech\Core\Enums\Reload\DepositStatus;
use Fintech\Core\Enums\Transaction\OrderStatusConfig;
use Fintech\Core\Enums\Transaction\OrderType;
use Fintech\Core\Exceptions\Transaction\CurrencyUnavailableException;
use Fintech\Core\Exceptions\Transaction\MasterCurrencyUnavailableException;
use Fintech\Core\Exceptions\Transaction\OrderRequestFailedException;
use Fintech\Core\Exceptions\Transaction\RequestAmountExistsException;
use Fintech\Core\Exceptions\Transaction\RequestOrderExistsException;
use Fintech\Core\Exceptions\UpdateOperationException;
use Fintech\MetaData\Facades\MetaData;
use Fintech\Reload\Events\DepositAccepted;
use Fintech\Reload\Events\DepositReceived;
use Fintech\Reload\Events\DepositRejected;
use Fintech\Reload\Facades\Reload;
use Fintech\Reload\Interfaces\DepositRepository;
use Fintech\Transaction\Facades\Transaction;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Class DepositService
 */
class DepositService
{
    use \Fintech\Core\Traits\HasFindWhereSearch;

    /**
     * DepositService constructor.
     */
    public function __construct(private readonly DepositRepository $depositRepository) {}

    public function find($id, $onlyTrashed = false)
    {
        return $this->depositRepository->find($id, $onlyTrashed);
    }

    public function update($id, array $inputs = [])
    {
        return $this->depositRepository->update($id, $inputs);
    }

    public function destroy($id)
    {
        return $this->depositRepository->delete($id);
    }

    public function restore($id)
    {
        return $this->depositRepository->restore($id);
    }

    public function export(array $filters)
    {
        return $this->depositRepository->list($filters);
    }

    public function list(array $filters = [])
    {
        return $this->depositRepository->list($filters);

    }

    public function import(array $filters)
    {
        return $this->depositRepository->create($filters);
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
        $depositor = Auth::user()->find($inputs['user_id']);

        if (! $depositor) {
            throw (new ModelNotFoundException)->setModel(config('fintech.auth.auth_model'), $inputs['user_id']);
        }

        if (Transaction::orderQueue()->addToQueueUserWise($inputs['user_id']) == 0) {
            throw new RequestOrderExistsException;
        }

        if (! empty($inputs['order_data']['interac_email'])) {
            $inputs['order_data']['order_type'] = OrderType::InteracDeposit;
            $inputs['status'] = DepositStatus::Pending;
            $inputs['description'] = 'Interac e-Transfer Deposit';
        } elseif (! empty($inputs['order_data']['card_token'])) {
            $inputs['order_data']['order_type'] = OrderType::CardDeposit;
            $inputs['status'] = DepositStatus::Pending;
            $inputs['description'] = 'Card Deposit';
        } else {
            $inputs['order_data']['order_type'] = OrderType::BankDeposit;
            $inputs['status'] = DepositStatus::Processing;
            $inputs['description'] = 'Bank Deposit';
        }

        $inputs['source_country_id'] = $inputs['source_country_id'] ?? $depositor->profile?->present_country_id;

        $depositAccount = Transaction::userAccount()->findWhere(['user_id' => $depositor->getKey(), 'country_id' => $inputs['source_country_id']]);

        if (! $depositAccount) {
            throw new CurrencyUnavailableException($inputs['source_country_id']);
        }

        $masterUser = Auth::user()->findWhere(['role_name' => SystemRole::MasterUser->value, 'country_id' => $inputs['source_country_id']]);

        if (! $masterUser) {
            throw new MasterCurrencyUnavailableException($inputs['source_country_id']);
        }

        $inputs['transaction_form_id'] = Transaction::transactionForm()->findWhere(['code' => 'point_reload'])->getKey();

        if (Transaction::order()->transactionDelayCheck($inputs)['countValue'] > 0) {
            throw new RequestAmountExistsException;
        }

        $role = $depositor->roles?->first() ?? null;

        $inputs['order_data']['role_id'] = $role->id;
        $inputs['order_data']['is_reload'] = true;
        $inputs['order_data']['is_reverse'] = false;
        $inputs['sender_receiver_id'] = $masterUser->getKey();
        $inputs['is_refunded'] = false;
        $inputs['risk'] = RiskProfile::Low;
        $inputs['converted_amount'] = $inputs['amount'];
        $inputs['converted_currency'] = $inputs['currency'];
        $inputs['order_data']['created_by'] = $depositor->name ?? 'N/A';
        $inputs['order_data']['created_by_mobile_number'] = $depositor->mobile ?? 'N/A';
        $inputs['order_data']['created_by_email'] = $depositor->email ?? 'N/A';
        $inputs['order_data']['created_at'] = now();
        $inputs['order_data']['service_stat_data'] = Business::serviceStat()->serviceStateData([
            'role_id' => $inputs['order_data']['role_id'],
            'reload' => $inputs['order_data']['is_reload'],
            'reverse' => $inputs['order_data']['is_reverse'],
            'source_country_id' => $inputs['source_country_id'],
            'destination_country_id' => $inputs['destination_country_id'],
            'amount' => $inputs['amount'],
            'service_id' => $inputs['service_id'],
        ]);
        $inputs['order_data']['previous_amount'] = $depositAccount->user_account_data['available_amount'] ?? 0;
        $inputs['order_data']['current_amount'] = $inputs['order_data']['previous_amount'] + $inputs['order_data']['service_stat_data']['total_amount'];
        $inputs['order_data']['master_user_name'] = $masterUser->name;
        $inputs['order_data']['user_name'] = $depositor->name;
        $inputs['order_data']['purchase_number'] = next_purchase_number(MetaData::country()->find($inputs['source_country_id'])->iso3);
        $inputs['order_number'] = $inputs['order_data']['purchase_number'];

        $service = Business::service()->find($inputs['service_id']);
        $vendor = $service->serviceVendor;
        $inputs['service_vendor_id'] = $vendor?->getKey() ?? null;
        $inputs['vendor'] = $vendor?->service_vendor_slug ?? null;

        $inputs['timeline'][] = [
            'message' => 'Fund Deposit entry created successfully',
            'flag' => 'create',
            'timestamp' => now(),
        ];

        DB::beginTransaction();

        try {

            $deposit = $this->depositRepository->create($inputs);

            DB::commit();

            Transaction::orderQueue()->removeFromQueueUserWise($inputs['user_id']);

            event(new DepositReceived($deposit));

            return $deposit;

        } catch (Exception $e) {

            DB::rollBack();

            Transaction::orderQueue()->removeFromQueueUserWise($inputs['user_id']);

            throw new OrderRequestFailedException($inputs['order_data']['order_type']->value, 0, $e);
        }
    }

    /**
     * @throws BusinessException
     * @throws RequestOrderExistsException
     * @throws \Exception
     */
    public function accept(BaseModel $deposit, array $inputs = []): BaseModel
    {
        if (Transaction::orderQueue()->addToQueueOrderWise($deposit->getKey()) == 0) {
            throw new RequestOrderExistsException;
        }

        $depositor = $deposit->user;

        $depositedAccount = Transaction::userAccount()->findWhere(['user_id' => $depositor->getKey(), 'country_id' => $deposit->destination_country_id]);

        $depositArray = $deposit->toArray();
        $depositArray['status'] = DepositStatus::Accepted->value;
        $depositArray['order_data']['vendor_data']['payment_info'] = $inputs['vendor_data'] ?? [];
        $depositArray['order_data']['accepted_by'] = $inputs['approver']?->name ?? 'System';
        $depositArray['order_data']['accepted_by_mobile_number'] = $inputs['approver']?->mobile ?? 'N/A';
        $depositArray['order_data']['accepted_at'] = now();
        $depositArray['order_data']['accepted_number'] = entry_number($depositArray['order_data']['purchase_number'], $deposit->sourceCountry->iso3, OrderStatusConfig::Accepted->value);
        $depositArray['order_number'] = entry_number($depositArray['order_data']['purchase_number'], $deposit->sourceCountry->iso3, OrderStatusConfig::Accepted->value);
        $serviceStatData = Business::serviceStat()->serviceStateData([
            'role_id' => $depositArray['order_data']['role_id'],
            'reload' => $depositArray['order_data']['is_reload'],
            'reverse' => $depositArray['order_data']['is_reverse'],
            'source_country_id' => $depositArray['source_country_id'],
            'destination_country_id' => $depositArray['destination_country_id'],
            'amount' => $depositArray['amount'],
            'service_id' => $depositArray['service_id'],
        ]);
        $depositArray['order_data']['service_stat_data'] = $serviceStatData;
        $depositArray['order_data']['user_name'] = $depositor->name ?? 'N/A';
        $depositArray['order_data']['previous_amount'] = $depositedAccount->user_account_data['available_amount'];
        $depositArray['order_data']['current_amount'] = ($depositArray['order_data']['previous_amount'] + $serviceStatData['total_amount']);

        //Collect Current Balance as Previous Balance
        DB::beginTransaction();

        try {

            $userBalanceData['previous_amount'] = Transaction::orderDetail([
                'get_order_detail_amount_sum' => true,
                'user_id' => $deposit->user_id,
                'order_detail_currency' => $deposit->currency,
            ]);

            $this->debitTransaction($deposit, $depositArray);

            $userBalanceData['current_amount'] = Transaction::orderDetail([
                'get_order_detail_amount_sum' => true,
                'user_id' => $deposit->user_id,
                'order_detail_currency' => $deposit->currency,
            ]);

            $userBalanceData['deposit_amount'] = Transaction::orderDetail([
                'get_order_detail_amount_sum' => true,
                'user_id' => $deposit->user_id,
                'order_id' => $deposit->getKey(),
                'order_detail_currency' => $deposit->currency,
            ]);

            $depositedAccountData = $depositedAccount->user_account_data;
            $depositedAccountData['deposit_amount'] = (float) $depositedAccountData['deposit_amount'] + (float) $userBalanceData['deposit_amount'];
            $depositedAccountData['available_amount'] = (float) $userBalanceData['current_amount'];

            $service = Business::service()->find($depositArray['service_id']);
            $message = isset($inputs['approver'])
                ? ucwords(strtolower($service->service_name))." deposit manually accepted by ({$depositArray['order_data']['accepted_by']})."
                : ucwords(strtolower($service->service_name)).' deposit automatically accepted by system.';

            $depositArray['timeline'][] = [
                'message' => $message,
                'flag' => 'success',
                'timestamp' => now(),
            ];

            if (! Transaction::userAccount()->update($depositedAccount->getKey(), ['user_account_data' => $depositedAccountData])
                || ! $this->depositRepository->update($deposit->getKey(), $depositArray)) {
                throw new UpdateOperationException(__('reload::messages.status_change_failed', ['current_status' => $deposit->status->label(), 'target_status' => DepositStatus::Accepted->label()]));
            }

            DB::commit();

            Transaction::orderQueue()->removeFromQueueOrderWise($deposit->getKey());

            $deposit->refresh();

            event(new DepositAccepted($deposit));

            return $deposit;

        } catch (\Exception $e) {
            DB::rollBack();
            Transaction::orderQueue()->removeFromQueueOrderWise($deposit->getKey());
            throw $e;
        }
    }

    private function debitTransaction(BaseModel $deposit, array &$depositArray): void
    {
        $serviceStatData = $depositArray['order_data']['service_stat_data'];

        //For Balance
        $master_user_name = $depositArray['order_data']['master_user_name'];
        $user_name = $depositArray['order_data']['user_name'];
        $deposit->order_detail_cause_name = 'cash_deposit';
        $deposit->order_detail_number = $depositArray['order_data']['accepted_number'];
        $deposit->order_detail_response_id = $depositArray['order_data']['purchase_number'];
        $deposit->notes = 'Point purchases by '.$master_user_name;
        $timeline[] = ['message' => '(System) Step 1: Balance '.\currency($deposit->converted_amount, $deposit->converted_currency).' purchases by system user ('.$master_user_name.').', 'flag' => 'info', 'timestamp' => now()];
        $orderDetailStore = Transaction::orderDetail()->create(Transaction::orderDetail()->orderDetailsDataArrange($deposit));
        $orderDetailStore->order_detail_parent_id = $deposit->order_detail_parent_id = $orderDetailStore->getKey();
        $orderDetailStore->save();

        $orderDetailStore->refresh();
        $amount = $deposit->amount;
        $converted_amount = $deposit->converted_amount;
        $orderDetailStoreForMaster = $orderDetailStore->replicate();
        $orderDetailStoreForMaster->user_id = $deposit->sender_receiver_id;
        $orderDetailStoreForMaster->sender_receiver_id = $deposit->user_id;
        $orderDetailStoreForMaster->order_detail_amount = -$amount;
        $orderDetailStoreForMaster->converted_amount = -$converted_amount;
        $orderDetailStoreForMaster->step = 2;
        $orderDetailStoreForMaster->notes = 'Point Sold to '.$user_name;
        $orderDetailStoreForMaster->save();
        $timeline[] = ['message' => '(System) Step 2: Balance '.\currency($deposit->converted_amount, $deposit->converted_currency).' sold to depositor ('.$user_name.').', 'flag' => 'info', 'timestamp' => now()];

        //For Charge
        $deposit->amount = -calculate_flat_percent($amount, $serviceStatData['charge']);
        $deposit->converted_amount = -calculate_flat_percent($converted_amount, $serviceStatData['charge']);
        $deposit->order_detail_cause_name = 'charge';
        $deposit->order_detail_parent_id = $orderDetailStore->getKey();
        $deposit->notes = 'Deposit Charge Sending to '.$master_user_name;
        $deposit->step = 3;
        $timeline[] = ['message' => '(System) Step 3: Deposit Charge '.\currency($serviceStatData['charge_amount'], $deposit->converted_currency).' sent to system user ('.$master_user_name.').', 'flag' => 'info', 'timestamp' => now()];
        $deposit->order_detail_parent_id = $orderDetailStore->getKey();
        $orderDetailStoreForCharge = Transaction::orderDetail()->create(Transaction::orderDetail()->orderDetailsDataArrange($deposit));
        $orderDetailStoreForChargeForMaster = $orderDetailStoreForCharge->replicate();
        $orderDetailStoreForChargeForMaster->user_id = $deposit->sender_receiver_id;
        $orderDetailStoreForChargeForMaster->sender_receiver_id = $deposit->user_id;
        $orderDetailStoreForChargeForMaster->order_detail_amount = calculate_flat_percent($amount, $serviceStatData['charge']);
        $orderDetailStoreForChargeForMaster->converted_amount = calculate_flat_percent($converted_amount, $serviceStatData['charge']);
        $orderDetailStoreForChargeForMaster->order_detail_cause_name = 'charge';
        $orderDetailStoreForChargeForMaster->notes = 'Deposit Charge Receiving from '.$user_name;
        $timeline[] = ['message' => '(System) Step 4: Deposit Charge '.\currency($serviceStatData['charge_amount'], $deposit->converted_currency).' received from depositor ('.$user_name.').', 'flag' => 'info', 'timestamp' => now()];
        $orderDetailStoreForChargeForMaster->step = 4;
        $orderDetailStoreForChargeForMaster->save();

        //For Discount
        if (calculate_flat_percent($amount, $serviceStatData['discount']) > 0) {
            $deposit->amount = calculate_flat_percent($amount, $serviceStatData['discount']);
            $deposit->converted_amount = calculate_flat_percent($converted_amount, $serviceStatData['discount']);
            $deposit->order_detail_cause_name = 'discount';
            $deposit->notes = 'Deposit Discount form '.$master_user_name;
            $timeline[] = ['message' => '(System) Step 5: Deposit Discount '.\currency($serviceStatData['discount_amount'], $deposit->converted_currency).' received from system user ('.$master_user_name.').', 'flag' => 'info', 'timestamp' => now()];
            $deposit->step = 5;
            //$data->order_detail_parent_id = $orderDetailStore->getKey();
            //$updateData['order_data']['previous_amount'] = 0;
            $orderDetailStoreForDiscount = Transaction::orderDetail()->create(Transaction::orderDetail()->orderDetailsDataArrange($deposit));
            $orderDetailStoreForDiscountForMaster = $orderDetailStoreForCharge->replicate();
            $orderDetailStoreForDiscountForMaster->user_id = $deposit->sender_receiver_id;
            $orderDetailStoreForDiscountForMaster->sender_receiver_id = $deposit->user_id;
            $orderDetailStoreForDiscountForMaster->order_detail_amount = -calculate_flat_percent($amount, $serviceStatData['discount']);
            $orderDetailStoreForDiscountForMaster->converted_amount = -calculate_flat_percent($converted_amount, $serviceStatData['discount']);
            $orderDetailStoreForDiscountForMaster->order_detail_cause_name = 'discount';
            $orderDetailStoreForDiscountForMaster->notes = 'Deposit Discount to '.$user_name;
            $timeline[] = ['message' => '(System) Step 6: Deposit Discount '.\currency($serviceStatData['discount_amount'], $deposit->converted_currency).' sent to depositor ('.$user_name.').', 'flag' => 'info', 'timestamp' => now()];

            $orderDetailStoreForDiscountForMaster->step = 6;
            $orderDetailStoreForDiscountForMaster->save();
        }

        array_push($depositArray['timeline'], ...$timeline);
    }

    public function cancel($data, array $inputs = []): array
    {
        $userAccountData = [
            'previous_amount' => null,
            'current_amount' => null,
            'deposit_amount' => null,
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

        $amount = $data->amount;
        $converted_amount = $data->converted_amount;
        $data->amount = -$amount;
        $data->converted_amount = -$converted_amount;
        $data->order_detail_cause_name = 'cash_deposit';
        $data->order_detail_number = $data->order_data['accepted_number'];
        $data->order_detail_response_id = $data->order_data['purchase_number'];
        $data->notes = 'Point Refund form '.$master_user_name;
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
        $orderDetailStoreForMaster->notes = 'Point Refund to'.$user_name;
        $orderDetailStoreForMaster->save();

        //For Charge
        $data->amount = -calculate_flat_percent($amount, $serviceStatData['charge']);
        $data->converted_amount = -calculate_flat_percent($converted_amount, $serviceStatData['charge']);
        $data->order_detail_cause_name = 'charge';
        $data->order_detail_parent_id = $orderDetailStore->getKey();
        $data->notes = 'Deposit Charge Send to '.$master_user_name;
        $data->step = 3;
        $data->order_detail_parent_id = $orderDetailStore->getKey();
        $orderDetailStoreForCharge = Transaction::orderDetail()->create(Transaction::orderDetail()->orderDetailsDataArrange($data));
        $orderDetailStoreForChargeForMaster = $orderDetailStoreForCharge->replicate();
        $orderDetailStoreForChargeForMaster->user_id = $data->sender_receiver_id;
        $orderDetailStoreForChargeForMaster->sender_receiver_id = $data->user_id;
        $orderDetailStoreForChargeForMaster->order_detail_amount = -calculate_flat_percent($amount, $serviceStatData['charge']);
        $orderDetailStoreForChargeForMaster->converted_amount = -calculate_flat_percent($converted_amount, $serviceStatData['charge']);
        $orderDetailStoreForChargeForMaster->order_detail_cause_name = 'charge';
        $orderDetailStoreForChargeForMaster->notes = 'Deposit Charge Receive from '.$user_name;
        $orderDetailStoreForChargeForMaster->step = 4;
        $orderDetailStoreForChargeForMaster->save();

        $data->amount = -calculate_flat_percent($amount, $serviceStatData['discount']);
        $data->converted_amount = -calculate_flat_percent($converted_amount, $serviceStatData['discount']);
        $data->order_detail_cause_name = 'discount';
        $data->notes = 'Deposit Discount form '.$master_user_name;
        $data->step = 5;
        //$data->order_detail_parent_id = $orderDetailStore->getKey();
        $updateData['order_data']['previous_amount'] = 0;

        $orderDetailStoreForDiscount = Transaction::orderDetail()->create(Transaction::orderDetail()->orderDetailsDataArrange($data));
        $orderDetailStoreForDiscountForMaster = $orderDetailStoreForCharge->replicate();
        $orderDetailStoreForDiscountForMaster->user_id = $data->sender_receiver_id;
        $orderDetailStoreForDiscountForMaster->sender_receiver_id = $data->user_id;
        $orderDetailStoreForDiscountForMaster->order_detail_amount = calculate_flat_percent($amount, $serviceStatData['discount']);
        $orderDetailStoreForDiscountForMaster->converted_amount = calculate_flat_percent($converted_amount, $serviceStatData['discount']);
        $orderDetailStoreForDiscountForMaster->order_detail_cause_name = 'discount';
        $orderDetailStoreForDiscountForMaster->notes = 'Deposit Discount to '.$user_name;
        $orderDetailStoreForDiscountForMaster->step = 6;
        $orderDetailStoreForDiscountForMaster->save();

        $userAccountData['current_amount'] = Transaction::orderDetail()->list([
            'get_order_detail_amount_sum' => true,
            'user_id' => $data->user_id,
            'converted_currency' => $data->converted_currency,
        ]);

        $userAccountData['deposit_amount'] = Transaction::orderDetail()->list([
            'get_order_detail_amount_sum' => true,
            'user_id' => $data->user_id,
            'order_id' => $data->getKey(),
            'converted_currency' => $data->converted_currency,
        ]);

        //'Point Transfer Commission Send to ' . $masterUser->name;
        //'Point Transfer Commission Receive from ' . $receiver->name;
        return $userAccountData;

    }

    /**
     * @throws RequestOrderExistsException
     * @throws Exception
     */
    public function reject(BaseModel $deposit, array $inputs = []): ?BaseModel
    {
        if (Transaction::orderQueue()->addToQueueOrderWise($deposit->getKey()) == 0) {
            throw new RequestOrderExistsException;
        }

        $depositArray = $deposit->toArray();
        $depositArray['status'] = DepositStatus::Rejected->value;
        $depositArray['order_data']['rejected_by'] = $inputs['rejector']->name ?? 'System';
        $depositArray['order_data']['rejected_at'] = now();
        $depositArray['order_data']['vendor_data']['payment_info'] = $inputs['vendor_data'] ?? [];
        $depositArray['order_data']['rejected_number'] = entry_number($depositArray['order_data']['purchase_number'], $deposit->sourceCountry->iso3, OrderStatusConfig::Rejected->value);
        $depositArray['order_number'] = entry_number($depositArray['order_data']['purchase_number'], $deposit->sourceCountry->iso3, OrderStatusConfig::Rejected->value);
        $depositArray['order_data']['rejected_by_mobile_number'] = $inputs['rejector']->mobile ?? 'N/A';
        $depositArray['order_data']['previous_amount'] = $depositAccount->user_account_data['available_amount'] ?? 0;
        $depositArray['order_data']['current_amount'] = $depositArray['order_data']['previous_amount'] - $depositArray['amount'];

        $service = Business::service()->find($depositArray['service_id']);

        $message = isset($inputs['rejector'])
            ? ucwords(strtolower($service->service_name))." deposit manually rejected by ({$depositArray['order_data']['rejected_by']})."
            : ucwords(strtolower($service->service_name)).' deposit automatically rejected by system.';

        $depositArray['timeline'][] = [
            'message' => $message,
            'flag' => 'error',
            'timestamp' => now(),
        ];

        DB::beginTransaction();

        try {

            if (! Reload::deposit()->update($deposit->getKey(), $depositArray)) {
                throw new Exception(__('reload::messages.status_change_failed', [
                    'current_status' => $deposit->status->label(),
                    'target_status' => DepositStatus::Rejected->label(),
                ]));
            }

            DB::commit();
            Transaction::orderQueue()->removeFromQueueOrderWise($deposit->getKey());

            $deposit->refresh();

            event(new DepositRejected($deposit));

            return $deposit;
        } catch (Exception $exception) {
            DB::rollBack();
            Transaction::orderQueue()->removeFromQueueOrderWise($deposit->getKey());
            throw $exception;
        }
    }
}
