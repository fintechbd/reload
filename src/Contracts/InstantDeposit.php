<?php

namespace Fintech\Reload\Contracts;

use Fintech\Core\Abstracts\BaseModel;

interface InstantDeposit
{
    public function initPayment(BaseModel $deposit): ?BaseModel;

    public function paymentStatus(BaseModel $deposit): ?BaseModel;

    public function trackPayment(BaseModel $deposit): ?BaseModel;

    public function cancelPayment(BaseModel $order, array $inputs = []): ?BaseModel;
}
