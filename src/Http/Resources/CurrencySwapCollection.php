<?php

namespace Fintech\Reload\Http\Resources;

use Fintech\Core\Facades\Core;
use Fintech\Core\Supports\Constant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use stdClass;

class CurrencySwapCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return $this->collection->map(function ($currencySwap) {
            $data = [
                'id' => $currencySwap->getKey(),
                //                 'source_country_id' => $currencySwap->source_country_id ?? null,
                'source_country_name' => null,
                //                 'destination_country_id' => $currencySwap->destination_country_id ?? null,
                'destination_country_name' => null,
                //                 'parent_id' => $currencySwap->parent_id ?? null,
                //                 'sender_receiver_id' => $currencySwap->sender_receiver_id ?? null,
                'sender_receiver_name' => null,
                //                 'user_id' => $currencySwap->user_id ?? null,
                'user_name' => null,
                //                 'service_id' => $currencySwap->service_id ?? null,
                'service_name' => null,
                //                 'transaction_form_id' => $currencySwap->transaction_form_id ?? null,
                'transaction_form_name' => $currencySwap->transaction_form_name ?? null,
                'ordered_at' => $currencySwap->ordered_at ?? null,
                'order_number' => $currencySwap->order_number ?? null,
                'risk_profile' => $currencySwap->risk_profile ?? null,
                'notes' => $currencySwap->notes ?? null,
                'is_refunded' => $currencySwap->is_refunded ?? null,
                'order_data' => $currencySwap->order_data ?? new stdClass,
                'status' => $currencySwap->status ?? null,
                'created_at' => $currencySwap->created_at ?? null,
                'updated_at' => $currencySwap->updated_at ?? null,
            ] + $currencySwap->commonAttributes();

            if (Core::packageExists('MetaData')) {
                $data['source_country_name'] = $currencySwap->sourceCountry?->name ?? null;
                $data['destination_country_name'] = $currencySwap->destinationCountry?->name ?? null;
            }
            if (Core::packageExists('Auth')) {
                $data['user_name'] = $currencySwap->user?->name ?? null;
                $data['sender_receiver_name'] = $currencySwap->senderReceiver?->name ?? null;
            }
            if (Core::packageExists('Business')) {
                $data['service_name'] = $currencySwap->service?->service_name ?? null;
            }
            if (Core::packageExists('Business')) {
                $data['service_name'] = $currencySwap->service?->service_name ?? null;
            }
            if (Core::packageExists('Transaction')) {
                $data['transaction_form_name'] = $currencySwap->transactionForm?->name ?? null;
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
