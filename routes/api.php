<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\IntakeFormController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Intake Form Routes
Route::post('intake-forms', [IntakeFormController::class, 'store']);
Route::get('conversations/{conversation}', [IntakeFormController::class, 'show']); 