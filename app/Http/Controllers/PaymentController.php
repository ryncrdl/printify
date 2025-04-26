<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\Payment;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;
use PhpParser\Node\Stmt\TryCatch;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;

use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Redirect;
use Smalot\PdfParser\Parser as PdfParser;
use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;

class PaymentController extends Controller
{
    public function CoinInserted(Request $request)
    {
        // Get the incoming amount from the request
        $amount = $request->input('amount');
        
        // Log the received amount for debugging
        Log::info('Received Coin:', ['amount' => $amount]);
        
        // Retrieve existing coins from the cache, defaulting to 0 if none exist
        $currentAmount = Cache::get('set_coin', 0);
        
        // Add the new coin to the current total
        $newTotal = $currentAmount + $amount;
        
        // Store the updated total back into the cache
        Cache::put('set_coin', $newTotal, now()->addSeconds(10)); // Cache for 10 seconds
        
        return response()->json(['message' => 'Coin received', 'new_total' => $newTotal]);
    }

    public function createQRPayment(Request $request)
    {
        $api_key            = env('PAYMONGO_SECRET_KEY');
        $transaction        = (object) $request->input('transaction');
        $amount             = (float) $transaction->price;  //Only above or equal to 100;
        $transaction_id     = $transaction->transaction_id;

        $body = [
            'data' => [
                'attributes' => [
                    'amount' => $amount * 100,  
                    'description' => 'Online Payment',
                    'remarks' => $transaction_id,  
                ]
            ]
        ];

        $response = Http::withHeaders([
            'accept' => 'application/json',
            'Authorization' => 'Basic ' . base64_encode("{$api_key}:"),
            'Content-Type' => 'application/json',
        ])
        ->post('https://api.paymongo.com/v1/links', $body);

        if ($response->successful()) {
            $data = (object) $response->json();
            $data = (object) $data->data;

            $payment = [
                'transaction_id' => $transaction_id,
                'payment_id'     => $data->id,
                'attributes'     => json_encode($data->attributes)
            ];

            Payment::create($payment);

            return response()->json(['data' => $data]);
        } else {

            return response()->json([
                'error' => 'Error creating payment link',
                'details' => $response->body()
            ], 500);
        }
    }

    public function getPaymentStatus(Request $request){
        try {
            $api_key            = env('PAYMONGO_SECRET_KEY');
            $payment_id         = $request->input('payment_id');
            
            $response = Http::withHeaders([
                'accept' => 'application/json',
                'authorization' => 'Basic ' . base64_encode("{$api_key}:"),
            ])
            ->get("https://api.paymongo.com/v1/links/$payment_id");
    
            $data       = $response->json();
            $data       = (object) $data['data'];
    
            $attributes         = (object) $data->attributes;
            $status             = $attributes->status;
            $transaction_id     = $attributes->remarks;
            $amount             = (float) $attributes->amount / 100;    

            $response_data     = [
                'status'            => $status,
                'amount'            => $amount,
                'transasction_id'   => $transaction_id
            ];
        
            if($status === 'paid'){
                DB::beginTransaction();
                File::where('transaction_id', $transaction_id)->update(['status' => 'paid']);
                DB::commit();
            }
          
            return response()->json(['data' => $response_data], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }



    


    public function updateStatus(Request $request){
        try {
            $data           = (object) $request->input('params');
            $transaction_id = $data->transaction_id;
            $status         = $data->status;

            DB::beginTransaction();;
            File::where('transaction_id', $transaction_id)->update(['status' => $status]);
            DB::commit();

            return response()->json(['message' => "Required Amount has been paid"], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }

}
