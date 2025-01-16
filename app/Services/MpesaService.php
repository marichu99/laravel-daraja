<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MpesaService
{
    public function authenticate()
    {
        $appKeySecret = env('MPESA_APP_KEY_SECRET');
        Log::channel('stderr')->info('App Key Secret:', ['key' => $appKeySecret]);

        $encoded = base64_encode($appKeySecret);
        Log::channel('stderr')->info('Encoded Key:', ['encoded' => $encoded]);

        $url = env('MPESA_API_URL') . '/oauth/v1/generate?grant_type=client_credentials';

        // Initialize cURL
        $ch = curl_init($url);

        // Set cURL options
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Basic ' . $encoded
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        // Execute the request
        $response = curl_exec($ch);

        // Handle errors
        if (curl_errno($ch)) {
            $errorMessage = curl_error($ch);
            curl_close($ch);
            Log::error('cURL Error', ['error' => $errorMessage]);
            throw new \Exception('Error connecting to Safaricom API: ' . $errorMessage);
        }

        curl_close($ch);

        // Decode the JSON response
        $decodedResponse = json_decode($response, true);

        // Log the response
        Log::channel('stderr')->info('Access Token Response:', ['response' => $decodedResponse]);

        // Return the access token
        return $decodedResponse['access_token'] ?? null;
    }

    public function generatePassword($shortcode, $passkey, $timestamp)
    {
        // Concatenate Shortcode, Passkey, and Timestamp
        $password = $shortcode . $passkey . $timestamp;

        // Base64 encode the password
        $encodedPassword = base64_encode($password);

        Log::channel('stderr')->info('Password:', ['token' => $encodedPassword]);

        return $encodedPassword;
    }

    public function stkPushSimulation($phoneNumber, $amount)
    {
        $token = $this->authenticate();
        Log::channel('stderr')->info('Access Token:', ['token' => $token]);

        $url = env('MPESA_API_URL') . '/mpesa/stkpush/v1/processrequest';

        $shortcode = env('MPESA_BUSINESS_SHORTCODE');
        $passkey = env('MPESA_PASSKEY');
        $timestamp = now()->format('YmdHis');

        $password = $this->generatePassword($shortcode, $passkey, $timestamp);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json',
        ])->post($url, [
                    'BusinessShortCode' => $shortcode,
                    'Password' => $password,
                    'Timestamp' => $timestamp,
                    'TransactionType' => 'CustomerPayBillOnline',
                    'Amount' => $amount,
                    'PhoneNumber' => $phoneNumber,
                    'PartyA' => $phoneNumber,
                    'PartyB' => $shortcode,
                    'CallBackURL' => env('MPESA_CALLBACK_URL'),
                    'AccountReference' => 'Test123',
                    'TransactionDesc' => 'Payment for services',
                ]);

        return $response->json();
    }
    public function path()
    {
        try {
        $data = file_get_contents('php://input');
        // Log the POST data
        Log::channel('stderr')->info('POST Data Received:', ['data' => $data]);

        // Store the data in a file
        Storage::disk('local')->put('stk.txt', $data);

        return response()->json(['message' => 'Data received successfully.']);
        } catch (\Exception $e) {
            Log::channel('stderr')->info('Log to console!'. $e->getMessage());
        }
    }
}
