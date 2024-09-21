<?php

namespace Fintech\Reload\Contracts;

use Fintech\Core\Abstracts\BaseModel;
use Fintech\Core\Supports\AssignVendorVerdict;

interface InstantDeposit
{
    public function initPayment(BaseModel $deposit): AssignVendorVerdict;

    public function paymentStatus(BaseModel $deposit): ?BaseModel;

    public function trackPayment(BaseModel $deposit): ?BaseModel;

    public function cancelPayment(BaseModel $order, array $inputs = []): AssignVendorVerdict;
}
