<?php

namespace Fintech\Reload\Http\Resources;

use Fintech\Core\Facades\Core;
use Fintech\Core\Supports\Constant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use stdClass;

class WalletToBankCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return $this->collection->map(function ($walletToBank) {
            $data = [
                'id' => $walletToBank->getKey(),
                'source_country_id' => $walletToBank->source_country_id ?? null,
                'source_country_name' => null,
                'destination_country_id' => $walletToBank->destination_country_id ?? null,
                'destination_country_name' => null,
                'parent_id' => $walletToBank->parent_id ?? null,
                'sender_receiver_id' => $walletToBank->sender_receiver_id ?? null,
                'sender_receiver_name' => null,
                'user_id' => $walletToBank->user_id ?? null,
                'user_name' => null,
                'service_id' => $walletToBank->service_id ?? null,
                'service_name' => null,
                'transaction_form_id' => $walletToBank->transaction_form_id ?? null,
                'transaction_form_name' => $walletToBank->transaction_form_name ?? null,
                'ordered_at' => $walletToBank->ordered_at ?? null,
                'amount' => $walletToBank->amount ?? null,
                'currency' => $walletToBank->currency ?? null,
                'converted_amount' => $walletToBank->converted_amount ?? null,
                'converted_currency' => $walletToBank->converted_currency ?? null,
                'order_number' => $walletToBank->order_number ?? null,
                'risk_profile' => $walletToBank->risk_profile ?? null,
                'notes' => $walletToBank->notes ?? null,
                'is_refunded' => $walletToBank->is_refunded ?? null,
                'order_data' => $walletToBank->order_data ?? new stdClass,
                'status' => $walletToBank->status ?? null,
                'created_at' => $walletToBank->created_at ?? null,
                'updated_at' => $walletToBank->updated_at ?? null,
            ];

            if (Core::packageExists('MetaData')) {
                $data['source_country_name'] = $walletToBank->sourceCountry?->name ?? null;
                $data['destination_country_name'] = $walletToBank->destinationCountry?->name ?? null;
            }
            if (Core::packageExists('Auth')) {
                $data['user_name'] = $walletToBank->user?->name ?? null;
                $data['sender_receiver_name'] = $walletToBank->senderReceiver?->name ?? null;
            }
            if (Core::packageExists('Business')) {
                $data['service_name'] = $walletToBank->service?->service_name ?? null;
            }
            if (Core::packageExists('Business')) {
                $data['service_name'] = $walletToBank->service?->service_name ?? null;
            }
            if (Core::packageExists('Transaction')) {
                $data['transaction_form_name'] = $walletToBank->transactionForm?->name ?? null;
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
