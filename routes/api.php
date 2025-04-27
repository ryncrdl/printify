<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cache; 
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\TransferFileController;

//upload files
Route::post('/upload_files', [TransferFileController::class, 'uploadFiles']);

// Coin slot receiver endpoint
Route::post('/coin_inserted', [PaymentController::class, 'CoinInserted']);
Route::post('/paymongo/webhook', [PaymentController::class, 'handleWebhook']);

