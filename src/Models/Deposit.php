<?php

namespace Fintech\Reload\Models;

use Fintech\Core\Enums\Auth\RiskProfile;
use Fintech\Core\Enums\Transaction\OrderStatus;
use Fintech\Core\Traits\Audits\BlameableTrait;
use Fintech\Transaction\Models\Order;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Deposit extends Order implements Auditable, HasMedia
{
    use BlameableTrait;
    use InteractsWithMedia;
    use \OwenIt\Auditing\Auditable;

    /*
    |--------------------------------------------------------------------------
    | GLOBAL VARIABLES
    |--------------------------------------------------------------------------
    */

    protected $casts = [
        'order_data' => 'array',
        'timeline' => 'array',
        'restored_at' => 'datetime',
        'enabled' => 'bool',
        'risk_profile' => RiskProfile::class,
        'status' => OrderStatus::class,
    ];

    /*
    |--------------------------------------------------------------------------
    | FUNCTIONS
    |--------------------------------------------------------------------------
    */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('slip')
            ->acceptsMimeTypes(['image/jpg', 'image/jpeg', 'image/png', 'image/svg+xml', 'image/gif', 'application/pdf'])
            ->useDisk(config('filesystems.default', 'public'))
            ->singleFile();
    }

    /**
     * @return array
     */
    public function getLinksAttribute()
    {
        $primaryKey = $this->getKey();

        $links = [
            'show' => action_link(route('reload.deposits.show', $primaryKey), __('core::messages.action.show'), 'get'),
            'reject' => action_link(route('reload.deposits.reject', $primaryKey), __('reload::messages.action.reject'), 'post'),
            'accept' => action_link(route('reload.deposits.accept', $primaryKey), __('reload::messages.action.accept'), 'post'),
            'cancel' => action_link(route('reload.deposits.cancel', $primaryKey), __('reload::messages.action.cancel'), 'post'),
        ];

        if ($this->currentStatus() == OrderStatus::Processing->value) {
            unset($links['cancel']);
        }

        if ($this->currentStatus() == OrderStatus::Accepted->value) {
            unset($links['reject']);
        }

        return $links;
    }

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

    public function currentStatus()
    {
        return $this->status->value;
    }

    /*
    |--------------------------------------------------------------------------
    | MUTATORS
    |--------------------------------------------------------------------------
    */
}
