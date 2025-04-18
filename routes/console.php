<?php

use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Process\Process; 

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    $receivedFiles = public_path('storage/received_files');
    $files = glob("$receivedFiles/*");

    if($files) return redirect('uploaded_files');  
    
})->everySecond();
    