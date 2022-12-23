<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

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
        Route::name('admin.')->middleware('auth:sanctum')->group(function () {
            Route::get('/users', function () {
                // Route assigned name "admin.users"...
            })->name('users');
        });
    });


    Route::name('user.')->group(function () {
        Route::post('/signup', [AuthController::class, 'signup'])->name('signup');
        Route::post('/login', [AuthController::class, 'signin'])->name('signin');
        Route::post('/email/verify', [AuthController::class, 'verifyEmail'])->name('email.verify');
    });
});
