<?php

namespace Fintech\Reload\Services;

use Fintech\Reload\Interfaces\WalletToBankRepository;
use Fintech\Transaction\Facades\Transaction;

/**
 * Class WalletToBankService
 */
class WalletToBankService
{
    /**
     * WalletToBankService constructor.
     */
    public function __construct(WalletToBankRepository $walletToBankRepository)
    {
        $this->walletToBankRepository = $walletToBankRepository;
    }

    public function find($id, $onlyTrashed = false)
    {
        return $this->walletToBankRepository->find($id, $onlyTrashed);
    }

    public function update($id, array $inputs = [])
    {
        return $this->walletToBankRepository->update($id, $inputs);
    }

    public function destroy($id)
    {
        return $this->walletToBankRepository->delete($id);
    }

    public function restore($id)
    {
        return $this->walletToBankRepository->restore($id);
    }

    public function export(array $filters)
    {
        return $this->walletToBankRepository->list($filters);
    }

    /**
     * @return mixed
     */
    public function list(array $filters = [])
    {
        return $this->walletToBankRepository->list($filters);

    }

    public function import(array $filters)
    {
        return $this->walletToBankRepository->create($filters);
    }

    public function create(array $inputs = [])
    {
        return $this->walletToBankRepository->create($inputs);
    }

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
        $data->notes = 'Local Bank Transfer send to ' . $data->amount . ' ' . $data->currency . ' to ' . $data->converted_amount . ' ' . $data->converted_currency . ' Payment Send to ' . $master_user_name;
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
        $orderDetailStoreForMaster->notes = 'Local Bank Transfer receive from ' . $data->amount . ' ' . $data->currency . ' to ' . $data->converted_amount . ' ' . $data->converted_currency . ' Payment Receive From' . $user_name;
        $orderDetailStoreForMaster->save();

        //For Charge
        $data->amount = calculate_flat_percent($amount, $serviceStatData['charge']);
        $data->converted_amount = calculate_flat_percent($converted_amount, $serviceStatData['charge']);
        $data->order_detail_cause_name = 'charge';
        $data->order_detail_parent_id = $orderDetailStore->getKey();
        $data->notes = 'Local Bank Transfer send to ' . $data->amount . ' ' . $data->currency . ' to ' . $data->converted_amount . ' ' . $data->converted_currency . ' Charge Send to ' . $master_user_name;
        $data->step = 3;
        $data->order_detail_parent_id = $orderDetailStore->getKey();
        $orderDetailStoreForCharge = Transaction::orderDetail()->create(Transaction::orderDetail()->orderDetailsDataArrange($data));
        $orderDetailStoreForChargeForMaster = $orderDetailStoreForCharge->replicate();
        $orderDetailStoreForChargeForMaster->user_id = $data->sender_receiver_id;
        $orderDetailStoreForChargeForMaster->sender_receiver_id = $data->user_id;
        $orderDetailStoreForChargeForMaster->order_detail_amount = -calculate_flat_percent($amount, $serviceStatData['charge']);
        $orderDetailStoreForChargeForMaster->converted_amount = -calculate_flat_percent($converted_amount, $serviceStatData['charge']);
        $orderDetailStoreForChargeForMaster->order_detail_cause_name = 'charge';
        $orderDetailStoreForChargeForMaster->notes = 'Local Bank Transfer from ' . $data->amount . ' ' . $data->currency . ' to ' . $data->converted_amount . ' ' . $data->converted_currency . ' Charge Receive from ' . $user_name;
        $orderDetailStoreForChargeForMaster->step = 4;
        $orderDetailStoreForChargeForMaster->save();

        //For Discount
        $data->amount = -calculate_flat_percent($amount, $serviceStatData['discount']);
        $data->converted_amount = -calculate_flat_percent($converted_amount, $serviceStatData['discount']);
        $data->order_detail_cause_name = 'discount';
        $data->notes = 'Local Bank Transfer from ' . $data->amount . ' ' . $data->currency . ' to ' . $data->converted_amount . ' ' . $data->converted_currency . ' Discount form ' . $master_user_name;
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
        $orderDetailStoreForDiscountForMaster->notes = 'Local Bank Transfer send to ' . $data->amount . ' ' . $data->currency . ' to ' . $data->converted_amount . ' ' . $data->converted_currency . ' Discount to ' . $user_name;
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

        $data->order_detail_cause_name = 'cash_withdraw';
        //$data->order_detail_number = $data->order_data['accepted_number'];
        $data->order_detail_response_id = $data->order_data['purchase_number'];
        $data->notes = 'Local Bank Transfer send to ' . $data->amount . ' ' . $data->currency . ' to ' . $data->converted_amount . ' ' . $data->converted_currency . ' Refund From ' . $master_user_name;
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
        $orderDetailStoreForMaster->notes = 'Local Bank Transfer receive from ' . $data->amount . ' ' . $data->currency . ' to ' . $data->converted_amount . ' ' . $data->converted_currency . ' Send to ' . $user_name;
        $orderDetailStoreForMaster->save();

