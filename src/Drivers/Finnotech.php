<?php

namespace Alikhedmati\Kyc\Drivers;

use GuzzleHttp\Client;

class Finnotech
{
    /**
     * @var string
     */

    private string $restApiBase;

    public function __construct()
    {
        $this->restApiBase = config('kyc.drivers.finnotech.base-url');
    }

    public function getAccessToken(): string
    {
        /**
         * The Strategy.
         */
    }

    /**
     * @param bool $isAuthenticated
     * @return Client
     */

    private function client(bool $isAuthenticated = false): Client
    {
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        if ($isAuthenticated){

            $headers['Authorization'] = $this->getAccessToken();

        }

        return new Client([
            'base_uri'  =>  $this->restApiBase,
            'headers'   =>  $headers,
            'http_errors'   =>  false
        ]);
    }
}