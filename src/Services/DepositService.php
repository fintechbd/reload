<?php

namespace Fintech\Reload\Services;

use Fintech\Auth\Facades\Auth;
use Fintech\Core\Enums\Auth\RiskProfile;
use Fintech\Core\Enums\Auth\SystemRole;
use Fintech\Core\Enums\Reload\DepositStatus;
use Fintech\Core\Enums\Transaction\OrderStatusConfig;
use Fintech\Core\Exceptions\StoreOperationException;
use Fintech\Core\Exceptions\Transaction\CurrencyUnavailableException;
use Fintech\Core\Exceptions\Transaction\MasterCurrencyUnavailableException;
use Fintech\Core\Exceptions\Transaction\RequestAmountExistsException;
use Fintech\Reload\Events\DepositReceived;
use Fintech\Reload\Facades\Reload;
use Fintech\Reload\Interfaces\DepositRepository;
use Fintech\Transaction\Facades\Transaction;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Class DepositService
 */
class DepositService extends \Fintech\Core\Abstracts\Service
{
    /**
     * DepositService constructor.
     */
    public function __construct(private readonly DepositRepository $depositRepository)
    {
    }

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
     * @throws StoreOperationException
     * @throws MasterCurrencyUnavailableException
     * @throws RequestAmountExistsException
     */
    public function create(array $inputs = [])
    {
        $depositUser = Auth::user()->find($inputs['user_id']);

        if (!$depositUser) {
            throw (new ModelNotFoundException)->setModel(config('fintech.auth.auth_model'), $inputs['user_id']);
        }

        $inputs['source_country_id'] = $inputs['source_country_id'] ?? $depositUser->profile?->present_country_id;

        if (Transaction::orderQueue()->addToQueueUserWise($depositUser->getKey()) > 0) {

            $depositAccount = Transaction::userAccount()->list([
                'user_id' => $depositUser->getKey(),
                'country_id' => $inputs['source_country_id']
            ])->first();

            if (!$depositAccount) {
                throw new CurrencyUnavailableException($inputs['source_country_id']);
            }

            $masterUser = Auth::user()->list([
                'role_name' => SystemRole::MasterUser->value,
                'country_id' => $inputs['source_country_id'],
            ])->first();

            if (!$masterUser) {
                throw new MasterCurrencyUnavailableException($inputs['source_country_id']);
            }

            $inputs['transaction_form_id'] = Transaction::transactionForm()->list(['code' => 'point_reload'])->first()->getKey();

            if (Transaction::order()->transactionDelayCheck($inputs)['countValue'] > 0) {
                throw new RequestAmountExistsException();
            }

            $inputs['sender_receiver_id'] = $masterUser->getKey();
            $inputs['is_refunded'] = false;
            $inputs['status'] = DepositStatus::Processing->value;
            $inputs['risk'] = RiskProfile::Low->value;
            $inputs['order_data']['created_by'] = $depositUser->name;
            $inputs['order_data']['created_by_mobile_number'] = $depositUser->mobile;
            $inputs['order_data']['created_by_email'] = $depositUser->email;
            $inputs['order_data']['created_at'] = now();
            $inputs['order_data']['current_amount'] = ($depositAccount->user_account_data['available_amount'] ?? 0) + $inputs['amount'];
            $inputs['order_data']['previous_amount'] = $depositAccount->user_account_data['available_amount'] ?? 0;
            $inputs['converted_amount'] = $inputs['amount'];
            $inputs['converted_currency'] = $inputs['currency'];
            $inputs['order_data']['master_user_name'] = $masterUser['name'];

            $deposit = $this->depositRepository->create($inputs);

            if (!$deposit) {
                throw (new StoreOperationException)->setModel(config('fintech.reload.deposit_model'));
            }

            $order_data = $inputs['order_data'];

            $order_data['purchase_number'] = entry_number($deposit->getKey(), $deposit->sourceCountry->iso3, OrderStatusConfig::Purchased->value);

            $deposit = $this->depositRepository->update($deposit->getKey(), ['order_data' => $order_data, 'order_number' => $order_data['purchase_number']]);

            Transaction::orderQueue()->removeFromQueueUserWise($inputs['user_id']);

            event(new DepositReceived($deposit));

            return $deposit;

        } else {
            throw new Exception('Your another order is in process...!');
        }

//        } catch (Exception $exception) {
//            Transaction::orderQueue()->removeFromQueueUserWise($user_id);
//
//            return response()->failed($exception);
//        }
    }

    public function accept($deposit): array
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
        $deposit->order_detail_number = $deposit->order_data['accepted_number'];
        $deposit->order_detail_response_id = $deposit->order_data['purchase_number'];
        $deposit->notes = 'Point purchases by ' . $master_user_name;
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
        $orderDetailStoreForMaster->notes = 'Point Sold to ' . $user_name;
        $orderDetailStoreForMaster->save();

