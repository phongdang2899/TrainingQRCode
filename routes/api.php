<?php

use App\Http\Controllers\Api\CampaignConfigController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\CampaignController;
use App\Http\Controllers\Api\CodeController;
use App\Http\Controllers\Api\ConfigController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\ProvinceController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\Reward\GiftCodeController;
use App\Http\Controllers\Api\Reward\RedeemController;
use App\Http\Controllers\Api\Reward\CustomerController as CustomerRewardPage;
use App\Http\Controllers\Api\Reward\OtpController;
use App\Http\Controllers\Api\ThirdParty\LoginController as ThirdPartyLoginController;
use App\Http\Controllers\Api\ThirdParty\RedeemController as ThirdPartyRedeemController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\ZoneController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Auth::routes();
Route::prefix('v1')->name('v1')->group(function () {
    // admin api
    Route::prefix('admin/users')->name('admin.users')->group(function () {
        Route::post('/login', [LoginController::class, 'login'])->name('login');
        Route::post('/logout', [LogoutController::class, 'logout'])->middleware(['auth:sanctum'])->name('logout');
    });
    Route::prefix('admin')->middleware(['auth:sanctum', 'not-system-user'])->group(function () {
        // region Campaign
        Route::prefix('campaigns')->name('campaigns')->group(function () {
            Route::get('', [CampaignController::class, 'index'])->name('index');
            Route::middleware(['auth.manager'])->group(function () {
                Route::post('', [CampaignController::class, 'store'])->name('store');
                Route::delete('', [CampaignController::class, 'delete'])->name('delete');
                Route::get('/{id}', [CampaignController::class, 'show'])->name('show');
                Route::put('/{id}', [CampaignController::class, 'update'])->name('update');
                Route::patch('/{id}/status', [CampaignController::class, 'updateStatus'])->name('updateStatus');
                Route::post('/generate', [CampaignController::class, 'generateCode'])->name('generate');
                Route::get('/{id}/config', [CampaignConfigController::class, 'index'])->name('config.index');
                Route::post('/{id}/config', [CampaignConfigController::class, 'store'])->name('config.store');
            });
        });
        // endregion

        // region Code
        Route::prefix('codes')->middleware(['auth.manager'])->name('codes')->group(function () {
            Route::get('', [CodeController::class, 'index'])->name('index');
            Route::delete('', [CodeController::class, 'delete'])->name('delete');
            Route::get('/{id}', [CodeController::class, 'show'])->name('show');
            Route::put('/status', [CodeController::class, 'updateStatus'])->name('updateStatus');
            Route::post('/export', [CodeController::class, 'exportCode'])->name('export');
        });
        // endregion

        // region User
        Route::prefix('users')->name('users')->group(function () {
            Route::put('/{id}/password', [UserController::class, 'updatePassword'])->name('updatePassword');
            Route::middleware(['auth.admin'])->group(function () {
                Route::get('', [UserController::class, 'index'])->name('index');
                Route::post('', [UserController::class, 'store'])->name('store');
                Route::delete('', [UserController::class, 'delete'])->name('delete');
                Route::get('/{id}', [UserController::class, 'show'])->name('show');
                Route::put('/status/all', [UserController::class, 'updateStatus'])->name('updateStatus');
                Route::put('/{id}', [UserController::class, 'update'])->name('update');
            });
            Route::get('/{id}/profile', [UserController::class, 'getProfileById'])->name('getProfileById');
            Route::put('/{id}/profile', [UserController::class, 'updateProfile'])->name('updateProfile');
        });
        // endregion

        // region Config
        Route::prefix('configs')->name('configs')->group(function () {
            Route::middleware(['auth.admin'])->group(function () {
                Route::get('', [ConfigController::class, 'index'])->name('index');
                Route::put('/{entity_id}', [ConfigController::class, 'update'])->name('update');
            });
            Route::get('/status', [ConfigController::class, 'getStatus'])->name('getStatus');
        });
        // endregion

        // region Report
        Route::prefix('reports')->name('reports')->group(function () {
            Route::get('/codes/activated', [ReportController::class, 'getActivatedCode'])->name('getActivatedCode');
            Route::get('/transaction/issue', [ReportController::class, 'getTransactionFailAndCompleted'])->name('getTransactionFailAndCompleted');
            Route::get('/transaction/day', [ReportController::class, 'getTransactionToday'])->name('getTransactionToday');
            Route::get('/customers', [ReportController::class, 'getCustomerReport'])->name('getCustomerReport');
            Route::get('/customers/{id}', [ReportController::class, 'showCustomer'])->name('showCustomer');
            Route::get('/zone', [ReportController::class, 'getZoneReport'])->name('getZoneReport');
        });
        // endregion

        // region Customer
        Route::prefix('customers')->name('customers')->group(function () {
            Route::get('', [CustomerController::class, 'index'])->name('index');
//            Route::delete('', [CustomerController::class, 'deleteList'])->name('deleteList');
            Route::get('/{id}', [CustomerController::class, 'show'])->name('show');
//            Route::put('/{id}', [CustomerController::class, 'update'])->name('update');
//            Route::delete('/{id}', [CustomerController::class, 'delete'])->name('delete');
//            Route::patch('/{id}/status', [CustomerController::class, 'updateStatus'])->name('updateStatus');
            Route::patch('/{id}/approve', [CustomerController::class, 'approveNewCustomer'])->name('approveNewCustomer');
            Route::post('/import', [CustomerController::class, 'fileImport'])->name('file-import');
        });
        // endregion

        // region Zone
        Route::prefix('zones')->middleware(['auth.admin'])->name('zones')->group(function () {
            Route::get('', [ZoneController::class, 'index'])->name('index');
            Route::post('', [ZoneController::class, 'store'])->name('store');
            Route::delete('', [ZoneController::class, 'delete'])->name('delete');
            Route::get('/{id}', [ZoneController::class, 'show'])->name('show');
            Route::get('/{id}/provinces', [ZoneController::class, 'getProvinceById'])->name('getProvinceById');
            Route::put('/{id}/provinces', [ZoneController::class, 'update'])->name('update');
        });
        // endregion

        // region Transaction
        Route::prefix('transactions')->name('transactions')->group(function () {
            Route::post('/redeem/retry', [TransactionController::class, 'redeemTranFailAndNotCompleted'])->name('redeemTranFailAndNotCompleted');
            Route::delete('/{id}', [TransactionController::class, 'delete'])->name('delete');
        });
        // endregion
    });

    // region Reward
    Route::prefix('enduser')->group(function () {
        Route::middleware(['unblocked-ip', 'valid-campaign'])->group(function () {
            Route::prefix('phone')->name('phone')->group(function () {
                Route::post('/create', [CustomerRewardPage::class, 'store'])->name('store');
                Route::post('/check', [CustomerRewardPage::class, 'checkPhoneNumber'])->name('checkPhoneNumber');
                Route::middleware(['unblocked-otp'])->group(function () {
                    Route::post('/otp', [OtpController::class, 'sendOTP'])->name('sendOTP');
                    Route::post('/validate', [RedeemController::class, 'verifyOTP'])->name('verifyOTP');
                });
            });
            Route::prefix('redeem')->name('redeem')->group(function () {
                Route::post('/retry', [RedeemController::class, 'tryGetReward'])->name('tryGetReward');
            });
            Route::post('/giftcode/check', [GiftCodeController::class, 'checkCode'])->name('checkCode');
        });
    });
    // endregion

    // region Third Party
    Route::prefix('3rd')->name('3rd')->middleware(['whitelist-ip'])->group(function () {
        Route::post('/login', [ThirdPartyLoginController::class, 'login'])->name('login');
        Route::middleware(['auth:sanctum', 'system-user', 'valid-campaign', 'unblocked-system-otp'])->group(function () {
            Route::post('/nap-the', [ThirdPartyRedeemController::class, 'checkAndCreateTransaction'])->name('checkAndCreateTransaction');
            Route::post('/redeem', [ThirdPartyRedeemController::class, 'getReward'])->name('getReward');
        });
    });
    // endregion

    Route::get('/provinces', [ProvinceController::class, 'index'])->name('index');
});
