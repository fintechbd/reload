<?php

namespace Fintech\Reload\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class WalletToAtmResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        return parent::toArray($request);
    }
}
