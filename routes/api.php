<?php

use App\Http\Controllers\Api\ConversationController;
use App\Http\Controllers\Api\GoogleCalendarController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AppointmentController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::post('conversations', [ConversationController::class, 'store']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('conversations', [ConversationController::class, 'index']);
    Route::get('conversations/{conversation}', [ConversationController::class, 'show']);
    
    Route::get('appointments', [AppointmentController::class, 'index']);
    Route::get('appointments/{appointment}', [AppointmentController::class, 'show']);
    Route::post('appointments', [AppointmentController::class, 'store']);
    Route::put('appointments/{appointment}', [AppointmentController::class, 'update']);
    Route::delete('appointments/{appointment}', [AppointmentController::class, 'destroy']);

    // Google Calendar routes
    Route::prefix('google')->group(function () {
        Route::get('connect', [GoogleCalendarController::class, 'connect']);
        Route::get('callback', [GoogleCalendarController::class, 'callback']);
        Route::post('disconnect', [GoogleCalendarController::class, 'disconnect']);
        Route::get('status', [GoogleCalendarController::class, 'status']);
    });
});
