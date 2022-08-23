<?php

namespace Alikhedmati\Kyc\Drivers;

use Alikhedmati\Kyc\Exceptions\KycException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

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

    /**
     * @return string
     * @throws GuzzleException
     * @throws KycException
     */

    public function getAccessToken(): string
    {
        /**
         * Seek cache.
         */

        $cachedAccessToken = Cache::get('kyc.finnotech.access-token');

        if ($cachedAccessToken){

            $cachedAccessToken = json_decode($cachedAccessToken);

            /**
             * Check expiration time.
             */

            $diffInHours = now()->diffInHours($cachedAccessToken->isValidUntil);

            /**
             * Logic: Cached access token should have at least N hours of expiration time.
             * else, We will call refresh token and return the new access token.
             */

            if ($diffInHours > config('kyc.drivers.finnotech.refresh-token-margin')){

                return $cachedAccessToken->value;

            }

            /**
             * Refresh token.
             */

            $newAccessToken = $this->refreshAccessToken($cachedAccessToken->refreshToken);

            /**
             * Update access token.
             */

            $accessToken = $cachedAccessToken;

            $accessToken['value'] = $newAccessToken->value;
            $accessToken['isValidUntil'] = now()->addMilliseconds($newAccessToken->lifeTime);

        }

        /**
         * Access Token Not Found.
         * So We Create It.
         */

        else {

            $accessToken = $this->createAccessToken();

        }

        /**
         * Store access token in cache.
         */

        Cache::put(
            key: 'kyc.finnotech.access-token',
            value: json_encode($accessToken),
            ttl: now()->addDays(10)
        );

        /**
         * Return access token.
         */

        return $accessToken->only('value')->first();

    }

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

            throw new KycException(trans('kyc::errors.requiredDataMissed'));

        }

        /**
         * Generate and return AuthenticationString.
         */

        return base64_encode($clientId . ':' . $clientPassword);
    }

    /**
     * @return Collection
     * @throws KycException
     * @throws GuzzleException
     */

    public function createAccessToken(): Collection
    {
        /**
         * Fetch required parameters.
         */

        $clientNationalCode = config('kyc.drivers.finnotech.client-national-code');

        $scopes = config('kyc.drivers.finnotech.scopes');

        if (!$clientNationalCode || !$scopes){

            throw new KycException(trans('kyc::errors.requiredDataMissed'));

        }

        /**
         * Make request.
         */

        $request = $this->client()->post('dev/v2/oauth2/token', [
            'json'  =>  [
                'grant_type'    =>  'client_credentials',
                'nid'   =>  $clientNationalCode,
                'scopes'    =>  $scopes,
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

        /**
         * Return.
         */

        $result = json_decode($request->getBody()->getContents())->result;

        return collect($result)->put('isValidUntil', now()->addMilliseconds($result->lifeTime));
    }

    /**
     * @param string $refreshToken
     * @return Collection
     * @throws GuzzleException
     * @throws KycException
     */

    public function refreshAccessToken(string $refreshToken): Collection
    {
        /**
         * Make request.
         */

        $request = $this->client()->post('dev/v2/oauth2/token', [
            'json'  =>  [
                'grant_type'    =>  'refresh_token',
                'token_type'    =>  'CLIENT-CREDENTIAL',
                'refresh_token' =>  $refreshToken
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

        /**
         * Return.
         */

        return collect(json_decode($request->getBody()->getContents())->result);
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