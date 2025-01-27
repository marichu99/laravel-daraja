<?php

namespace App\Http\Controllers;

use App\Services\MpesaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;


class MpesaController extends Controller
{
    protected $mpesaService;

    // Injecting the MpesaService class
    public function __construct(MpesaService $mpesaService)
    {
        $this->mpesaService = $mpesaService;
    }

    // Display the phone and amount form
    public function showForm()
    {
        return view('trial');
    }

    // Handle the form submission
    public function submitForm(Request $request)
    {
        // Validate the incoming request
        $validated = $request->validate([
            'phone' => 'required|regex:/^254\d{9}$/',
            'amount' => 'required|numeric|min:1',
        ]);

        try {

            // Call the Mpesa service to initiate the STK push
            $response = $this->mpesaService->stkPushSimulation($request->phone, $request->amount);
            

            Log::channel('stderr')->info('The Amount!', $response);

            // Check if the response is successful and provide feedback
            if (isset($response['errorMessage'])) {
                return redirect('/')->withErrors(['msg' => $response['errorMessage']]);
            }

            // If successful, return a success message
            return redirect('/')->with('success', 'Payment request was successful.');
        } catch (\Exception $e) {
            Log::channel('stderr')->info('Log to console!'. $e->getMessage());
            // Handle any exception and provide feedback
            return redirect('/')->withErrors(['msg' => 'An error occurred: '. $e->getMessage()]);
        }
    }

    public function newEndpoint()
    {
        // You can customize this method to return a view or handle any logic
        return response()->json(['message' => 'This is the new /path endpoint!']);
    }
}
