<?php

namespace Fintech\Reload\Models;

use Fintech\Core\Enums\Transaction\OrderStatus;
use Fintech\Core\Traits\AuditableTrait;
use Fintech\Reload\Traits\AuthRelations;
use Fintech\Reload\Traits\BusinessRelations;
use Fintech\Reload\Traits\MetaDataRelations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Deposit extends Model implements HasMedia
{
    use AuditableTrait;
    use AuthRelations;
    use BusinessRelations;
    use InteractsWithMedia;
    use MetaDataRelations;
    use SoftDeletes;

    /*
    |--------------------------------------------------------------------------
    | GLOBAL VARIABLES
    |--------------------------------------------------------------------------
    */

    protected $table = 'orders';

    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    protected $appends = ['links'];

    protected $casts = ['order_data' => 'array', 'restored_at' => 'datetime'];

    protected $hidden = ['creator_id', 'editor_id', 'destroyer_id', 'restorer_id'];

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

    public function currentStatus()
    {
        return $this->status;
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
    | MUTATORS
    |--------------------------------------------------------------------------
    */
}
