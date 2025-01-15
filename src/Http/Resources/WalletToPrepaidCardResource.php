<?php

namespace Fintech\Reload\Http\Resources;

use Fintech\Core\Facades\Core;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use stdClass;

class WalletToPrepaidCardResource extends JsonResource
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
                'risk' => $this->risk ?? null,
                'is_refunded' => $this->is_refunded ?? null,
                'order_data' => $this->order_data ?? null,
            ] + $this->commonAttributes();
    }
}
