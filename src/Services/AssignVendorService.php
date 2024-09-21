<?php

namespace Fintech\Reload\Services;

use ErrorException;
use Fintech\Business\Facades\Business;
use Fintech\Core\Abstracts\BaseModel;
use Fintech\Core\Enums\Transaction\OrderStatus;
use Fintech\Core\Exceptions\UpdateOperationException;
use Fintech\Core\Exceptions\VendorNotFoundException;
use Fintech\Core\Facades\Core;
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
     * @throws ErrorException
     * @throws UpdateOperationException|VendorNotFoundException
     */
    public function initPayment(BaseModel $deposit): mixed
    {
        $this->initVendor($deposit->vendor);

        $service = Business::service()->find($deposit->service_id);

        $timeline = $deposit->timeline;

        $timeline[] = [
            'message' => "Requesting ({$this->serviceVendorModel->service_vendor_name}) for {$service->service_name} payment request",
            'flag' => 'info',
            'timestamp' => now(),
        ];

        if (! Transaction::order()->update($deposit->getKey(), [
            'timeline' => $timeline,
            'status' => OrderStatus::Processing->value])) {
            throw new UpdateOperationException(__('remit::messages.assign_vendor.failed', ['slug' => $deposit->vendor]));
        }

        $deposit->fresh();

        return $this->serviceVendorDriver->initPayment($deposit);
    }

    /**
     * @throws RemitException
     * @throws ErrorException
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
     * @throws ErrorException
     */
    public function cancelPayment(BaseModel $order): mixed
    {
        $this->initVendor($order->vendor);

        return $this->serviceVendorDriver->orderStatus($order);
    }

    /**
     * @throws RemitException
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
