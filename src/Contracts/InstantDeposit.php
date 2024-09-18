<?php

namespace Fintech\Reload\Contracts;

use Fintech\Core\Abstracts\BaseModel;

interface InstantDeposit
{
    public function initPayment(BaseModel $order): ?BaseModel;

    public function paymentStatus(BaseModel $order): ?BaseModel;
    public function trackPayment(BaseModel $order): ?BaseModel;
    public function cancelPayment(BaseModel $order, array $inputs = []): ?BaseModel;
}
