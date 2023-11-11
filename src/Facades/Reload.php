<?php

namespace Fintech\Reload\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * // Crud Service Method Point Do not Remove //
 *
 * @see \Fintech\Reload\Reload
 */
class Reload extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Fintech\Reload\Reload::class;
    }
}
