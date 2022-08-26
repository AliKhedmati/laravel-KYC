<?php

namespace Alikhedmati\Kyc;

use Alikhedmati\Kyc\Contracts\Factory;
use Alikhedmati\Kyc\Drivers\Finnotech\Finnotech;
use Illuminate\Support\Manager;

class Kyc extends Manager implements Factory
{
    /**
     * @param $driver
     * @return mixed
     */

    public function provider($driver = null): mixed
    {
        return $this->driver($driver);
    }

    /**
     * @return Finnotech
     */

    public function createFinnotechDriver(): Finnotech
    {
        return new Finnotech();
    }

    /**
     * @return string
     */

    public function getDefaultDriver(): string
    {
        return $this->config->get('kyc.default-driver');
    }
}