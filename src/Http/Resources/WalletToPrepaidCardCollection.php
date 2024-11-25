<?php

namespace Fintech\Reload\Http\Resources;

use Fintech\Core\Facades\Core;
use Fintech\Core\Supports\Constant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use stdClass;

class WalletToPrepaidCardCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return $this->collection->map(function ($walletToCard) {
            $data = [
                'id' => $walletToCard->getKey(),
                'source_country_id' => $walletToCard->source_country_id ?? null,
                'source_country_name' => null,
                'destination_country_id' => $walletToCard->destination_country_id ?? null,
                'destination_country_name' => null,
                'parent_id' => $walletToCard->parent_id ?? null,
                'sender_receiver_id' => $walletToCard->sender_receiver_id ?? null,
                'sender_receiver_name' => null,
                'user_id' => $walletToCard->user_id ?? null,
                'user_name' => null,
                'service_id' => $walletToCard->service_id ?? null,
                'service_name' => null,
                'transaction_form_id' => $walletToCard->transaction_form_id ?? null,
                'transaction_form_name' => $walletToCard->transaction_form_name ?? null,
                'ordered_at' => $walletToCard->ordered_at ?? null,
                'amount' => $walletToCard->amount ?? null,
                'currency' => $walletToCard->currency ?? null,
                'converted_amount' => $walletToCard->converted_amount ?? null,
                'converted_currency' => $walletToCard->converted_currency ?? null,
                'order_number' => $walletToCard->order_number ?? null,
                'risk_profile' => $walletToCard->risk_profile ?? null,
                'notes' => $walletToCard->notes ?? null,
                'is_refunded' => $walletToCard->is_refunded ?? null,
                'order_data' => $walletToCard->order_data ?? new stdClass,
                'status' => $walletToCard->status ?? null,
                'created_at' => $walletToCard->created_at ?? null,
                'updated_at' => $walletToCard->updated_at ?? null,
            ];

            if (Core::packageExists('MetaData')) {
                $data['source_country_name'] = $walletToCard->sourceCountry?->name ?? null;
                $data['destination_country_name'] = $walletToCard->destinationCountry?->name ?? null;
            }
            if (Core::packageExists('Auth')) {
                $data['user_name'] = $walletToCard->user?->name ?? null;
                $data['sender_receiver_name'] = $walletToCard->senderReceiver?->name ?? null;
            }
            if (Core::packageExists('Business')) {
                $data['service_name'] = $walletToCard->service?->service_name ?? null;
            }
            if (Core::packageExists('Business')) {
                $data['service_name'] = $walletToCard->service?->service_name ?? null;
            }
            if (Core::packageExists('Transaction')) {
                $data['transaction_form_name'] = $walletToCard->transactionForm?->name ?? null;
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
