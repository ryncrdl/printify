<?php

namespace App\Http\Controllers;

use App\Models\File;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;
use PhpParser\Node\Stmt\TryCatch;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\Process\Process;

use Illuminate\Support\Facades\Redirect;
use Smalot\PdfParser\Parser as PdfParser;
use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;

use Illuminate\Support\Facades\File as FileFacade; 
use Illuminate\Support\Str;

class TransferFileController extends Controller{    
    public function uploadFiles(Request $request){
        $files = $request->files;

        if(empty($files)) return response()->json(['message' => 'Uploading file is required.'], 500);

        $is_process =  File::whereIn('status', ['unpaid', 'paid'])->get();

        if(count($is_process) >= 1){
            return response()->json(['message' => 'Sorry! The system is processing'], 500);
        }

        $transaction_id = $this->transaction_id();

        $files_path = [];
        $absolute_paths = [];

        foreach($files as $file){
            $folder = public_path('storage/received_files');
            $absolute_path = public_path($folder);

            $file_name = $file->getClientOriginalName();

            $file->move($folder, $file_name);

            $path = asset("$folder/$file_name");

            $files_path[] = $path;
            $absolute_paths[] = $absolute_path;
        }

        $is_many = count($files) > 0;
        $message = $is_many ? "Files have" : "File has";

        $transaction = [
            'transaction_id' => $transaction_id,
            'files'          => json_encode($files_path),
            'pages'          => "0",
            "size"           => "Long",
            "color"          => "Colored",
            "price"          => "0.00",
            'status'         => 'unpaid'
        ];

        try {
            DB::beginTransaction();
            File::create($transaction);
            DB::commit();
    
            return response()->json(['message' => "$message been uploaded successfully."]);
        } catch (\Exception $e) {

            foreach ($absolute_paths as $path) {
                if (file_exists($path)) {
                    unlink($path);
                }
            }

            DB::rollBack();
            return response()->json(['message' => 'An error occurred.', 'error' => $e->getMessage()], 500);
        }
    }
    public function uploadedFiles(Request $request)
    {
        $agent = $request->header('User-Agent');
        $is_mobile = $this->isMobile($agent);
    
        if ($is_mobile) {
            return response()->json(['redirect' => '/uploader'], 200);
        }
    
        $ftp_server     = "ftp.printify.icu";
        $ftp_user_name  = "u948461357.printify2025";
        $ftp_user_pass  = "Printify_2025";
        $remote_dir     = 'public/storage/received_files';
        $local_dir      = public_path('storage/received_files');
    
        if (!is_dir($local_dir)) {
            mkdir($local_dir, 0755, true);
        }
    
        // 1. Connect to FTP
        $ftp_connection = ftp_connect($ftp_server);
        if (!$ftp_connection) {
            return response()->json(['message' => 'FTP connection failed'], 500);
        }
    
        if (!ftp_login($ftp_connection, $ftp_user_name, $ftp_user_pass)) {
            ftp_close($ftp_connection);
            return response()->json(['message' => 'FTP login failed'], 500);
        }
    
        ftp_pasv($ftp_connection, true);
    
        // 2. Change to correct directory
        if (!ftp_chdir($ftp_connection, $remote_dir)) {
            ftp_close($ftp_connection);
            return response()->json(['message' => "Failed to change to directory: $remote_dir"], 500);
        }
    
        // 3. Get file list (relative path)
        $files = ftp_nlist($ftp_connection, ".");
    
        if ($files !== false && count($files) > 0) {
            foreach ($files as $file) {
                $file_name = basename($file);
                $local_file = $local_dir . DIRECTORY_SEPARATOR . $file_name;
    
                if (file_exists($local_file)) {
                    Log::info("File '{$file_name}' already exists locally. Skipping download.");
                    continue;
                }
    
                // Download the file
                if (ftp_get($ftp_connection, $local_file, $file, FTP_BINARY)) {
                    Log::info("File '{$file}' downloaded successfully.");
                    if (ftp_delete($ftp_connection, $file)) {
                        Log::info("File '{$file}' deleted from FTP server.");
                    } else {
                        Log::warning("Failed to delete file '{$file}' from FTP server.");
                    }
                } else {
                    Log::error("Failed to download file '{$file}'.");
                }
            }
        }
    
        ftp_close($ftp_connection);
    
        // 4. Load local files
        $local_files = glob($local_dir . DIRECTORY_SEPARATOR . '*');
    
        // 5. Parse local files
        $data = [];
        $page_data = [];
    
        foreach ($local_files as $file) {
            $file_name = basename($file);
            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            $pages = 0;
    
            try {
                if ($extension === 'pdf') {
                    $parser = new \Smalot\PdfParser\Parser();
                    $pdf = $parser->parseFile($file);
                    $pages = count($pdf->getPages());
                } elseif (in_array($extension, ['doc', 'docx'])) {
                    $phpWord = \PhpOffice\PhpWord\IOFactory::load($file);
                    $text = '';
                    foreach ($phpWord->getSections() as $section) {
                        foreach ($section->getElements() as $element) {
                            if (method_exists($element, 'getText')) {
                                $text .= $element->getText();
                            }
                        }
                    }
                    $pages = substr_count($text, "\f") + 1;
                }
            } catch (\Exception $e) {
                Log::error("Error reading file '{$file_name}': " . $e->getMessage());
            }
    
            $url = asset("storage/received_files/$file_name");
    
            $data[] = [
                'file_name' => $file_name,
                'path' => $url,
            ];
    
            $page_data[] = [
                'file_name' => $file_name,
                'path' => $url,
                'total_page' => $pages,
            ];
        }
    
        $transaction = File::whereIn('status', ['unpaid', 'paid'])->first();
    
        return response()->json([
            'files' => $data,
            'page_data' => $page_data,
            'transaction' => $transaction,
        ], 200);
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

    public function isMobile($agent)
    {
        return preg_match('/mobile|android|iphone|ipad|ipod|opera mini|iemobile|blackberry/i', $agent);
    }

    public function updatePrice(Request $request)
    {
        $transaction = (object) $request->input('params');
    
        try {
            DB::beginTransaction();
            File::where('transaction_id', $transaction->transaction_id)
                ->update([
                    'pages' => (string) $transaction->pages,
                    'price' => (string) $transaction->price,
                    'size' => (string) $transaction->size,
                    'color' => (string) $transaction->color
                ]);

            $data =  File::where('transaction_id', $transaction->transaction_id)->first();
            DB::commit();
            return response()->json(['message' => 'Price updated successfully.', 'data' => $data]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }
    

    public function transaction_id(){
        $files = File::all(); 
        $countFiles = count($files) + 1;
    
        $date = date('Ymd'); 
        $transactionId = $date . '-' . str_pad($countFiles, 6, '0', STR_PAD_LEFT);
    
        return $transactionId;
    }

    public function Print(Request $request){
        try {
            $transaction = (object) $request->input('transaction');

            $destination_folder = 'C:/Users/kaye/Desktop/printifty-ready-for-print';
            $files              = json_decode($transaction->files);
            $public_path        = public_path('/storage/received_files');
            $size               = $transaction->size;
            $color              = $transaction->color;
            $transaction_id     = $transaction->transaction_id;

            $sizeLetter = strtoupper($size) === 'Long' ? 'L' : 'S';
            $colorLetter = strtoupper($color) === 'Colored' ? 'C' : 'B';

            if (!FileFacade::exists($destination_folder)) {
                FileFacade::makeDirectory($destination_folder, 0777, true);
            }

            foreach($files as $index => $file){

                $file_name      = basename($file);
                $source_path    = public_path("storage/received_files/$file_name");

                if (!file_exists($source_path)) {
                    return response()->json(['message' => "File not found: {$source_path}"], 500);
                }

                $extension  = pathinfo($file_name, flags: PATHINFO_EXTENSION);
                $new_name   = "$transaction_id" . "_" . "$index" . "_" . "$sizeLetter$colorLetter" . "." . "$extension";

                $destination_path = "$destination_folder/$new_name";

                if (!file_exists($source_path)) {
                    return response()->json(['message' => "File not found: {$source_path}"], 500);
                }
             
                if (!rename($source_path, $destination_path)) {
                    return response()->json(['message' => "Failed to move {$file_name}"], 500);
                }
            }
        
            DB::beginTransaction();
            File::where('transaction_id', $transaction_id)->update(['status' => 'done']);
            DB::commit();
      
            return response()->json(['message' =>  "Your' files is already to print."], 200);
        } catch (\Throwable $th) {
            return response()->json(['message' =>  $th->getMessage() ], 500);
        }
    }

}
