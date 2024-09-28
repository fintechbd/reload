<?php

namespace Fintech\Reload\Http\Controllers\Callback;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class InteracTransferController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): \Illuminate\Http\JsonResponse
    {
        logger("Interaction Transfer Log, Method: {$request->method()}", $request->all());

        match ($request->input('Event.PaymentSuccessful')) {
            'PaymentLinkSuccessful' => $this->paymentLinkSentEvent($request),
            'PaymentSuccessful' => $this->paymentExecuted($request),
            default => logger("Interaction Transfer Unknown Event: {$request->input('Event.PaymentSuccessful')}", $request->all()),
        };

        return response()->json();
    }

    private function paymentLinkSentEvent(Request $request) {}

    private function paymentExecuted(Request $request) {}
}
