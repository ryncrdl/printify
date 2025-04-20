<?php

use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Inspiring;
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
    