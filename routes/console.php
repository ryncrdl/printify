<?php

use App\Models\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Process\Process; 

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    Log::info("Running auto redirect to uploaded_files");
    $receivedFiles = public_path('storage/received_files');
    $files = glob("$receivedFiles/*");

    if($files) redirect()->route('uploaded_files'); 
})->everySecond();


Schedule::call(function () {
    $transaction = File::where('status', 'pending')->first();

        if($transaction){
            $response = Http::withBasicAuth(env('PAYMONGO_SECRET_KEY'), '')
                ->get("https://api.paymongo.com/v1/sources/{$transaction->paymongo_source_id}");

            if ($response->successful()) {
                $status = $response->json()['data']['attributes']['status'];

                if ($status === 'paid') {
                    $transaction->update(['status' => 'paid']);
                    $this->info("Transaction {$transaction->id} marked as paid.");
                }
            } else {
                $this->error("Failed to check status for transaction {$transaction->id}");
            }
        }   
    }
)->everySecond();
    