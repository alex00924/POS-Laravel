<?php
/**
 * Created by PhpStorm.
 * User: Piggy
 * Date: 10/6/2018
 * Time: 11:59 AM
 */

return [
    'USD' => env('BRAINTREE_MERCHANT_ACCOUNT_ID'),
    'AUD' => env('BRAINTREE_MERCHANT_ACCOUNT_ID').'-aud',
    'JPY' => env('BRAINTREE_MERCHANT_ACCOUNT_ID').'-yen',
    'EUR' => env('BRAINTREE_MERCHANT_ACCOUNT_ID').'-eur',
    'CAD' => env('BRAINTREE_MERCHANT_ACCOUNT_ID').'-cad',
    'GBP' => env('BRAINTREE_MERCHANT_ACCOUNT_ID').'-gbp',
];