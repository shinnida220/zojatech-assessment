<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\AccountManagementController;
use App\Http\Controllers\InviteController;

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

Route::group(['middleware' => ['cors', 'json.response']], function () {

    Route::prefix('admin')->group(function () {
        Route::name('admin.')->group(function () {
            Route::post('/login', [AuthController::class, 'signin'])->name('signin');
            Route::post('/logout', [AuthController::class, 'signout'])->name('signout');
            
            Route::middleware(['auth:sanctum', 'ability:admin'])->group(function () {
                Route::post('wallet/fund', [WalletController::class, 'fund'])->name('wallet.fund');
                
                Route::put('account/ban', [AccountManagementController::class, 'ban'])->name('account.ban');
                Route::put('account/unban', [AccountManagementController::class, 'unban'])->name('account.unban');
                Route::put('account/promote', [AccountManagementController::class, 'promote'])->name('account.promote');
                Route::put('account/demote', [AccountManagementController::class, 'demote'])->name('account.demote');

                Route::post('/invite', [InviteController::class, 'invite'])->name('invite.single');
                Route::post('/invite-multiple', [InviteController::class, 'invite'])->name('invite.multiple');
            });
        });
    });


    Route::name('user.')->group(function () {
        Route::post('/signup', [AuthController::class, 'signup'])->name('signup');
        Route::post('/login', [AuthController::class, 'signin'])->name('signin');
        Route::post('/logout', [AuthController::class, 'signout'])->name('signout');
        Route::post('/email/verify', [AuthController::class, 'verifyEmail'])->name('email.verify');

        Route::middleware(['auth:sanctum', 'ability:user'])->group(function () {
            Route::post('wallet/withdraw', [WalletController::class, 'withdraw'])->name('wallet.withdraw');
        });
    });
});
