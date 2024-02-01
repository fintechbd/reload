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
            Route::apiResource('deposits', \Fintech\Reload\Http\Controllers\DepositController::class)->only(['index', 'store', 'show']);
            Route::post('deposits/{deposit}/reject', [\Fintech\Reload\Http\Controllers\DepositController::class, 'reject'])->name('deposits.reject');
            Route::post('deposits/{deposit}/accept', [\Fintech\Reload\Http\Controllers\DepositController::class, 'accept'])->name('deposits.accept');
            Route::post('deposits/{deposit}/cancel', [\Fintech\Reload\Http\Controllers\DepositController::class, 'cancel'])->name('deposits.cancel');
            Route::apiResource('currency-swaps', \Fintech\Reload\Http\Controllers\CurrencySwapController::class);
            Route::apiResource('wallet-transfers', \Fintech\Reload\Http\Controllers\WalletTransferController::class);

            //DO NOT REMOVE THIS LINE//
        });
}
