<?php

namespace Alikhedmati\Kyc\Facades;

use Illuminate\Support\Facades\Facade;

class KycFacade extends Facade
{
    public static function getFacadeAccessor(): string
    {
        return 'Kyc';
    }
}