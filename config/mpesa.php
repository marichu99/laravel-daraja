<?php

return [
    'app_key' => env('MPESA_APP_KEY'),
    'app_secret' => env('MPESA_APP_SECRET'),
    'party_b' => env('MPESA_PARTY_B'),
    'stk_password' => env('MPESA_STK_PASSWORD'),
    'stk_timestamp' => env('MPESA_STK_TIMESTAMP'),
    'transaction_type' => env('MPESA_TRANSACTION_TYPE'),
    'callback_url' => env('MPESA_CALLBACK_URL'),
    'account_reference' => env('MPESA_ACCOUNT_REFERENCE'),
    'transaction_desc' => env('MPESA_TRANSACTION_DESC'),
];
