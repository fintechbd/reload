<?php

namespace Fintech\Reload\Models;

use Fintech\Core\Enums\Transaction\OrderStatus;
use Fintech\Core\Traits\Audits\BlameableTrait;
use Fintech\Transaction\Models\Order;
use OwenIt\Auditing\Contracts\Auditable;

class RequestMoney extends Order implements Auditable
{
    use BlameableTrait;
    use \OwenIt\Auditing\Auditable;

    /*
    |--------------------------------------------------------------------------
    | GLOBAL VARIABLES
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | FUNCTIONS
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    /**
     * @return array
     */
    public function getLinksAttribute()
    {
        $primaryKey = $this->getKey();

        $links = [
            'show' => action_link(route('reload.request-moneys.show', $primaryKey), __('core::messages.action.show'), 'get'),
            'reject' => action_link(route('reload.request-moneys.reject', $primaryKey), __('core::messages.action.reject'), 'post'),
            'accept' => action_link(route('reload.request-moneys.accept', $primaryKey), __('core::messages.action.accept'), 'post'),
        ];

        if (! empty($this->parent_id)) {
            unset($links['accept']);
        }

        if ($this->currentStatus() == OrderStatus::Rejected->value) {
            unset($links['accept']);
            unset($links['reject']);
        }

        if ($this->getAttribute('deleted_at') == null) {
            unset($links['restore']);
        } else {
            unset($links['destroy']);
        }

        return $links;
    }

    public function currentStatus()
    {
        return $this->status;
    }

    /*
    |--------------------------------------------------------------------------
    | MUTATORS
    |--------------------------------------------------------------------------
    */
}
