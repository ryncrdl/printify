<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;

use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Redirect;
use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;

class TransferFileController extends Controller
{

    public function uploadedFiles(){
        $receivedFiles  = public_path('storage/received_files');
        $files = glob("$receivedFiles/*");

        $data = [];
        foreach($files as $file){
            $file_name  = basename($file);
            $path       = asset("storage/received_files/$file_name");

            $data[] = [
                'file_name' => $file_name,
                'path'      => $path
            ];
        }

        return response()->json(['files' => $data], 200);
      
    }

    public function receiveFile()
    {
        $scriptPath     = base_path('public/scripts/ReceiveBluetooth.ps1');
        $receivedFiles  = public_path('storage/received_files');
        $vbscript       = public_path('scripts/LaunchHidden.vbs');
        $files = glob("$receivedFiles/*");

        if (!file_exists($vbscript)) {
            \Log::error("Bluetooth script not found: " . $vbscript);
            return response()->json(['status' => 'error', 'message' => 'Script not found']);
        }

        if($files) return response()->json(['redirect' => route('uploaded_files')]);

    
        pclose(popen("start \"\" \"$vbscript\"", "r"));
        Log::info("Bluetooth transfer started");

        return response()->json(['message' => 'Waiting to send files via Bluetooth.'], 200);
       
    }
}
