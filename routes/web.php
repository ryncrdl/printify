<?php

use App\Http\Controllers\PaymentController;
use Inertia\Inertia;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Application;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TransferFileController;

Route::get('/', function () {
    $receivedFiles = public_path('storage/received_files');
    $files = glob("$receivedFiles/*");

    if (!empty($files)) {
        return redirect()->route('uploaded_files');
    }

    return Inertia::render('Home');
});

Route::get('/uploaded_files', function () {
    return Inertia::render('UploadedFiles');
})->name('uploaded_files');

Route::get('/uploader', function () {
    return Inertia::render('Uploader');
})->name('uploader');


//Blueooth API
Route::get('/receive_bluetooth', [TransferFileController::class, 'receiveFile']);
Route::get('/get_files', [TransferFileController::class, 'uploadedFiles']);

//QC API
Route::post('/upload_files', [TransferFileController::class, 'uploadFiles']);
Route::post('/update_price', [TransferFileController::class, 'updatePrice']);
Route::post('/create_payment', [TransferFileController::class, 'createPayment']);
Route::post('/paymongo/webhook', [TransferFileController::class, 'handleWebhook']);

//PAYMENTS
Route::get('/payment-success', function () {
    return Inertia::render('PrintPage');
})->name('payment.success');

Route::get('/payment-failed', function () {
    return 'Payment failed. Please try again.';
})->name('payment.failed');

//COINS
// Route::post('/coin_inserted', [PaymentController::class, 'CoinInserted'])->middleware('disableCsrfForCoinRoute');

Route::get('/get_coin', function () {
    $coins = Cache::get('set_coin', 0);
    Cache::forget('set_coin');
    return ['amount' => $coins];
});

// Route::get('/', function () {
//     return Inertia::render('Welcome', [
//         'canLogin' => Route::has('login'),
//         'canRegister' => Route::has('register'),
//         'laravelVersion' => Application::VERSION,
//         'phpVersion' => PHP_VERSION,
//     ]);
// });


// Route::get('/dashboard', function () {
//     return Inertia::render('Dashboard');
// })->middleware(['auth', 'verified'])->name('dashboard');

// Route::middleware('auth')->group(function () {
//     Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
//     Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
//     Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
// });


require __DIR__.'/auth.php';
