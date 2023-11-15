<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "API" middleware group. Enjoy building your API!
|
*/
if (Config::get('fintech.reload.enabled')) {
    Route::prefix('reload')->name('reload.')
        ->middleware(config('fintech.auth.middleware'))
        ->group(function () {
            Route::apiResource('deposits', \Fintech\Reload\Http\Controllers\DepositController::class);
            Route::post('deposits/{deposit}/restore', [\Fintech\Reload\Http\Controllers\DepositController::class, 'restore'])->name('deposits.restore');

            //DO NOT REMOVE THIS LINE//
        });
}
