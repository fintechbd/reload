<?php

namespace Fintech\Reload\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @method array commonAttributes()
 */
class DepositResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'parent_order_number' => $this->parentOrder?->order_number ?? null,
            'slip' => $this->getFirstMediaUrl('slip') ?? null,
            'risk' => $this->risk ?? null,
            'is_refunded' => $this->is_refunded ?? null,
            'order_data' => $this->order_data ?? null,
        ] + $this->commonAttributes();
    }
}
