<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\WalletController;

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
            
            Route::middleware(['auth:sanctum', 'ability:admin'])->group(function () {
                Route::post('wallet/fund', [WalletController::class, 'fund'])->name('wallet.fund');
            });

        });
    });


    Route::name('user.')->group(function () {
        Route::post('/signup', [AuthController::class, 'signup'])->name('signup');
        Route::post('/login', [AuthController::class, 'signin'])->name('signin');
        Route::post('/email/verify', [AuthController::class, 'verifyEmail'])->name('email.verify');

        Route::middleware(['auth:sanctum', 'ability:user'])->group(function () {
            Route::post('wallet/withdraw', [WalletController::class, 'withdraw'])->name('wallet.withdraw');
        });
    });
});
