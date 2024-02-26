<?php

use Fintech\Reload\Http\Controllers\CurrencySwapController;
use Fintech\Reload\Http\Controllers\DepositController;
use Fintech\Reload\Http\Controllers\RequestMoneyController;
use Fintech\Reload\Http\Controllers\WalletToWalletController;
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
            Route::apiResource('deposits', DepositController::class)->only(['index', 'store', 'show']);
            Route::post('deposits/{deposit}/reject', [DepositController::class, 'reject'])->name('deposits.reject');
            Route::post('deposits/{deposit}/accept', [DepositController::class, 'accept'])->name('deposits.accept');
            Route::post('deposits/{deposit}/cancel', [DepositController::class, 'cancel'])->name('deposits.cancel');
            Route::apiResource('currency-swaps', CurrencySwapController::class)->only(['index', 'store', 'show']);
            Route::apiResource('wallet-to-wallets', WalletToWalletController::class)->only(['index', 'store', 'show']);

            Route::apiResource('request-moneys', RequestMoneyController::class);
            Route::post('request-moneys/{request_money}/restore', [RequestMoneyController::class, 'restore'])->name('request-moneys.restore');

            //DO NOT REMOVE THIS LINE//
        });
}
