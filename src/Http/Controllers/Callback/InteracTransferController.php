<?php

namespace Fintech\Reload\Http\Controllers\Callback;

use Exception;
use Fintech\Core\Enums\Reload\DepositStatus;
use Fintech\Reload\Facades\Reload;
use Fintech\Transaction\Facades\Transaction;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class InteracTransferController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): void
    {
        logger("Interaction Transfer Log, Method: {$request->method()}", $request->all());

        match ($request->input('Event')) {
            'PaymentSuccessful' => $this->acceptTheDeposit($request),
            default => logger("Interaction Transfer Unknown Event: {$request->input('Event')}", $request->all()),
        };
    }

    private function acceptTheDeposit(Request $request): void
    {
        $deposit = Reload::deposit()->findWhere(['paginate' => false, 'order_number' => $request->input('Data.Reference')]);

        try {

            if (! $deposit) {
                throw (new ModelNotFoundException)->setModel(config('fintech.reload.deposit_model'), $request->input('Data.Reference'));
            }

            $exists = false;

            foreach ([DepositStatus::Processing, DepositStatus::AdminVerification] as $requiredStatus) {
                if ($deposit->status->value === $requiredStatus->value) {
                    $exists = true;
                    break;
                }
            }

            if (! $exists) {
                throw new Exception(__('reload::messages.deposit.invalid_status', ['current_status' => $deposit->status->label(), 'target_status' => DepositStatus::Accepted->label()]));
            }
            if ($request->input('Data.PaymentStatus') == 'SUCCESSFUL') {
                Reload::deposit()->accept($deposit, ['vendor_data' => $request->all()]);
            } else {
                Reload::deposit()->cancel($deposit);
            }

        } catch (Exception $exception) {

            Transaction::orderQueue()->removeFromQueueOrderWise($deposit->getKey());

            logger($exception);
        }
    }
}
