<?php

namespace Fintech\Reload\Services;

use Exception;
use Fintech\Auth\Facades\Auth;
use Fintech\Business\Facades\Business;
use Fintech\Core\Abstracts\BaseModel;
use Fintech\Core\Enums\Auth\RiskProfile;
use Fintech\Core\Enums\Auth\SystemRole;
use Fintech\Core\Enums\Reload\DepositStatus;
use Fintech\Core\Enums\Transaction\OrderStatus;
use Fintech\Core\Enums\Transaction\OrderType;
use Fintech\Core\Exceptions\StoreOperationException;
use Fintech\Core\Exceptions\Transaction\CurrencyUnavailableException;
use Fintech\Core\Exceptions\Transaction\MasterCurrencyUnavailableException;
use Fintech\Core\Exceptions\Transaction\OrderRequestFailedException;
use Fintech\Core\Exceptions\Transaction\RequestAmountExistsException;
use Fintech\Core\Exceptions\Transaction\RequestOrderExistsException;
use Fintech\MetaData\Facades\MetaData;
use Fintech\Reload\Facades\Reload;
use Fintech\Reload\Interfaces\RequestMoneyRepository;
use Fintech\Transaction\Facades\Transaction;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Class RequestMoneyService
 */
class RequestMoneyService
{
    use \Fintech\Core\Traits\HasFindWhereSearch;

    /**
     * RequestMoneyService constructor.
     */
    public function __construct(RequestMoneyRepository $requestMoneyRepository)
    {
        $this->requestMoneyRepository = $requestMoneyRepository;
    }

    public function find($id, $onlyTrashed = false)
    {
        return $this->requestMoneyRepository->find($id, $onlyTrashed);
    }

    public function update($id, array $inputs = [])
    {
        return $this->requestMoneyRepository->update($id, $inputs);
    }

    public function destroy($id)
    {
        return $this->requestMoneyRepository->delete($id);
    }

    public function restore($id)
    {
        return $this->requestMoneyRepository->restore($id);
    }

    public function export(array $filters)
    {
        return $this->requestMoneyRepository->list($filters);
    }

    /**
     * @return mixed
     */
    public function list(array $filters = [])
    {
        return $this->requestMoneyRepository->list($filters);

    }

    public function import(array $filters)
    {
        return $this->requestMoneyRepository->create($filters);
    }

    public function create(array $inputs = []): BaseModel
    {
        $user_id = $inputs['user_id'] ?? null;
        $orderType = OrderType::RequestMoney;

        $sender = Auth::user()->find($user_id);
        if (! $sender) {
            throw (new ModelNotFoundException)->setModel(config('fintech.auth.auth_model'), $user_id);
        }
        $inputs['source_country_id'] = $inputs['source_country_id'] ?? $sender->profile?->present_country_id;
        $senderAccount = Transaction::userAccount()->findWhere(['user_id' => $sender->getKey(), 'country_id' => $inputs['source_country_id']]);
        if (! $senderAccount) {
            throw new CurrencyUnavailableException($inputs['source_country_id']);
        }

        // Receiver
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

        $inputs['transaction_form_id'] = Transaction::transactionForm()->findWhere(['code' => 'request_money'])->getKey();

        if (Transaction::order()->transactionDelayCheck($inputs)['countValue'] > 0) {
            throw new RequestAmountExistsException;
        }

        $role = $sender->roles?->first() ?? null;
        $inputs['order_data']['role_id'] = $role->id;
        $inputs['order_data']['order_type'] = $orderType;
        $inputs['description'] = "Request Money sent to {$recipient->name} [{$inputs['converted_currency']}]";
        $inputs['notes'] = $inputs['notes'] ?? "Request Money sent to {$recipient->name} [{$inputs['converted_currency']}]";
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
            'message' => 'Request Money entry created successfully',
            'flag' => 'create',
            'timestamp' => now(),
        ];
        $inputs['parent_id'] = null;

        DB::beginTransaction();

