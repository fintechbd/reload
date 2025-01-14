<?php

namespace Fintech\Reload\Http\Resources;

use Fintech\Core\Facades\Core;
use Fintech\Core\Supports\Constant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use stdClass;

class RequestMoneyCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return $this->collection->map(function ($requestMoney) {
            $data = [
                'id' => $requestMoney->getKey(),
                //                 'source_country_id' => $requestMoney->source_country_id ?? null,
                'source_country_name' => null,
                //                 'destination_country_id' => $requestMoney->destination_country_id ?? null,
                'destination_country_name' => null,
                //                 'parent_id' => $requestMoney->parent_id ?? null,
                //                 'sender_receiver_id' => $requestMoney->sender_receiver_id ?? null,
                'sender_receiver_name' => null,
                //                 'user_id' => $requestMoney->user_id ?? null,
                'user_name' => null,
                //                 'service_id' => $requestMoney->service_id ?? null,
                'service_name' => null,
                //                 'transaction_form_id' => $requestMoney->transaction_form_id ?? null,
                'transaction_form_name' => $requestMoney->transaction_form_name ?? null,
                'ordered_at' => $requestMoney->ordered_at ?? null,
                'order_number' => $requestMoney->order_number ?? null,
                'risk_profile' => $requestMoney->risk_profile ?? null,
                'notes' => $requestMoney->notes ?? null,
                'is_refunded' => $requestMoney->is_refunded ?? null,
                'order_data' => $requestMoney->order_data ?? new stdClass,
                'status' => $requestMoney->status ?? null,
                'created_at' => $requestMoney->created_at ?? null,
                'updated_at' => $requestMoney->updated_at ?? null,
            ] + $requestMoney->commonAttributes();

            if (Core::packageExists('MetaData')) {
                $data['source_country_name'] = $requestMoney->sourceCountry?->name ?? null;
                $data['destination_country_name'] = $requestMoney->destinationCountry?->name ?? null;
            }
            if (Core::packageExists('Auth')) {
                $data['user_name'] = $requestMoney->user?->name ?? null;
                $data['sender_receiver_name'] = $requestMoney->senderReceiver?->name ?? null;
            }
            if (Core::packageExists('Business')) {
                $data['service_name'] = $requestMoney->service?->service_name ?? null;
            }
            if (Core::packageExists('Business')) {
                $data['service_name'] = $requestMoney->service?->service_name ?? null;
            }
            if (Core::packageExists('Transaction')) {
                $data['transaction_form_name'] = $requestMoney->transactionForm?->name ?? null;
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
