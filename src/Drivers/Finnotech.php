<?php

namespace Alikhedmati\Kyc\Drivers;

use Alikhedmati\Kyc\Exceptions\KycException;
use GuzzleHttp\Client;
use Illuminate\Support\Collection;

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

    public function getAccessToken(): string{}

    /**
     * @return string
     * @throws KycException
     */

    protected function getAuthenticationString(): string
    {
        /**
         * Fetch client-id and client-password.
         */

        $clientId = config('kyc.drivers.finnotech.client-id');

        $clientPassword = config('kyc.drivers.finnotech.client-password');

        if (!$clientId || !$clientPassword){

            throw new KycException(trans('kyc::errors.clientIdOrPasswordMissing'));

        }

        /**
         * Generate and return AuthenticationString.
         */

        return base64_encode($clientId . ':' . $clientPassword);
    }


    public function authenticate(array $scopes): Collection
    {


        /**
         * Make request.
         */

        $request = $this->client()->post('dev/v2/oauth2/token', [
            'json'  =>  [

            ],
            'headers'   =>  [
                'Authorization' =>  'Basic '. $this->getAuthenticationString()
            ],
        ]);

        /**
         * Handle request failures.
         */

        if ($request->getStatusCode() != 200){

            throw new KycException(json_decode($request->getBody()->getContents())->error->message);

        }
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