        try {
            $senderRequestMoney = $this->requestMoneyRepository->create($inputs);
            DB::commit();
            $senderAccounting = Transaction::accounting($senderRequestMoney, $sender->getKey());
            $senderRequestMoney = $senderAccounting->debitTransaction();
            $senderAccounting->debitBalanceFromUserAccount();
            //            $receiverInputs = $inputs;
            //            unset($inputs);
            //            $receiverInputs['parent_id'] = $senderRequestMoney->getKey();
            //            $receiverInputs['user_id'] = $recipient->getKey();
            //            $receiverInputs['description'] = "Request Money received from {$sender->name} [{$receiverInputs['currency']}]";
            //            $receiverInputs['notes'] = "Request Money received from {$sender->name} [{$receiverInputs['currency']}]";
            //            $receiverInputs['sender_receiver_id'] = $recipientMasterUser->getKey();
            //            $receiverInputs['order_data']['master_user_name'] = $recipientMasterUser->name;
            //            $receiverInputs['order_data']['user_name'] = $recipient->name;
            //            $recipientRequestMoney = $this->requestMoneyRepository->create($receiverInputs);
            //            $recipientAccounting = Transaction::accounting($recipientRequestMoney, $recipient->getKey());
            //            $recipientRequestMoney = $recipientAccounting->creditTransaction();
            //            $recipientAccounting->creditBalanceToUserAccount();
            //            Transaction::orderQueue()->removeFromQueueUserWise($user_id);

            $senderRequestMoney->refresh();
            // @TODO not working on double entry need fix ;-(
            //            event(new WalletToWalletReceived($senderRequestMoney));

            return $senderRequestMoney;

        } catch (Exception $e) {

            DB::rollBack();

            Transaction::orderQueue()->removeFromQueueUserWise($user_id);

            throw new OrderRequestFailedException($orderType->value, 0, $e);
        }
    }

    public function accept(BaseModel $requestMoney, array $inputs = []) {}

    public function reject(BaseModel $requestMoney, array $inputs = []) {}

    public function oldCreate(array $inputs = [])
    {
        $user_id = $inputs['user_id'] ?? null;
        $orderType = OrderType::RequestMoney;

        $sender = Auth::user()->find($user_id);

        if (! $sender) {
            throw (new ModelNotFoundException)->setModel(config('fintech.auth.auth_model'), $user_id);
        }

        $inputs['source_country_id'] = $inputs['source_country_id'] ?? $sender->profile?->present_country_id;

        $senderAccount = Transaction::userAccount()->findWhere(['user_id' => $sender->getKey(), 'country_id' => $inputs['source_country_id']]);

        if (! $senderAccount) {
            throw new CurrencyUnavailableException($inputs['source_country_id']);
        }

        // Receiver
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

        $inputs['transaction_form_id'] = Transaction::transactionForm()->findWhere(['code' => 'request_money'])->getKey();

        if (Transaction::order()->transactionDelayCheck($inputs)['countValue'] > 0) {
            throw new RequestAmountExistsException;
        }

        //            if (Transaction::orderQueue()->addToQueueUserWise(($user_id ?? $sender->getKey())) > 0) {
        //
        //                $senderAccount = Transaction::userAccount()->findWhere(['user_id' => $user_id ?? $sender->getKey(), 'currency' => $request->input('currency', $sender->profile?->presentCountry?->currency)]);
        //
        //                if (! $senderAccount) {
        //                    throw new CurrencyUnavailableException($request->input('source_country_id', $sender->profile?->present_country_id));
        //                }
        //
        //                $masterUser = Auth::user()->findWhere(['role_name' => SystemRole::MasterUser->value, 'country_id' => $request->input('source_country_id', $sender->profile?->present_country_id)]);
        //
        //                if (! $masterUser) {
        //                    throw new Exception('Master User Account not found for '.$request->input('source_country_id', $sender->profile?->country_id).' country');
        //                }
        //
        //                $receiver = Auth::user()->find($inputs['sender_receiver_id']);
        //                $receiverDepositAccount = Transaction::userAccount()->findWhere(['user_id' => $inputs['sender_receiver_id'], 'currency' => $request->input('currency', $receiver->profile?->presentCountry?->currency)]);
        //
        //                if (! $receiverDepositAccount) {
        //                    throw new Exception("Receiver don't have account deposit balance");
        //                }
        //
        //                //set pre defined conditions of deposit
        //                $inputs['transaction_form_id'] = Transaction::transactionForm()->findWhere(['code' => 'request_money'])->getKey();
        //                $inputs['user_id'] = $receiver ?? $receiverDepositAccount->getKey();
        //                $delayCheck = Transaction::order()->transactionDelayCheck($inputs);
        //                if ($delayCheck['countValue'] > 0) {
        //                    throw new Exception('Your Request For This Amount Is Already Submitted. Please Wait For Update');
        //                }
        //
        //                $inputs['user_id'] = $receiver->getKey();
        //                $inputs['order_data']['is_reload'] = true;
        //                $inputs['sender_receiver_id'] = $user_id ?? $sender->getKey(); //$masterUser->getKey();
        //                $inputs['order_data']['sender_receiver_id'] = $user_id ?? $sender->getKey();
        //                $inputs['is_refunded'] = false;
        //                $inputs['status'] = DepositStatus::Processing->value;
        //                $inputs['risk'] = RiskProfile::Low->value;
        //                $inputs['converted_currency'] = $inputs['currency'];
        //                $inputs['notes'] = 'Request Money for Request Money to '.$sender->name;
        //                $inputs['order_data']['created_by'] = $sender->name;
        //                $inputs['order_data']['created_by_mobile_number'] = $sender->mobile;
        //                $inputs['order_data']['created_at'] = now();
        //                $inputs['order_data']['master_user_name'] = $masterUser['name'];
        //                //$inputs['order_data']['operator_short_code'] = $request->input('operator_short_code', null);
        //                $inputs['order_data']['system_notification_variable_success'] = 'request_money_success';
        //                $inputs['order_data']['system_notification_variable_failed'] = 'request_money_failed';
        //                $inputs['order_data']['source_country_id'] = $inputs['source_country_id'];
        //                $inputs['order_data']['destination_country_id'] = $inputs['destination_country_id'];
        //                $inputs['converted_amount'] = $inputs['amount'];
        //                $inputs['converted_currency'] = $inputs['currency'];
        //                $inputs['order_data']['order_type'] = OrderType::RequestMoney;
        //                unset($inputs['pin'], $inputs['password']);
        //                $requestMoney = Reload::requestMoney()->create($inputs);
        //
        //                if (! $requestMoney) {
        //                    throw (new StoreOperationException)->setModel(config('fintech.reload.request_money_model'));
        //                }
        //                $order_data = $requestMoney->order_data;
        //                $order_data['purchase_number'] = entry_number($requestMoney->getKey(), $requestMoney->sourceCountry->iso3, OrderStatus::Successful->value);
        //                $order_data['service_stat_data'] = Business::serviceStat()->serviceStateData($requestMoney);
        //                $order_data['user_name'] = $requestMoney->user->name;
        //                Reload::requestMoney()->update($requestMoney->getKey(), ['order_data' => $order_data, 'order_number' => $order_data['purchase_number']]);
        //                $this->__receiverStore($requestMoney->getKey());
        //                Transaction::orderQueue()->removeFromQueueUserWise($user_id ?? $sender->getKey());
        //                DB::commit();
        //
        //                return response()->created([
        //                    'message' => __('core::messages.resource.created', ['model' => 'Request Money']),
        //                    'id' => $requestMoney->id,
        //                ]);
        //            }
        //
        //        } catch (Exception $exception) {
        //            Transaction::orderQueue()->removeFromQueueUserWise($user_id ?? $sender->getKey());
        //            DB::rollBack();
        //
        //            return response()->failed($exception);
        //        }
        //
        //        return $this->requestMoneyRepository->create($inputs);
    }

    public function debitTransaction($data): array
    {
        $userAccountData = [
            'previous_amount' => null,
            'current_amount' => null,
            'spent_amount' => null,
        ];
        // Collect Current Balance as Previous Balance
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
        $data->notes = 'Request Money send to '.$data->amount.' '.$data->currency.' to '.$data->converted_amount.' '.$data->converted_currency.' Payment Send to '.$master_user_name;
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
        $orderDetailStoreForMaster->notes = 'Request Money receive from '.$data->amount.' '.$data->currency.' to '.$data->converted_amount.' '.$data->converted_currency.' Payment Receive From'.$user_name;
        $orderDetailStoreForMaster->save();

        // For Charge
        $data->amount = calculate_flat_percent($amount, $serviceStatData['charge']);
        $data->converted_amount = calculate_flat_percent($converted_amount, $serviceStatData['charge']);
        $data->order_detail_cause_name = 'charge';
        $data->order_detail_parent_id = $orderDetailStore->getKey();
        $data->notes = 'Request Money send to '.$data->amount.' '.$data->currency.' to '.$data->converted_amount.' '.$data->converted_currency.' Charge Send to '.$master_user_name;
        $data->step = 3;
        $data->order_detail_parent_id = $orderDetailStore->getKey();
        $orderDetailStoreForCharge = Transaction::orderDetail()->create(Transaction::orderDetail()->orderDetailsDataArrange($data));
        $orderDetailStoreForChargeForMaster = $orderDetailStoreForCharge->replicate();
        $orderDetailStoreForChargeForMaster->user_id = $data->sender_receiver_id;
        $orderDetailStoreForChargeForMaster->sender_receiver_id = $data->user_id;
        $orderDetailStoreForChargeForMaster->order_detail_amount = -calculate_flat_percent($amount, $serviceStatData['charge']);
        $orderDetailStoreForChargeForMaster->converted_amount = -calculate_flat_percent($converted_amount, $serviceStatData['charge']);
        $orderDetailStoreForChargeForMaster->order_detail_cause_name = 'charge';
        $orderDetailStoreForChargeForMaster->notes = 'Request Money from '.$data->amount.' '.$data->currency.' to '.$data->converted_amount.' '.$data->converted_currency.' Charge Receive from '.$user_name;
        $orderDetailStoreForChargeForMaster->step = 4;
        $orderDetailStoreForChargeForMaster->save();

        // For Discount
        $data->amount = -calculate_flat_percent($amount, $serviceStatData['discount']);
        $data->converted_amount = -calculate_flat_percent($converted_amount, $serviceStatData['discount']);
        $data->order_detail_cause_name = 'discount';
        $data->notes = 'Request Money from '.$data->amount.' '.$data->currency.' to '.$data->converted_amount.' '.$data->converted_currency.' Discount form '.$master_user_name;
        $data->step = 5;
        // $data->order_detail_parent_id = $orderDetailStore->getKey();
        // $updateData['order_data']['previous_amount'] = 0;
        $orderDetailStoreForDiscount = Transaction::orderDetail()->create(Transaction::orderDetail()->orderDetailsDataArrange($data));
        $orderDetailStoreForDiscountForMaster = $orderDetailStoreForDiscount->replicate();
        $orderDetailStoreForDiscountForMaster->user_id = $data->sender_receiver_id;
        $orderDetailStoreForDiscountForMaster->sender_receiver_id = $data->user_id;
        $orderDetailStoreForDiscountForMaster->order_detail_amount = calculate_flat_percent($amount, $serviceStatData['discount']);
        $orderDetailStoreForDiscountForMaster->converted_amount = calculate_flat_percent($converted_amount, $serviceStatData['discount']);
        $orderDetailStoreForDiscountForMaster->order_detail_cause_name = 'discount';
        $orderDetailStoreForDiscountForMaster->notes = 'Request Money send to '.$data->amount.' '.$data->currency.' to '.$data->converted_amount.' '.$data->converted_currency.' Discount to '.$user_name;
        $orderDetailStoreForDiscountForMaster->step = 6;
        $orderDetailStoreForDiscountForMaster->save();

        // 'Point Transfer Commission Send to ' . $masterUser->name;
        // 'Point Transfer Commission Receive from ' . $receiver->name;

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
        // Collect Current Balance as Previous Balance
        $userAccountData['previous_amount'] = Transaction::orderDetail()->list([
            'get_order_detail_amount_sum' => true,
            'user_id' => $data->user_id,
            'converted_currency' => $data->converted_currency,
        ]);

        $serviceStatData = $data->order_data['service_stat_data'];
        $master_user_name = $data->order_data['master_user_name'];
        $user_name = $data->order_data['user_name'];

        $data->order_detail_cause_name = 'cash_withdraw';
        // $data->order_detail_number = $data->order_data['accepted_number'];
        $data->order_detail_response_id = $data->order_data['purchase_number'];
        $data->notes = 'Request Money send to '.$data->amount.' '.$data->currency.' to '.$data->converted_amount.' '.$data->converted_currency.' Refund From '.$master_user_name;
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
        $orderDetailStoreForMaster->notes = 'Request Money receive from '.$data->amount.' '.$data->currency.' to '.$data->converted_amount.' '.$data->converted_currency.' Send to '.$user_name;
        $orderDetailStoreForMaster->save();

        // For Charge
        /*$data->amount = -calculate_flat_percent($amount, $serviceStatData['charge']);
        $data->converted_amount = -calculate_flat_percent($converted_amount, $serviceStatData['charge']);
        $data->order_detail_cause_name = 'charge';
        $data->order_detail_parent_id = $orderDetailStore->getKey();
        $data->notes = 'Request Money send to '.$data->amount.' '.$data->currency.' to '.$data->converted_amount.' '.$data->converted_currency.' Charge Receive from '.$master_user_name;
        $data->step = 3;
        $data->order_detail_parent_id = $orderDetailStore->getKey();
        $orderDetailStoreForCharge = Transaction::orderDetail()->create(Transaction::orderDetail()->orderDetailsDataArrange($data));
        $orderDetailStoreForChargeForMaster = $orderDetailStoreForCharge->replicate();
        $orderDetailStoreForChargeForMaster->user_id = $data->sender_receiver_id;
        $orderDetailStoreForChargeForMaster->sender_receiver_id = $data->user_id;
        $orderDetailStoreForChargeForMaster->order_detail_amount = calculate_flat_percent($amount, $serviceStatData['charge']);
        $orderDetailStoreForChargeForMaster->converted_amount = calculate_flat_percent($converted_amount, $serviceStatData['charge']);
        $orderDetailStoreForChargeForMaster->order_detail_cause_name = 'charge';
        $orderDetailStoreForChargeForMaster->notes = 'Request Money receive from '.$data->amount.' '.$data->currency.' to '.$data->converted_amount.' '.$data->converted_currency.' Charge Send to '.$user_name;
        $orderDetailStoreForChargeForMaster->step = 4;
        $orderDetailStoreForChargeForMaster->save();*/

        // For Discount
        $data->amount = calculate_flat_percent($amount, $serviceStatData['discount']);
        $data->converted_amount = calculate_flat_percent($converted_amount, $serviceStatData['discount']);
        $data->order_detail_cause_name = 'discount';
        $data->notes = 'Request Money receive from '.$data->amount.' '.$data->currency.' to '.$data->converted_amount.' '.$data->converted_currency.' Discount form '.$master_user_name;
        $data->step = 5;
        // $data->order_detail_parent_id = $orderDetailStore->getKey();
        $updateData['order_data']['previous_amount'] = 0;
        $orderDetailStoreForDiscount = Transaction::orderDetail()->create(Transaction::orderDetail()->orderDetailsDataArrange($data));
        $orderDetailStoreForDiscountForMaster = $orderDetailStoreForDiscount->replicate();
        $orderDetailStoreForDiscountForMaster->user_id = $data->sender_receiver_id;
        $orderDetailStoreForDiscountForMaster->sender_receiver_id = $data->user_id;
        $orderDetailStoreForDiscountForMaster->order_detail_amount = -calculate_flat_percent($amount, $serviceStatData['discount']);
        $orderDetailStoreForDiscountForMaster->converted_amount = -calculate_flat_percent($converted_amount, $serviceStatData['discount']);
        $orderDetailStoreForDiscountForMaster->order_detail_cause_name = 'discount';
        $orderDetailStoreForDiscountForMaster->notes = 'Request Money send to '.$data->amount.' '.$data->currency.' to '.$data->converted_amount.' '.$data->converted_currency.' Discount to '.$user_name;
        $orderDetailStoreForDiscountForMaster->step = 6;
        $orderDetailStoreForDiscountForMaster->save();

        // For commission
        $data->amount = -calculate_flat_percent($amount, $serviceStatData['commission']);
        $data->converted_amount = -calculate_flat_percent($converted_amount, $serviceStatData['commission']);
        $data->order_detail_cause_name = 'commission';
        $data->order_detail_parent_id = $orderDetailStore->getKey();
        $data->notes = 'Request Money Deposit Commission Receive from '.$master_user_name;
        $data->step = 3;
        $data->order_detail_parent_id = $orderDetailStore->getKey();
        $orderDetailStoreForCommission = Transaction::orderDetail()->create(Transaction::orderDetail()->orderDetailsDataArrange($data));
        $orderDetailStoreForCommissionForMaster = $orderDetailStoreForCommission->replicate();
        $orderDetailStoreForCommissionForMaster->user_id = $data->sender_receiver_id;
        $orderDetailStoreForCommissionForMaster->sender_receiver_id = $data->user_id;
        $orderDetailStoreForCommissionForMaster->order_detail_amount = calculate_flat_percent($amount, $serviceStatData['commission']);
        $orderDetailStoreForCommissionForMaster->converted_amount = calculate_flat_percent($converted_amount, $serviceStatData['commission']);
        $orderDetailStoreForCommissionForMaster->order_detail_cause_name = 'commission';
        $orderDetailStoreForCommissionForMaster->notes = 'Wallet to Wallet Deposit Commission Send to '.$user_name;
        $orderDetailStoreForCommissionForMaster->step = 4;
        $orderDetailStoreForCommissionForMaster->save();

        // 'Point Transfer Commission Send to ' . $masterUser->name;
        // 'Point Transfer Commission Receive from ' . $receiver->name;

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

    /**
     * @return int[]
     */
    public function requestMoneyAccept($deposit): array
    {
        $userAccountData = [
            'previous_amount' => null,
            'current_amount' => null,
            'deposit_amount' => null,
        ];

        // Collect Current Balance as Previous Balance
        $userAccountData['previous_amount'] = Transaction::orderDetail()->list([
            'get_order_detail_amount_sum' => true,
            'user_id' => $deposit->user_id,
            'converted_currency' => $deposit->converted_currency,
        ]);

        $serviceStatData = $deposit->order_data['service_stat_data'];
        $master_user_name = $deposit->order_data['master_user_name'];
        $user_name = $deposit->order_data['user_name'];

        $deposit->order_detail_cause_name = 'cash_deposit';
        // $deposit->order_detail_number = $deposit->order_data['accepted_number'];
        $deposit->order_detail_response_id = $deposit->order_data['purchase_number'];
        $deposit->notes = 'Request Money receive from '.$master_user_name;
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
        $orderDetailStoreForMaster->notes = 'Request Money send to '.$user_name;
        $orderDetailStoreForMaster->save();

        // For Charge
        /*$deposit->amount = -calculate_flat_percent($amount, $serviceStatData['charge']);
        $deposit->converted_amount = -calculate_flat_percent($converted_amount, $serviceStatData['charge']);
        $deposit->order_detail_cause_name = 'charge';
        $deposit->order_detail_parent_id = $orderDetailStore->getKey();
        $deposit->notes = 'Request Money Deposit Charge Receive from '.$master_user_name;
        $deposit->step = 3;
        $deposit->order_detail_parent_id = $orderDetailStore->getKey();
        $orderDetailStoreForCharge = Transaction::orderDetail()->create(Transaction::orderDetail()->orderDetailsDataArrange($deposit));
        $orderDetailStoreForChargeForMaster = $orderDetailStoreForCharge->replicate();
        $orderDetailStoreForChargeForMaster->user_id = $deposit->sender_receiver_id;
        $orderDetailStoreForChargeForMaster->sender_receiver_id = $deposit->user_id;
        $orderDetailStoreForChargeForMaster->order_detail_amount = calculate_flat_percent($amount, $serviceStatData['charge']);
        $orderDetailStoreForChargeForMaster->converted_amount = calculate_flat_percent($converted_amount, $serviceStatData['charge']);
        $orderDetailStoreForChargeForMaster->order_detail_cause_name = 'charge';
        $orderDetailStoreForChargeForMaster->notes = 'Request Money Deposit Charge Send to '.$user_name;
        $orderDetailStoreForChargeForMaster->step = 4;
        $orderDetailStoreForChargeForMaster->save();*/

        // discount
        $deposit->amount = calculate_flat_percent($amount, $serviceStatData['discount']);
        $deposit->converted_amount = calculate_flat_percent($converted_amount, $serviceStatData['discount']);
        $deposit->order_detail_cause_name = 'discount';
        $deposit->notes = 'Request Money Discount form '.$master_user_name;
        $deposit->step = 5;
        // $data->order_detail_parent_id = $orderDetailStore->getKey();
        // $updateData['order_data']['previous_amount'] = 0;
        $orderDetailStoreForDiscount = Transaction::orderDetail()->create(Transaction::orderDetail()->orderDetailsDataArrange($deposit));
        $orderDetailStoreForDiscountForMaster = $orderDetailStoreForDiscount->replicate();
        $orderDetailStoreForDiscountForMaster->user_id = $deposit->sender_receiver_id;
        $orderDetailStoreForDiscountForMaster->sender_receiver_id = $deposit->user_id;
        $orderDetailStoreForDiscountForMaster->order_detail_amount = -calculate_flat_percent($amount, $serviceStatData['discount']);
        $orderDetailStoreForDiscountForMaster->converted_amount = -calculate_flat_percent($converted_amount, $serviceStatData['discount']);
        $orderDetailStoreForDiscountForMaster->order_detail_cause_name = 'discount';
        $orderDetailStoreForDiscountForMaster->notes = 'Request Money Deposit Discount to '.$user_name;
        $orderDetailStoreForDiscountForMaster->step = 6;
        $orderDetailStoreForDiscountForMaster->save();

        // For commission
        $deposit->amount = -calculate_flat_percent($amount, $serviceStatData['commission']);
        $deposit->converted_amount = -calculate_flat_percent($converted_amount, $serviceStatData['commission']);
        $deposit->order_detail_cause_name = 'commission';
        $deposit->order_detail_parent_id = $orderDetailStore->getKey();
        $deposit->notes = 'Request Money Deposit Commission Receive from '.$master_user_name;
        $deposit->step = 3;
        $deposit->order_detail_parent_id = $orderDetailStore->getKey();
        $orderDetailStoreForCommission = Transaction::orderDetail()->create(Transaction::orderDetail()->orderDetailsDataArrange($deposit));
        $orderDetailStoreForCommissionForMaster = $orderDetailStoreForCommission->replicate();
        $orderDetailStoreForCommissionForMaster->user_id = $deposit->sender_receiver_id;
        $orderDetailStoreForCommissionForMaster->sender_receiver_id = $deposit->user_id;
        $orderDetailStoreForCommissionForMaster->order_detail_amount = calculate_flat_percent($amount, $serviceStatData['commission']);
        $orderDetailStoreForCommissionForMaster->converted_amount = calculate_flat_percent($converted_amount, $serviceStatData['commission']);
        $orderDetailStoreForCommissionForMaster->order_detail_cause_name = 'commission';
        $orderDetailStoreForCommissionForMaster->notes = 'Request Money Deposit Commission Send to '.$user_name;
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

        // 'Point Transfer Commission Send to ' . $masterUser->name;
        // 'Point Transfer Commission Receive from ' . $receiver->name;
        return $userAccountData;

    }
}
