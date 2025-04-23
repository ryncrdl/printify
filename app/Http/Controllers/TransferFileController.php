<?php

namespace App\Http\Controllers;

use App\Models\File;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use PhpParser\Node\Stmt\TryCatch;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Redirect;

use Smalot\PdfParser\Parser as PdfParser;
use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;

class TransferFileController extends Controller
{
    public function uploadFiles(Request $request){
        $files = $request->files;

        if(empty($files)) return response()->json(['message' => 'Uploading file is required.']);

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

    public function uploadedFiles(Request $request){
        $agent = $request->header('User-Agent');
        $is_mobile = $this->isMobile($agent);

        if ($is_mobile) {
            return response()->json(['redirect' => '/uploader'], 200);
        }

        $receivedFiles  = public_path('storage/received_files');
        $files = glob("$receivedFiles/*");

        $data = [];
        $page_data = [];
      
        foreach($files as $file){
            $file_name  = basename($file);
            $path       = asset("storage/received_files/$file_name");

            $extension = pathinfo($file, PATHINFO_EXTENSION);

            if (strtolower($extension) === 'pdf') {
                $parser = new PdfParser();
                $pdf    = $parser->parseFile($file);
                $pages = count($pdf->getPages());
            } elseif (in_array(strtolower($extension), ['doc', 'docx'])) {
                $phpWord = WordIOFactory::load($file);
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

            $data[] = [
                'file_name'     => $file_name,
                'path'          => $path,
            ];

            $page_data[] = [
                'file_name'     => $file_name,
                'path'          => $path,
                'total_page'    => $pages
            ];
        }

        $transaction = File::whereIn('status', ['unpaid', 'paid'])->first();

        return response()->json(['files' => $data, 'page_data' => $page_data, 'transaction' => $transaction], 200);
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
                    'price' => (string) $transaction->price
                ]);
            DB::commit();
            return response()->json(['message' => 'Price updated successfully.']);
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

}
