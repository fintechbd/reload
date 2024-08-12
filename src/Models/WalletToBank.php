<?php

namespace Fintech\Reload\Models;

use Fintech\Core\Traits\AuditableTrait;
use Fintech\Transaction\Models\Order;
use Illuminate\Database\Eloquent\SoftDeletes;

class WalletToBank extends Order
{
    use AuditableTrait;
    use SoftDeletes;

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
            'show' => action_link(route('reload.wallet-to-banks.show', $primaryKey), __('restapi::messages.action.show'), 'get'),
            'update' => action_link(route('reload.wallet-to-banks.update', $primaryKey), __('restapi::messages.action.update'), 'put'),
            'destroy' => action_link(route('reload.wallet-to-banks.destroy', $primaryKey), __('restapi::messages.action.destroy'), 'delete'),
            'restore' => action_link(route('reload.wallet-to-banks.restore', $primaryKey), __('restapi::messages.action.restore'), 'post'),
        ];

        if ($this->getAttribute('deleted_at') == null) {
            unset($links['restore']);
        } else {
            unset($links['destroy']);
        }

        return $links;
    }

    /*
    |--------------------------------------------------------------------------
    | MUTATORS
    |--------------------------------------------------------------------------
    */
}
