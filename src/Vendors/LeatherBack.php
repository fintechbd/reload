<?php

namespace Fintech\Reload\Vendors;

use ErrorException;
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
                'X-Api' => $this->config[$this->status]['api_key']
            ]);
    }

    private function post($url = '', $payload = [])
    {
        $response = $this->client->post($url, $payload)->json();

        if ($response['isSuccess'] == true) {
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

    public function initPayment(BaseModel $order): array
    {
        $params = [
            "amount" => $order->amount,
            "channel" => "Interac",
            "currency" => $order->currency,
            "narration" => $order->note,
            "reference" => $order->order_number,
            "userInformation" => [
                "firstName" => "ogr",
                "lastName" => "et",
                "phone" => "08100969815",
                "emailAddress" => "anmshawkat@gmail.com"
            ],
            "paymentRequestProps" => [
                "email" => "anmshawkat@gmail.com"
            ],
            "metaData" => [
                "return-url" => "https://test.co"
            ]
        ];

        return $this->post('/payment/pay/initiate', $params);
    }

    public function paymentStatus(BaseModel $order): mixed
    {
        $params = [
            'transaction_id' => $order->order_data[''],
            'utility_auth_key' => $this->options[$order->order_data['']]['utility_auth_key'],
            'utility_secret_key' => $this->options[$order->order_data['']]['utility_secret_key'],
        ];

        return $this->post('/bill-status', $params);
    }
}
