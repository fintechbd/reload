<?php

namespace Fintech\Reload\Vendors;

use Fintech\Core\Abstracts\BaseModel;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class LeatherBack
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

    public function initPayment(BaseModel $order): array
    {
        $interacData = $order->order_data['interac_data'];

        $nameSplitted = json_decode(preg_replace('/(.*) (.+)/iu', '["$1", "$2"]', $order->order_data['created_by']), true);

        $params = [
            'amount' => intval($order->amount),
            'channel' => 'Interac',
            'currency' => $order->currency,
            'narration' => $order->note ?? '',
            'reference' => $order->order_number,
            'userInformation' => [
                'firstName' => $interacData['first_name'] ?? $nameSplitted[0],
                'lastName' => $interacData['last_name'] ?? $nameSplitted[1],
                'phone' => $interacData['phone'] ?? $order->order_data['created_by_mobile_number'],
                'emailAddress' => $order->order_data['created_by_email'],
            ],
            'paymentRequestProps' => [
                'email' => $interacData['email'],
            ],
            'metaData' => [
                'return-url' => route('reload.interac-transfers.callback'),
            ],
        ];

        $response = $this->post('/payment/pay/initiate', $params);

        if ($response['status']) {
            $status = (in_array($response['Code'], ['0001', '0002']))
                ? OrderStatus::Accepted->value
                : OrderStatus::AdminVerification->value;
        }
        $order_data['vendor_data'] = $response;

        if (Transaction::order()->update($order->getKey(), ['status' => $status, 'order_data' => $order_data])) {
            $order->fresh();

            return $order;
        }
    }

    private function post($url = '', $payload = [])
    {
        $response = $this->client->post($url, $payload)->json();

        if ($response['origin_message']['isSuccess']) {
            return [
                'status' => true,
                'amount' => intval($response['value']['paymentItem']['totalAmount']),
                'message' => $response['value']['message'] ?? null,
                'origin_message' => $response,
            ];
        }

        return [
            'status' => false,
            'amount' => null,
            'message' => $response['error'] ?? null,
            'origin_message' => $response,
        ];
    }

    public function paymentStatus(BaseModel $order, array $inputs = []): mixed
    {
        $params = [
            'transaction_id' => $order->order_data[''],
            'utility_auth_key' => $this->options[$order->order_data['']]['utility_auth_key'],
            'utility_secret_key' => $this->options[$order->order_data['']]['utility_secret_key'],
        ];

        return $this->post('/bill-status', $params);
    }
}
