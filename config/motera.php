<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Supported Banks
    |--------------------------------------------------------------------------
    |
    | Banks offered in the withdrawal form. Replace with the actual
    | destination banks supported for payouts.
    |
    */

    'banks' => [
        ['code' => 'GTBank', 'name' => 'Guaranty Trust Bank'],
        ['code' => 'Zenith', 'name' => 'Zenith Bank'],
        ['code' => 'Access', 'name' => 'Access Bank'],
        ['code' => 'UBA', 'name' => 'United Bank for Africa'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Limits & Fees
    |--------------------------------------------------------------------------
    |
    | Display and validation minimums. Note: tier limits are informational
    | only — they are not enforced by the application at this time.
    |
    */

    'limits' => [
        'min_deposit' => 100,
        'min_withdrawal' => 500,
        'min_transfer' => 100,
        'card_physical_fee' => 1000,
        'tier_limits' => [
            'tier_1' => 50000,
            'tier_2' => 500000,
            'tier_3' => 5000000,
        ],
    ],

];
