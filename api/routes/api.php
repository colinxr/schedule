<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ConversationController;
use App\Http\Controllers\Api\GoogleCalendarController;
use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\WorkScheduleController;
use App\Http\Controllers\Api\AvailabilityController;
use App\Http\Controllers\Api\Artist\ClientController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Auth routes
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

Route::post('conversations', [ConversationController::class, 'store']);

Route::middleware('auth:sanctum')->group(function () {
    // Auth routes that require authentication
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('me', [AuthController::class, 'me']);

    // Existing routes
    Route::get('conversations', [ConversationController::class, 'index']);
    Route::get('conversations/{conversation}', [ConversationController::class, 'show']);
    
    Route::get('appointments', [AppointmentController::class, 'index']);
    Route::get('appointments/{appointment}', [AppointmentController::class, 'show']);
    Route::post('appointments', [AppointmentController::class, 'store']);
    Route::put('appointments/{appointment}', [AppointmentController::class, 'update']);
    Route::delete('appointments/{appointment}', [AppointmentController::class, 'destroy']);
    Route::patch('appointments/{appointment}/price', [AppointmentController::class, 'updatePrice']);
    Route::patch('appointments/{appointment}/deposit', [AppointmentController::class, 'updateDeposit']);
    Route::patch('appointments/{appointment}/deposit/toggle-paid', [AppointmentController::class, 'toggleDepositPaid']);

    // Google Calendar routes
    Route::prefix('google')->group(function () {
        Route::get('connect', [GoogleCalendarController::class, 'connect']);
        Route::get('callback', [GoogleCalendarController::class, 'callback']);
        Route::post('disconnect', [GoogleCalendarController::class, 'disconnect']);
        Route::get('status', [GoogleCalendarController::class, 'status']);
    });

    // Work Schedule Routes
    Route::get('/schedule', [WorkScheduleController::class, 'index']);
    Route::post('/schedule', [WorkScheduleController::class, 'store']);
    Route::put('/schedule/{workSchedule}', [WorkScheduleController::class, 'update']);
    Route::delete('/schedule/{workSchedule}', [WorkScheduleController::class, 'destroy']);

    // Artist Availability
    Route::get('/artists/{artist}/available-slots', [AvailabilityController::class, 'getAvailableSlots'])
        ->name('artists.available-slots')
        ->middleware(['throttle:30,1', 'cache.headers:public;max_age=3600;etag']);

    Route::get('/client/{client}', [ClientController::class, 'show'])
        ->name('api.artist.clients.show');
});