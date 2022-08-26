<?php

return [
    'default-driver'    =>  env('KYC_DEFAULT_DRIVER', 'finnotech'),
    'drivers' =>  [
        'finnotech' =>  [
            'base-url'  =>  env('KYC_FINNOTECH_BASE_URL', 'https://apibeta.finnotech.ir/'),
            'client-id' =>  env('KYC_FINNOTECH_CLIENT_ID'),
            'client-password' =>  env('KYC_FINNOTECH_CLIENT_PASSWORD'),
            'client-national-code'  =>  env('KYC_FINNOTECH_CLIENT_NATIONAL_CODE'),
            'scopes'    =>  env('KYC_FINNOTECH_SCOPES'),
            'refresh-token-margin'  =>  env('KYC_FINNOTECH_REFRESH_TOKEN_MARGIN', 10), // In Hours
        ],
    ],
];