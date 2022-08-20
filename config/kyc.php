<?php

return [
    'providers' =>  [
        'finnotech' =>  [
            'base-url'  =>  env('KYC_FINNOTECH_BASE_URL', 'https://apibeta.finnotech.ir/'),
            'client-id' =>  env('KYC_FINNOTECH_CLIENT_ID'),
            'client-password' =>  env('KYC_FINNOTECH_CLIENT_PASSWORD'),
        ],
    ],
];