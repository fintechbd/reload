<?php

namespace Fintech\Reload\Http\Resources;

use Fintech\Core\Facades\Core;
use Fintech\Core\Supports\Constant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use stdClass;

class DepositCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return $this->collection->map(function ($deposit) {
            $data = [
                'id' => $deposit->getKey(),
                'description' => $deposit->description ?? null,
                //                'source_country_id' => $deposit->source_country_id ?? null,
                'source_country_name' => null,
                //                'destination_country_id' => $deposit->destination_country_id ?? null,
                'destination_country_name' => null,
                //                'parent_id' => $deposit->parent_id ?? null,
                //                'sender_receiver_id' => $deposit->sender_receiver_id ?? null,
                'sender_receiver_name' => null,
                //                'user_id' => $deposit->user_id ?? null,
                'user_name' => null,
                //                'service_id' => $deposit->service_id ?? null,
                'service_name' => null,
                //                'transaction_form_id' => $deposit->transaction_form_id ?? null,
                'transaction_form_name' => $deposit->transaction_form_name ?? null,
                'ordered_at' => $deposit->ordered_at ?? null,
                'slip' => $deposit->getFirstMediaUrl('slip') ?? null,
                'currency' => $deposit->currency ?? null,
                'amount' => (string) ($deposit->amount ?? null),
                'converted_currency' => $deposit->converted_currency ?? null,
                'converted_amount' => (string) ($deposit->converted_amount ?? null),
                'amount_formatted' => $deposit->amount_formatted,
                'converted_amount_formatted' => $deposit->converted_amount_formatted,
                'charge_amount' => $deposit->charge_amount,
                'charge_amount_formatted' => $deposit->charge_amount_formatted,
                'discount_amount' => $deposit->discount_amount,
                'discount_amount_formatted' => $deposit->discount_amount_formatted,
                'commission_amount' => $deposit->commission_amount,
                'commission_amount_formatted' => $deposit->commission_amount_formatted,
                'cost_amount' => $deposit->cost_amount,
                'cost_amount_formatted' => $deposit->cost_amount_formatted,
                'interac_charge' => $deposit->interac_charge,
                'interac_charge_formatted' => $deposit->interac_charge_formatted,
                'total_amount' => $deposit->total_amount,
                'total_amount_formatted' => $deposit->total_amount_formatted,
                'previous_amount' => $deposit->previous_amount,
                'previous_amount_formatted' => $deposit->previous_amount_formatted,
                'current_amount' => $deposit->previous_amount,
                'current_amount_formatted' => $deposit->previous_amount_formatted,
                'order_number' => $deposit->order_number ?? null,
                'risk_profile' => $deposit->risk_profile ?? null,
                'notes' => $deposit->notes ?? null,
                'is_refunded' => $deposit->is_refunded ?? null,
                'order_data' => $deposit->order_data ?? new stdClass,
                'status' => $deposit->status ?? null,
                'created_at' => $deposit->created_at ?? null,
                'updated_at' => $deposit->updated_at ?? null,
            ];

            if (Core::packageExists('MetaData')) {
                $data['source_country_name'] = $deposit->sourceCountry?->name ?? null;
                $data['destination_country_name'] = $deposit->destinationCountry?->name ?? null;
            }
            if (Core::packageExists('Auth')) {
                $data['user_name'] = $deposit->user?->name ?? null;
                $data['sender_receiver_name'] = $deposit->senderReceiver?->name ?? null;
            }
            if (Core::packageExists('Business')) {
                $data['service_name'] = $deposit->service?->service_name ?? null;
            }
            if (Core::packageExists('Business')) {
                $data['service_name'] = $deposit->service?->service_name ?? null;
            }
            if (Core::packageExists('Transaction')) {
                $data['transaction_form_name'] = $deposit->transactionForm?->name ?? null;
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
