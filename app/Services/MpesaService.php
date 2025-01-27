<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;


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
        // Authenticate and get the access token
        $token = $this->authenticate();
        Log::channel('stderr')->info('Access Token:', ['token' => $token]);

        // Define the STK Push URL
        $url = env('MPESA_API_URL') . '/mpesa/stkpush/v1/processrequest';

        // Generate required parameters
        $shortcode = env('MPESA_BUSINESS_SHORTCODE');
        $passkey = env('MPESA_PASSKEY');
        $timestamp = now()->format('YmdHis');
        $password = $this->generatePassword($shortcode, $passkey, $timestamp);

        // Send the STK Push request
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

        // Decode the response
        $responseBody = $response->json();

        // Log the response for debugging
        Log::channel('stderr')->info('STK Push Response:', $responseBody);

        // Extract the CheckoutRequestID from the response
        $checkoutRequestID = $responseBody['CheckoutRequestID'] ?? null;

        sleep(8);
        if ($checkoutRequestID) {
            // Call the path function recursively after 5 seconds
            $this->callPathRecursively($checkoutRequestID, $token);
        }

        return $responseBody;
    }

    protected function callPathRecursively($checkoutRequestID, $token)
    {
        // Wait for 5 seconds before making the next request
        sleep(3);

        // Call the path function
        $response = $this->path($checkoutRequestID, $token);

        // Log the response for debugging
        Log::channel('stderr')->info('STK Query Response:', $response);

        // check if the transaction is underway
        // If the transaction is not complete, call the function recursively

        $errorCode = $response["errorCode"] ?? null;
        if ($errorCode) {
            $this->callPathRecursively($checkoutRequestID, $token);
        }


        // Check the ResultCode
        $resultCode = $response['ResultCode'] ?? null;
        $resultDesc = $response['ResultDesc'] ?? 'Unknown error';

        if ($resultCode == '0') {
            Log::channel('stderr')->info('Transaction completed successfully.');
            return;
        } else {
            // Throw an exception with the ResultDesc as the error message
            throw new Exception("Transaction failed: " . $resultDesc);
        }


    }

    public function path($checkoutRequestID, $token)
    {
        // Define the STK Push Query URL
        $url = env('MPESA_API_URL') . '/mpesa/stkpushquery/v1/query';

        // Generate required parameters
        $shortcode = env('MPESA_BUSINESS_SHORTCODE');
        $passkey = env('MPESA_PASSKEY');
        $timestamp = now()->format('YmdHis');
        $password = $this->generatePassword($shortcode, $passkey, $timestamp);

        // Send the STK Push Query request
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json',
        ])->post($url, [
                    'BusinessShortCode' => $shortcode,
                    'Password' => $password,
                    'Timestamp' => $timestamp,
                    'CheckoutRequestID' => $checkoutRequestID,
                ]);

        return $response->json();
    }

}
