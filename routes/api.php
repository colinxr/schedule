<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ConversationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Intake Form Routes
Route::post('conversations', [ConversationController::class, 'store']);
Route::get('conversations/{conversation}', [ConversationController::class, 'show']); 
