<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Cache; 


// Coin slot receiver endpoint
Route::post('/coin_inserted', [PaymentController::class, 'CoinInserted']);

