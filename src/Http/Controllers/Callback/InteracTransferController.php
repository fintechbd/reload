<?php

namespace Fintech\Reload\Http\Controllers\Callback;

use Exception;
use Fintech\Core\Enums\Reload\DepositStatus;
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
        match ($request->input('Event')) {
            'PaymentSuccessful' => $this->acceptOrder($request),
            'PaymentFailed' => $this->cancelOrder($request),
            default => logger("Interact-E-Transfer Unknown Event: {$request->input('Event')}", $request->all()),
        };
    }

    private function acceptOrder(Request $request): void
    {
        $deposit = reload()->deposit()->findWhere([
            'paginate' => false,
            'purchase_number' => $request->input('Data.Reference'),
            'status' => [
                DepositStatus::Processing->value,
                DepositStatus::AdminVerification->value,
            ],
        ]);

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
                reload()->deposit()->accept($deposit, ['vendor_data' => $request->all()]);
            }

        } catch (ModelNotFoundException $exception) {
            logger($exception);
        } catch (Exception $exception) {
            transaction()->orderQueue()->removeFromQueueOrderWise($deposit->getKey());
            logger($exception);
        }
    }

    private function cancelOrder(Request $request): void
    {
        $deposit = reload()->deposit()->findWhere([
            'paginate' => false,
            'purchase_number' => $request->input('Data.Reference'),
            'status' => [
                DepositStatus::Processing->value,
                DepositStatus::AdminVerification->value,
            ],
        ]);

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
                throw new Exception(__('reload::messages.deposit.invalid_status', [
                    'current_status' => $deposit->status->label(),
                    'target_status' => DepositStatus::Rejected->label()]));
            }
            if ($request->input('Data.PaymentStatus') == 'FAILED') {
                reload()->deposit()->reject($deposit, ['vendor_data' => $request->all()]);
            }

        } catch (ModelNotFoundException $exception) {
            logger($exception);
        } catch (Exception $exception) {
            transaction()->orderQueue()->removeFromQueueOrderWise($deposit->getKey());
            logger($exception);
        }
    }
}
