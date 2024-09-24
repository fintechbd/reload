<?php

namespace Fintech\Reload\Services;

use ErrorException;
use Fintech\Business\Facades\Business;
use Fintech\Core\Abstracts\BaseModel;
use Fintech\Core\Enums\Transaction\OrderStatus;
use Fintech\Core\Exceptions\UpdateOperationException;
use Fintech\Core\Exceptions\VendorNotFoundException;
use Fintech\Reload\Contracts\InstantDeposit;
use Fintech\Remit\Exceptions\RemitException;
use Fintech\Transaction\Facades\Transaction;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\App;

class AssignVendorService
{
    private $serviceVendorModel;

    private InstantDeposit $serviceVendorDriver;

    /**
     * @throws VendorNotFoundException
     */
    private function initVendor(string $slug): void
    {
        $availableVendors = config('fintech.reload.providers', []);

        if (! isset($availableVendors[$slug])) {
            throw new VendorNotFoundException(ucfirst($slug));
        }

        $this->serviceVendorModel = Business::serviceVendor()->findWhere(['service_vendor_slug' => $slug, 'enabled']);

        if (! $this->serviceVendorModel) {
            throw (new ModelNotFoundException)->setModel(config('fintech.business.service_vendor_model'), $slug);
        }

        $this->serviceVendorDriver = App::make($availableVendors[$slug]['driver']);
    }

    /**
     * @param  BaseModel  $deposit
     *
     * @throws UpdateOperationException|VendorNotFoundException
     */
    public function initPayment($deposit)
    {
        $data['timeline'] = $deposit->timeline ?? [];

        $this->initVendor($deposit->vendor);

        $service = Business::service()->find($deposit->service_id);

        $data['timeline'][] = [
            'message' => "Requesting ({$this->serviceVendorModel->service_vendor_name}) for ".ucwords(strtolower($service->service_name)).' payment request',
            'flag' => 'info',
            'timestamp' => now(),
        ];

        $verdict = $this->serviceVendorDriver->initPayment($deposit);

        $data['timeline'][] = $verdict->timeline;
        $data['note'] = $verdict->message;
        $data['order_data'] = $deposit->order_data;
        $data['order_data']['vendor_data'] = $verdict->toArray();

        if (! $verdict->status) {
            $data['status'] = OrderStatus::AdminVerification->value;
            $data['timeline'][] = [
                'message' => "Updating {$service->service_name} payment request status. Requires ".OrderStatus::AdminVerification->label().' confirmation',
                'flag' => 'error',
                'timestamp' => now(),
            ];
        } else {
            $data['timeline'][] = [
                'message' => "Waiting for ({$this->serviceVendorModel->service_vendor_name}/Customer) to approve ".ucwords(strtolower($service->service_name)).' payment request.',
                'flag' => 'info',
                'timestamp' => now(),
            ];
        }

        if (! Transaction::order()->update($deposit->getKey(), $data)) {
            throw new \ErrorException(__('remit::messages.assign_vendor.failed', [
                'slug' => $deposit->vendor,
            ]));
        }
    }

    /**
     * @throws RemitException
     * @throws ErrorException|VendorNotFoundException
     */
    public function trackPayment(BaseModel $order): mixed
    {

        if ($order->service_vendor_id == config('fintech.business.default_vendor')) {
            throw new RemitException(__('remit::messages.assign_vendor.not_assigned'));
        }

        $this->initVendor($order->vendor);

        return $this->serviceVendorDriver->trackOrder($order);
    }

    /**
     * @throws ErrorException|VendorNotFoundException
     */
    public function cancelPayment(BaseModel $order): mixed
    {
        $this->initVendor($order->vendor);

        return $this->serviceVendorDriver->orderStatus($order);
    }

    /**
     * @throws RemitException|VendorNotFoundException
     */
    public function paymentStatus(BaseModel $order): mixed
    {

        if ($order->service_vendor_id == config('fintech.business.default_vendor')) {
            throw new RemitException(__('remit::messages.assign_vendor.not_assigned'));
        }

        $this->initVendor($order->vendor);

        return $this->serviceVendorDriver->orderStatus($order);
    }
}
