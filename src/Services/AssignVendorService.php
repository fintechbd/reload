<?php

namespace Fintech\Reload\Services;

use ErrorException;
use Fintech\Business\Facades\Business;
use Fintech\Core\Abstracts\BaseModel;
use Fintech\Core\Enums\Transaction\OrderStatus;
use Fintech\Core\Exceptions\UpdateOperationException;
use Fintech\Reload\Contracts\InstantDeposit;
use Fintech\Remit\Contracts\MoneyTransfer;
use Fintech\Remit\Exceptions\AlreadyAssignedException;
use Fintech\Remit\Exceptions\RemitException;
use Fintech\Transaction\Facades\Transaction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\App;

class AssignVendorService
{
    private $serviceVendorModel;

    private InstantDeposit $serviceVendorDriver;

    /**
     * @throws RemitException
     */
    private function initVendor(string $slug): void
    {
        $availableVendors = config('fintech.remit.providers', []);

        if (! isset($availableVendors[$slug])) {
            throw new RemitException(__('remit::messages.assign_vendor.not_found', ['slug' => ucfirst($slug)]));
        }

        $this->serviceVendorModel = Business::serviceVendor()->list(['service_vendor_slug' => $slug, 'enabled'])->first();

        if (! $this->serviceVendorModel) {
            throw (new ModelNotFoundException)->setModel(config('fintech.business.service_vendor_model'), $slug);
        }

        $this->serviceVendorDriver = App::make($availableVendors[$slug]['driver']);
    }

    /**
     * @throws RemitException|ErrorException
     */
    public function requestQuote(BaseModel $order, string $vendor_slug): mixed
    {
        $this->initVendor($vendor_slug);

        return $this->serviceVendorDriver->requestQuote($order);
    }


    /**
     * @throws ErrorException
     * @throws UpdateOperationException|RemitException
     */
    public function initPayment(BaseModel $order, string $vendor_slug): mixed
    {
        $this->initVendor($vendor_slug);

        if (! Transaction::order()->update($order->getKey(), [
            'vendor' => $vendor_slug,
            'service_vendor_id' => $this->serviceVendorModel->getKey(),
            'status' => OrderStatus::Processing->value])) {
            throw new UpdateOperationException(__('remit::assign_vendor.failed', ['slug' => $vendor_slug]));
        }

        $order->fresh();

        return $this->serviceVendorDriver->executeOrder($order);
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
