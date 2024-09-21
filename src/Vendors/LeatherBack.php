<?php

namespace Fintech\Reload\Vendors;

use Fintech\Core\Abstracts\BaseModel;
use Fintech\Core\Enums\Transaction\OrderStatus;
use Fintech\Reload\Contracts\InstantDeposit;
use Fintech\Transaction\Facades\Transaction;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class LeatherBack implements InstantDeposit
{
    private mixed $config;

    private mixed $apiUrl;

    private string $status = 'sandbox';

    private PendingRequest $client;

    public function __construct()
    {
        $this->config = config('fintech.reload.providers.leatherback');

        if ($this->config['mode'] === 'sandbox') {
            $this->apiUrl = $this->config[$this->status]['endpoint'];
            $this->status = 'sandbox';

        } else {
            $this->apiUrl = $this->config[$this->status]['endpoint'];
            $this->status = 'live';
        }

        $this->client = Http::withoutVerifying()
            ->baseUrl($this->apiUrl)
            ->acceptJson()
            ->contentType('application/json')
            ->withHeaders([
                'X-Api' => $this->config[$this->status]['api_key'],
            ]);
    }

    public function initPayment(BaseModel $deposit): ?BaseModel
    {
        $order_data = $deposit->order_data;

        $timeline = $deposit->timeline;

        $name = explode(" ", $deposit->order_data['created_by']);
        $lastName = (count($name) > 1) ? array_pop($name) : '';
        $firstName = implode(" ", $name);

        $params = [
            'amount' => intval($deposit->amount),
            'channel' => 'Interac',
            'currency' => $deposit->currency,
            'narration' => $deposit->note ?? '',
            'reference' => $deposit->order_number,
            'userInformation' => [
                'fullName' => $deposit->order_data['created_by'] ?? '',
                'firstName' => trim($firstName),
                'lastName' => trim($lastName),
                'phone' => $order_data['created_by_mobile_number'],
                'emailAddress' => $order_data['created_by_email'],
            ],
            'paymentRequestProps' => [
                'email' => $order_data['interac_email'] ?? $order_data['created_by_email'],
            ],
            'metaData' => [
                'return-url' => route('reload.interac-transfers.callback'),
            ],
        ];

        $response = $this->client->post('/payment/pay/initiate', $params)->json();

        $response = ($response['isSuccess'])
            ? [
                'status' => true,
                'amount' => intval($response['value']['paymentItem']['totalAmount']),
                'message' => $response['value']['message'] ?? '',
                'origin_message' => $response,
            ]
            : [
                'status' => false,
                'amount' => null,
                'message' => $response['error'] ?? '',
                'origin_message' => $response,
            ];

        $order_data['vendor_data'] = $response;

        $info['order_data'] = $order_data;

        if (!$response['status']) {

            $info['status'] = OrderStatus::AdminVerification->value;

            $timeline[] = [
                'message' => "(Leather Back) payment request reported error: " . $response['message'],
                'flag' => 'error',
                'timestamp' => now(),
            ];
        }

        if ($response['status']) {
            $timeline[] = [
                'message' => "(Leather Back) payment request response: " . $response['message'],
                'flag' => 'success',
                'timestamp' => now(),
            ];
        }

        $info['timeline'] = $timeline;

        if (Transaction::order()->update($deposit->getKey(), $info)) {
            $deposit->fresh();

            return $deposit;
        }

        return null;
    }

    public function paymentStatus(BaseModel $order): ?BaseModel
    {
        return $this->post("/payment/transactions/{$eference}");
    }

    public function cancelPayment(BaseModel $order, array $inputs = []): ?BaseModel
    {
        // TODO: Implement cancelPayment() method.
    }

    public function trackPayment(BaseModel $order): ?BaseModel
    {
        return $this->get('/payment/transactions/');
    }
}
