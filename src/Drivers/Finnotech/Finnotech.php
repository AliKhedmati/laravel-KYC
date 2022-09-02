<?php

namespace Alikhedmati\Kyc\Drivers\Finnotech;

use Alikhedmati\Kyc\Contracts\Driver;

class Finnotech extends Base implements Driver
{
    /**
     * @return Authentication\Authentication
     */

    public function authentication(): Authentication\Authentication
    {
        return new Authentication\Authentication();
    }

    /**
     * @return Banking\Banking
     */

    public function banking(): Banking\Banking
    {
        return new Banking\Banking();
    }

    /**
     * @return Kyc\Kyc
     */

    public function kyc(): Kyc\Kyc
    {
        return new Kyc\Kyc();
    }
}