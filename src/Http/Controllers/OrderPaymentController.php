<?php

namespace Fintech\Reload\Http\Controllers;

use Exception;
use Fintech\Core\Enums\Transaction\OrderStatus;
use Fintech\Core\Exceptions\UpdateOperationException;
use Fintech\Reload\Http\Requests\OrderPaymentRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class OrderPaymentController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @throws Exception
     */
    public function __invoke(string $id, OrderPaymentRequest $request): JsonResponse
    {
        try {

            $order = transaction()->order()->find($id);

            if (! $order) {
                throw (new ModelNotFoundException)->setModel(config('fintech.transaction.order_model'), $id);
            }

            $payoutService = business()->service()->findWhere(['service_slug' => 'interac_e_transfer', 'enabled' => true]);

            if (! $payoutService) {
                throw new Exception(__('reload::messages.deposit.service_unavailable'));
            }

            $inputs = $request->validated();

            $orderData = $order->order_data ?? [];

            $orderData['interac_email'] = $inputs['interac_email'];

            $payoutVendor = (empty($inputs['vendor']))
                ? business()->serviceVendor()->findWhere(['service_vendor_slug' => $inputs['vendor'], 'enabled' => true, 'paginate' => false])
                : $payoutService->serviceVendor;

            if (! $payoutVendor) {
                throw (new ModelNotFoundException)->setModel(config('fintech.business.service_vendor_model'), $inputs['vendor']);
            }

            $data = [
                'status' => OrderStatus::Pending,
                'order_data' => $orderData,
                'notes' => "{$order->notes}.\nRequesting {$payoutService->service_name} Payout from {$payoutVendor->service_vendor_name} vendor.",
            ];

            $payout = [
                'user_id' => $order->user_id,
                'parent_id' => $order->getKey(),
                'source_country_id' => $order->source_country_id,
                'destination_country_id' => $order->source_country_id,
                'service_id' => $payoutService->getKey(),
                'ordered_at' => now()->format('Y-m-d H:i:s'),
                'amount' => $order->total_amount,
                'currency' => $order->currency,
                'order_data' => [
                    'request_from' => request()->platform()->value,
                    'interac_email' => $inputs['interac_email'],
                ],
            ];

            if (! transaction()->order()->update($id, $data) || ! reload()->deposit()->create($payout)) {
                throw (new UpdateOperationException)->setModel(config('fintech.transaction.order_model'), $id);
            }

            $orderService = $order->service;

            return response()->updated(__('core::messages.transaction.request_created', ['service' => ucwords($orderService->service_name).' Payment']));

        } catch (Exception $exception) {

            return response()->failed($exception);
        }
    }
}
