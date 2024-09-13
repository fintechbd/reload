<?php

use Fintech\RestApi\Http\Controllers\Reload\Charts\DepositPartnerController;
use Fintech\RestApi\Http\Controllers\Reload\Charts\WithdrawPartnerController;
use Fintech\RestApi\Http\Controllers\Reload\CurrencySwapController;
use Fintech\RestApi\Http\Controllers\Reload\DepositController;
use Fintech\RestApi\Http\Controllers\Reload\RequestMoneyController;
use Fintech\RestApi\Http\Controllers\Reload\WalletToAtmController;
use Fintech\RestApi\Http\Controllers\Reload\WalletToBankController;
use Fintech\RestApi\Http\Controllers\Reload\WalletToPrepaidCardController;
use Fintech\RestApi\Http\Controllers\Reload\WalletToWalletController;
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
    Route::prefix(config('fintech.reload.root_prefix', 'api/'))->middleware(['api', 'http_log', 'encrypted'])->group(function () {
        Route::prefix('reload')->name('reload.')
            ->middleware(config('fintech.auth.middleware'))
            ->group(function () {
                Route::apiResource('deposits', DepositController::class)
                    ->only(['index', 'store', 'show']);

                Route::post('interac', function (\Illuminate\Http\Request $request) {
                    return response()->created([
                        'message' => __('restapi::messages.resource.created', ['model' => 'Interac-E-Transfer']),
                        'id' => mt_rand(1,9)
                    ]);
                });

                Route::post('deposits/{deposit}/reject', [DepositController::class, 'reject'])
                    ->name('deposits.reject');

                Route::post('deposits/{deposit}/accept', [DepositController::class, 'accept'])
                    ->name('deposits.accept');

                Route::post('deposits/{deposit}/cancel', [DepositController::class, 'cancel'])
                    ->name('deposits.cancel');

                Route::apiResource('currency-swaps', CurrencySwapController::class)
                    ->only(['index', 'store', 'show']);

                Route::apiResource('wallet-to-wallets', WalletToWalletController::class)
                    ->only(['index', 'store', 'show']);

                Route::post('request-moneys/{request_money}/accept', [RequestMoneyController::class, 'accept'])
                    ->name('request-moneys.accept');

                Route::post('request-moneys/{request_money}/reject', [RequestMoneyController::class, 'reject'])
                    ->name('request-moneys.reject');

                //             Route::post('request-moneys/{request_money}/restore', [RequestMoneyController::class, 'restore'])->name('request-moneys.restore');
                Route::apiResource('request-moneys', RequestMoneyController::class)
                    ->only(['index', 'store', 'show']);

                Route::apiResource('wallet-to-banks', WalletToBankController::class)
                    ->only(['index', 'store', 'show']);
                //             Route::post('wallet-to-banks/{wallet_to_bank}/restore', [WalletToBankController::class, 'restore'])->name('wallet-to-banks.restore');

                Route::apiResource('wallet-to-atms', WalletToAtmController::class)
                    ->only(['index', 'store', 'show']);
                //             Route::post('wallet-to-atms/{wallet_to_atm}/restore', [WalletToAtmController::class, 'restore'])->name('wallet-to-atms.restore');

                Route::apiResource('wallet-to-prepaid-cards', WalletToPrepaidCardController::class)
                    ->only(['index', 'store', 'show']);
                //             Route::post('wallet-to-prepaid-cards/{wallet_to_prepaid_card}/restore', [WalletToPrepaidCardController::class, 'restore'])->name('wallet-to-prepaid-cards.restore');

                //DO NOT REMOVE THIS LINE//

                Route::prefix('charts')->name('charts.')->group(function () {
                    Route::get('deposit-partner-summary',
                        DepositPartnerController::class)
                        ->name('deposit-partner-summary');

                    Route::get('withdraw-partner-summary',
                        WithdrawPartnerController::class)
                        ->name('withdraw-partner-summary');
                });
            });
    });
}
