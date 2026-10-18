<?php

use App\Http\Controllers\Api\LecturaIotController;
use Illuminate\Support\Facades\Route;

Route::post('/iot/tachos/{tacho:codigo}/lecturas', [LecturaIotController::class, 'store'])
    ->middleware('throttle:30,1')
    ->name('iot.tachos.lecturas');