        //For Charge
        /*$data->amount = -calculate_flat_percent($amount, $serviceStatData['charge']);
        $data->converted_amount = -calculate_flat_percent($converted_amount, $serviceStatData['charge']);
        $data->order_detail_cause_name = 'charge';
        $data->order_detail_parent_id = $orderDetailStore->getKey();
        $data->notes = 'Local Bank Transfer send to '.$data->amount.' '.$data->currency.' to '.$data->converted_amount.' '.$data->converted_currency.' Charge Receive from '.$master_user_name;
        $data->step = 3;
        $data->order_detail_parent_id = $orderDetailStore->getKey();
        $orderDetailStoreForCharge = Transaction::orderDetail()->create(Transaction::orderDetail()->orderDetailsDataArrange($data));
        $orderDetailStoreForChargeForMaster = $orderDetailStoreForCharge->replicate();
        $orderDetailStoreForChargeForMaster->user_id = $data->sender_receiver_id;
        $orderDetailStoreForChargeForMaster->sender_receiver_id = $data->user_id;
        $orderDetailStoreForChargeForMaster->order_detail_amount = calculate_flat_percent($amount, $serviceStatData['charge']);
        $orderDetailStoreForChargeForMaster->converted_amount = calculate_flat_percent($converted_amount, $serviceStatData['charge']);
        $orderDetailStoreForChargeForMaster->order_detail_cause_name = 'charge';
        $orderDetailStoreForChargeForMaster->notes = 'Local Bank Transfer receive from '.$data->amount.' '.$data->currency.' to '.$data->converted_amount.' '.$data->converted_currency.' Charge Send to '.$user_name;
        $orderDetailStoreForChargeForMaster->step = 4;
        $orderDetailStoreForChargeForMaster->save();*/

        //For Discount
        $data->amount = calculate_flat_percent($amount, $serviceStatData['discount']);
        $data->converted_amount = calculate_flat_percent($converted_amount, $serviceStatData['discount']);
        $data->order_detail_cause_name = 'discount';
        $data->notes = 'Local Bank Transfer receive from ' . $data->amount . ' ' . $data->currency . ' to ' . $data->converted_amount . ' ' . $data->converted_currency . ' Discount form ' . $master_user_name;
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
        $orderDetailStoreForDiscountForMaster->notes = 'Local Bank Transfer send to ' . $data->amount . ' ' . $data->currency . ' to ' . $data->converted_amount . ' ' . $data->converted_currency . ' Discount to ' . $user_name;
        $orderDetailStoreForDiscountForMaster->step = 6;
        $orderDetailStoreForDiscountForMaster->save();

        //For commission
        $data->amount = -calculate_flat_percent($amount, $serviceStatData['commission']);
        $data->converted_amount = -calculate_flat_percent($converted_amount, $serviceStatData['commission']);
        $data->order_detail_cause_name = 'commission';
        $data->order_detail_parent_id = $orderDetailStore->getKey();
        $data->notes = 'Local Bank Transfer Deposit Commission Receive from ' . $master_user_name;
        $data->step = 3;
        $data->order_detail_parent_id = $orderDetailStore->getKey();
        $orderDetailStoreForCommission = Transaction::orderDetail()->create(Transaction::orderDetail()->orderDetailsDataArrange($data));
        $orderDetailStoreForCommissionForMaster = $orderDetailStoreForCommission->replicate();
        $orderDetailStoreForCommissionForMaster->user_id = $data->sender_receiver_id;
        $orderDetailStoreForCommissionForMaster->sender_receiver_id = $data->user_id;
        $orderDetailStoreForCommissionForMaster->order_detail_amount = calculate_flat_percent($amount, $serviceStatData['commission']);
        $orderDetailStoreForCommissionForMaster->converted_amount = calculate_flat_percent($converted_amount, $serviceStatData['commission']);
        $orderDetailStoreForCommissionForMaster->order_detail_cause_name = 'commission';
        $orderDetailStoreForCommissionForMaster->notes = 'Local Bank Transfer Deposit Commission Send to ' . $user_name;
        $orderDetailStoreForCommissionForMaster->step = 4;
        $orderDetailStoreForCommissionForMaster->save();

        //'Point Transfer Commission Send to ' . $masterUser->name;
        //'Point Transfer Commission Receive from ' . $receiver->name;

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

        return $userAccountData;

    }
}
