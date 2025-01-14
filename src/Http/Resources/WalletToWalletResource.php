<?php

namespace Fintech\Reload\Http\Resources;

use Fintech\Core\Facades\Core;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use stdClass;

class WalletToWalletResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request
     * @return array
     */
    public function toArray($request)
    {
        $data = [
            'id' => $this->getKey(),
            'description' => $deposit->description ?? null,
            //                 'source_country_id' => $this->source_country_id ?? null,
            'source_country_name' => null,
            //                 'destination_country_id' => $this->destination_country_id ?? null,
            'destination_country_name' => null,
            //                 'parent_id' => $this->parent_id ?? null,
            //                 'sender_receiver_id' => $this->sender_receiver_id ?? null,
            'sender_receiver_name' => null,
            //                 'user_id' => $this->user_id ?? null,
            'user_name' => null,
            //                 'service_id' => $this->service_id ?? null,
            'service_name' => null,
            //                 'transaction_form_id' => $this->transaction_form_id ?? null,
            'transaction_form_name' => $this->transaction_form_name ?? null,
            'ordered_at' => $this->ordered_at ?? null,
            'amount' => $this->amount ?? null,
            'amount_formatted' => (string) currency($this->amount ?? null, $this->currency),
            'currency' => $this->currency ?? null,
            'converted_amount' => $this->converted_amount ?? null,
            'converted_amount_formatted' => (string) currency($this->converted_amount ?? null, $this->converted_currency),
            'converted_currency' => $this->converted_currency ?? null,
            'order_number' => $this->order_number ?? null,
            'risk_profile' => $this->risk_profile ?? null,
            'notes' => $this->notes ?? null,
            'is_refunded' => $this->is_refunded ?? null,
            'order_data' => $this->order_data ?? new stdClass,
            'status' => $this->status ?? null,
            'created_at' => $this->created_at ?? null,
            'updated_at' => $this->updated_at ?? null,
        ] + $this->commonAttributes();

        if (Core::packageExists('MetaData')) {
            $data['source_country_name'] = $this->sourceCountry?->name ?? null;
            $data['destination_country_name'] = $this->destinationCountry?->name ?? null;
        }
        if (Core::packageExists('Auth')) {
            $data['user_name'] = $this->user?->name ?? null;
            $data['sender_receiver_name'] = $this->senderReceiver?->name ?? null;
        }
        if (Core::packageExists('Business')) {
            $data['service_name'] = $this->service?->service_name ?? null;
        }
        if (Core::packageExists('Business')) {
            $data['service_name'] = $this->service?->service_name ?? null;
        }
        if (Core::packageExists('Transaction')) {
            $data['transaction_form_name'] = $this->transactionForm?->name ?? null;
        }

        return $data;
    }
}
