<?php

namespace Fintech\Reload\Vendors;

use Fintech\Core\Abstracts\BaseModel;
use Fintech\Core\Enums\Transaction\OrderStatus;
use Fintech\Core\Supports\AssignVendorVerdict;
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

    public function initPayment($deposit): AssignVendorVerdict
    {
        $order_data = $deposit->order_data;

        $name = explode(' ', $order_data['created_by']);
        $lastName = (count($name) > 1) ? array_pop($name) : '';
        $firstName = implode(' ', $name);

        $payload = [
            'amount' => intval($deposit->amount),
            'channel' => 'Interac',
            'currency' => $deposit->currency,
            'narration' => $deposit->note ?? '',
            'reference' => $order_data['purchase_number'],
            'userInformation' => [
                'fullName' => $order_data['created_by'] ?? '',
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

        $response = $this->client->post('/payment/pay/initiate', $payload)->json();

        $verdict = AssignVendorVerdict::make();

        if ($response['isSuccess']) {
            return $verdict->status(true)
                ->original($response)
                ->amount($response['value']['paymentItem']['totalAmount'] ?? 0)
                ->message($response['value']['message'] ?? '')
                ->charge($response['value']['paymentItem']['fees'] ?? 0)
                ->ref_number($response['value']['paymentItem']['paymentReference'] ?? '')
                ->orderTimeline('(Leather Back) responded with ' . strtolower($verdict->message) . '.', 'success');
        }

        $verdict->status(false)->original($response);

        if ($response['type'] == 'ValidationException') {
            $verdict->message = '';
            foreach ($response['failures'] as $key => $value) {
                $verdict->message .= ($key + 1) . ". {$value} ";
            }
        } else {
            $verdict->message = $response['title'] ?? 'Unknown error';
        }

        return $verdict->orderTimeline('(Leather Back) reported error: ' . strtolower($verdict->message), 'error');
    }

    public function paymentStatus(BaseModel $deposit): ?BaseModel
    {
        return $this->post("/payment/transactions/{$eference}");
    }

    public function cancelPayment(BaseModel $order, array $inputs = []): AssignVendorVerdict
    {
        return AssignVendorVerdict::make();
    }

    public function trackPayment(BaseModel $deposit): ?BaseModel
    {
        return $this->get('/payment/transactions/');
    }
}
