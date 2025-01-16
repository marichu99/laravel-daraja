<?php

use App\Http\Controllers\MpesaController;
use App\Services\MpesaService;
use Illuminate\Support\Facades\Route;

Route::get('/', [MpesaController::class, 'showForm']);
Route::post('/submit-phone-amount', action: [MpesaController::class, 'submitForm']);
Route::post('/path', [MpesaService::class, 'path'])->name('path');

