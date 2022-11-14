<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CustomerController;

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });



Route::post('register',[CustomerController::class,'register']);
Route::post('login',[CustomerController::class,'login']);

Route::middleware('auth:api')->group(function () {

    Route::prefix('customer')->group(function () {

        Route::post('profile',[CustomerController::class,'profile']);
        Route::post('update-profile',[CustomerController::class,'update_profile']);

    });
});