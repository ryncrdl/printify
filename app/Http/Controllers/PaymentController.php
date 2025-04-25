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

}
