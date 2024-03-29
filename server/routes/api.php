<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\Auth\UserController as ApiUserController;

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

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/register', [ApiUserController::class, 'register']);
Route::post('/login', [ApiUserController::class, 'login']);

Route::group(['middleware' => 'jwt.auth'], function () {
    // Itt vannak az autentikált API útvonalak
    Route::middleware('auth:api')->get('/user', function (Request $request) {
        return $request->user();
    });
});

Route::middleware(['auth:api'])->group(function () {
  /*  Route::get('/activeUserDetails', [ApiUserController::class, 'getUserDetails']);
    Route::post('/buyFuel', [ApiUserController::class, 'buyFuel']);
    Route::post('/acceptMission', [ApiUserController::class, 'acceptMission']);
    Route::post('/seenFight', [ApiUserController::class, 'seenFight']);
    Route::patch('/doBattle', [ApiUserController::class, 'doBattle']);
    Route::get('/fightInfo', [ApiUserController::class, 'fightInfo']);
    //equipitem
    Route::post('/equipItem', [ApiUserController::class, 'equipItem']);
    Route::post('/unequipItem', [ApiUserController::class, 'unequipItem']);
    Route::post('/sellItem', [ApiUserController::class, 'sellItem']);
    //Route::post('/buyItem', [ApiUserController::class, 'buyItem']);
*/
});
