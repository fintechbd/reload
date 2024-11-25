<?php

namespace Fintech\Reload\Http\Resources;

use Fintech\Core\Facades\Core;
use Fintech\Core\Supports\Constant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use stdClass;

class WalletToWalletCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return $this->collection->map(function ($walletToWallet) {
            $data = [
                'id' => $walletToWallet->getKey(),
                'description' => $deposit->description ?? null,
                'source_country_id' => $walletToWallet->source_country_id ?? null,
                'source_country_name' => null,
                'destination_country_id' => $walletToWallet->destination_country_id ?? null,
                'destination_country_name' => null,
                'parent_id' => $walletToWallet->parent_id ?? null,
                'sender_receiver_id' => $walletToWallet->sender_receiver_id ?? null,
                'sender_receiver_name' => null,
                'user_id' => $walletToWallet->user_id ?? null,
                'user_name' => null,
                'service_id' => $walletToWallet->service_id ?? null,
                'service_name' => null,
                'transaction_form_id' => $walletToWallet->transaction_form_id ?? null,
                'transaction_form_name' => $walletToWallet->transaction_form_name ?? null,
                'ordered_at' => $walletToWallet->ordered_at ?? null,
                'amount' => $walletToWallet->amount ?? null,
                'amount_formatted' => (string) currency($walletToWallet->amount ?? null, $walletToWallet->currency),
                'currency' => $walletToWallet->currency ?? null,
                'converted_amount' => $walletToWallet->converted_amount ?? null,
                'converted_amount_formatted' => (string) currency($walletToWallet->converted_amount ?? null, $walletToWallet->converted_currency),
                'converted_currency' => $walletToWallet->converted_currency ?? null,
                'order_number' => $walletToWallet->order_number ?? null,
                'risk_profile' => $walletToWallet->risk_profile ?? null,
                'notes' => $walletToWallet->notes ?? null,
                'is_refunded' => $walletToWallet->is_refunded ?? null,
                'order_data' => $walletToWallet->order_data ?? new stdClass,
                'status' => $walletToWallet->status ?? null,
                'created_at' => $walletToWallet->created_at ?? null,
                'updated_at' => $walletToWallet->updated_at ?? null,
            ];

            if (Core::packageExists('MetaData')) {
                $data['source_country_name'] = $walletToWallet->sourceCountry?->name ?? null;
                $data['destination_country_name'] = $walletToWallet->destinationCountry?->name ?? null;
            }
            if (Core::packageExists('Auth')) {
                $data['user_name'] = $walletToWallet->user?->name ?? null;
                $data['sender_receiver_name'] = $walletToWallet->senderReceiver?->name ?? null;
            }
            if (Core::packageExists('Business')) {
                $data['service_name'] = $walletToWallet->service?->service_name ?? null;
            }
            if (Core::packageExists('Business')) {
                $data['service_name'] = $walletToWallet->service?->service_name ?? null;
            }
            if (Core::packageExists('Transaction')) {
                $data['transaction_form_name'] = $walletToWallet->transactionForm?->name ?? null;
            }

            return $data;
        })->toArray();
    }

    /**
     * Get additional data that should be returned with the resource array.
     *
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        return [
            'options' => [
                'dir' => Constant::SORT_DIRECTIONS,
                'per_page' => Constant::PAGINATE_LENGTHS,
                'sort' => ['id', 'name', 'created_at', 'updated_at'],
            ],
            'query' => $request->all(),
        ];
    }
}
