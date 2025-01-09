<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FlowController;

Route::get('/', function () {
    return view('app');
});

Route::prefix('api')->group(function () {
    Route::post('flows', [FlowController::class, 'store']);
    Route::get('flows', [FlowController::class, 'index']);
    Route::get('flows/{id}', [FlowController::class, 'show']);
    Route::put('flows/{id}', [FlowController::class, 'update']);
    Route::delete('flows/{id}', [FlowController::class, 'destroy']);
    Route::post('flows/{id}/execute', [FlowController::class, 'execute']);
});