        //For Charge
        $deposit->amount = calculate_flat_percent($amount, $serviceStatData['charge']);
        $deposit->converted_amount = calculate_flat_percent($converted_amount, $serviceStatData['charge']);
        $deposit->order_detail_cause_name = 'charge';
        $deposit->order_detail_parent_id = $orderDetailStore->getKey();
        $deposit->notes = 'Deposit Charge Sending to ' . $master_user_name;
        $deposit->step = 3;
        $deposit->order_detail_parent_id = $orderDetailStore->getKey();
        $orderDetailStoreForCharge = Transaction::orderDetail()->create(Transaction::orderDetail()->orderDetailsDataArrange($deposit));
        $orderDetailStoreForChargeForMaster = $orderDetailStoreForCharge->replicate();
        $orderDetailStoreForChargeForMaster->user_id = $deposit->sender_receiver_id;
        $orderDetailStoreForChargeForMaster->sender_receiver_id = $deposit->user_id;
        $orderDetailStoreForChargeForMaster->order_detail_amount = -calculate_flat_percent($amount, $serviceStatData['charge']);
        $orderDetailStoreForChargeForMaster->converted_amount = -calculate_flat_percent($converted_amount, $serviceStatData['charge']);
        $orderDetailStoreForChargeForMaster->order_detail_cause_name = 'charge';
        $orderDetailStoreForChargeForMaster->notes = 'Deposit Charge Receiving from ' . $user_name;
        $orderDetailStoreForChargeForMaster->step = 4;
        $orderDetailStoreForChargeForMaster->save();

        $deposit->amount = -calculate_flat_percent($amount, $serviceStatData['discount']);
        $deposit->converted_amount = -calculate_flat_percent($converted_amount, $serviceStatData['discount']);
        $deposit->order_detail_cause_name = 'discount';
        $deposit->notes = 'Deposit Discount form ' . $master_user_name;
        $deposit->step = 5;
        //$data->order_detail_parent_id = $orderDetailStore->getKey();
        //$updateData['order_data']['previous_amount'] = 0;
        $orderDetailStoreForDiscount = Transaction::orderDetail()->create(Transaction::orderDetail()->orderDetailsDataArrange($deposit));
        $orderDetailStoreForDiscountForMaster = $orderDetailStoreForCharge->replicate();
        $orderDetailStoreForDiscountForMaster->user_id = $deposit->sender_receiver_id;
        $orderDetailStoreForDiscountForMaster->sender_receiver_id = $deposit->user_id;
        $orderDetailStoreForDiscountForMaster->order_detail_amount = calculate_flat_percent($amount, $serviceStatData['discount']);
        $orderDetailStoreForDiscountForMaster->converted_amount = calculate_flat_percent($converted_amount, $serviceStatData['discount']);
        $orderDetailStoreForDiscountForMaster->order_detail_cause_name = 'discount';
        $orderDetailStoreForDiscountForMaster->notes = 'Deposit Discount to ' . $user_name;
        $orderDetailStoreForDiscountForMaster->step = 6;
        $orderDetailStoreForDiscountForMaster->save();

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

    public function cancel($data): array
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
        $data->notes = 'Point Refund form ' . $master_user_name;
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
        $orderDetailStoreForMaster->notes = 'Point Refund to' . $user_name;
        $orderDetailStoreForMaster->save();

        //For Charge
        $data->amount = -calculate_flat_percent($amount, $serviceStatData['charge']);
        $data->converted_amount = -calculate_flat_percent($converted_amount, $serviceStatData['charge']);
        $data->order_detail_cause_name = 'charge';
        $data->order_detail_parent_id = $orderDetailStore->getKey();
        $data->notes = 'Deposit Charge Send to ' . $master_user_name;
        $data->step = 3;
        $data->order_detail_parent_id = $orderDetailStore->getKey();
        $orderDetailStoreForCharge = Transaction::orderDetail()->create(Transaction::orderDetail()->orderDetailsDataArrange($data));
        $orderDetailStoreForChargeForMaster = $orderDetailStoreForCharge->replicate();
        $orderDetailStoreForChargeForMaster->user_id = $data->sender_receiver_id;
        $orderDetailStoreForChargeForMaster->sender_receiver_id = $data->user_id;
        $orderDetailStoreForChargeForMaster->order_detail_amount = -calculate_flat_percent($amount, $serviceStatData['charge']);
        $orderDetailStoreForChargeForMaster->converted_amount = -calculate_flat_percent($converted_amount, $serviceStatData['charge']);
        $orderDetailStoreForChargeForMaster->order_detail_cause_name = 'charge';
        $orderDetailStoreForChargeForMaster->notes = 'Deposit Charge Receive from ' . $user_name;
        $orderDetailStoreForChargeForMaster->step = 4;
        $orderDetailStoreForChargeForMaster->save();

        $data->amount = -calculate_flat_percent($amount, $serviceStatData['discount']);
        $data->converted_amount = -calculate_flat_percent($converted_amount, $serviceStatData['discount']);
        $data->order_detail_cause_name = 'discount';
        $data->notes = 'Deposit Discount form ' . $master_user_name;
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
        $orderDetailStoreForDiscountForMaster->notes = 'Deposit Discount to ' . $user_name;
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
}
