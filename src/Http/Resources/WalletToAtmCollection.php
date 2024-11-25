<?php

namespace Fintech\Reload\Http\Resources;

use Fintech\Core\Facades\Core;
use Fintech\Core\Supports\Constant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use stdClass;

class WalletToAtmCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return $this->collection->map(function ($walletToAtm) {
            $data = [
                'id' => $walletToAtm->getKey(),
                'source_country_id' => $walletToAtm->source_country_id ?? null,
                'source_country_name' => null,
                'destination_country_id' => $walletToAtm->destination_country_id ?? null,
                'destination_country_name' => null,
                'parent_id' => $walletToAtm->parent_id ?? null,
                'sender_receiver_id' => $walletToAtm->sender_receiver_id ?? null,
                'sender_receiver_name' => null,
                'user_id' => $walletToAtm->user_id ?? null,
                'user_name' => null,
                'service_id' => $walletToAtm->service_id ?? null,
                'service_name' => null,
                'transaction_form_id' => $walletToAtm->transaction_form_id ?? null,
                'transaction_form_name' => $walletToAtm->transaction_form_name ?? null,
                'ordered_at' => $walletToAtm->ordered_at ?? null,
                'amount' => $walletToAtm->amount ?? null,
                'currency' => $walletToAtm->currency ?? null,
                'converted_amount' => $walletToAtm->converted_amount ?? null,
                'converted_currency' => $walletToAtm->converted_currency ?? null,
                'order_number' => $walletToAtm->order_number ?? null,
                'risk_profile' => $walletToAtm->risk_profile ?? null,
                'notes' => $walletToAtm->notes ?? null,
                'is_refunded' => $walletToAtm->is_refunded ?? null,
                'order_data' => $walletToAtm->order_data ?? new stdClass,
                'status' => $walletToAtm->status ?? null,
                'created_at' => $walletToAtm->created_at ?? null,
                'updated_at' => $walletToAtm->updated_at ?? null,
            ];

            if (Core::packageExists('MetaData')) {
                $data['source_country_name'] = $walletToAtm->sourceCountry?->name ?? null;
                $data['destination_country_name'] = $walletToAtm->destinationCountry?->name ?? null;
            }
            if (Core::packageExists('Auth')) {
                $data['user_name'] = $walletToAtm->user?->name ?? null;
                $data['sender_receiver_name'] = $walletToAtm->senderReceiver?->name ?? null;
            }
            if (Core::packageExists('Business')) {
                $data['service_name'] = $walletToAtm->service?->service_name ?? null;
            }
            if (Core::packageExists('Business')) {
                $data['service_name'] = $walletToAtm->service?->service_name ?? null;
            }
            if (Core::packageExists('Transaction')) {
                $data['transaction_form_name'] = $walletToAtm->transactionForm?->name ?? null;
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
