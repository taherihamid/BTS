<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GadgetController;

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

Route::group([
    'middleware' => 'api',
    'prefix' => 'auth'
], function ($router) {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::get('/user-profile', [AuthController::class, 'userProfile']);

});
Route::group([
    'middleware' => 'api',
], function ($router) {
    Route::post('/gadget-store', [GadgetController::class, 'store'])->name('gadget_store');
    Route::get('/gadget-list', [GadgetController::class, 'index'])->name('gadget_list');
    Route::get('/gadget-single/{id}', [GadgetController::class, 'show'])->name('gadget_single');
    Route::post('/gadget-single-update',  [GadgetController::class, 'update'])->name('gadget_single_update');
});

