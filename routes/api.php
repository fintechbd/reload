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

            Route::post('request-moneys/{request_money}/reject', [RequestMoneyController::class, 'reject'])->name('request-moneys.reject');
            Route::post('request-moneys/{request_money}/restore', [RequestMoneyController::class, 'restore'])->name('request-moneys.restore');
            Route::apiResource('request-moneys', RequestMoneyController::class);

            Route::apiResource('wallet-to-banks', \Fintech\Reload\Http\Controllers\WalletToBankController::class);
            Route::post('wallet-to-banks/{wallet_to_bank}/restore', [\Fintech\Reload\Http\Controllers\WalletToBankController::class, 'restore'])->name('wallet-to-banks.restore');

            Route::apiResource('wallet-to-atms', \Fintech\Reload\Http\Controllers\WalletToAtmController::class);
            Route::post('wallet-to-atms/{wallet_to_atm}/restore', [\Fintech\Reload\Http\Controllers\WalletToAtmController::class, 'restore'])->name('wallet-to-atms.restore');

            Route::apiResource('wallet-to-prepaid-cards', \Fintech\Reload\Http\Controllers\WalletToPrepaidCardController::class);
            Route::post('wallet-to-prepaid-cards/{wallet_to_prepaid_card}/restore', [\Fintech\Reload\Http\Controllers\WalletToPrepaidCardController::class, 'restore'])->name('wallet-to-prepaid-cards.restore');

            //DO NOT REMOVE THIS LINE//
        });
}
