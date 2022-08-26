<?php

namespace Alikhedmati\Kyc\Facades;

use Alikhedmati\Kyc\Contracts\Factory;
use Illuminate\Support\Facades\Facade;

class Kyc extends Facade
{
    public static function getFacadeAccessor(): string
    {
        return Factory::class;
    }
}