<?php

namespace Alikhedmati\Kyc\Contracts;

interface Factory
{
    public function provider($provider = null);
}