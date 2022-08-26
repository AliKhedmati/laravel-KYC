<?php

namespace Alikhedmati\Kyc\Contracts;

interface Factory
{
    public function driver($driver = null);
}