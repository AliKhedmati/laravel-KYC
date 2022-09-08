<?php

namespace Alikhedmati\Kyc\Drivers\Finnotech\Authentication;

use Alikhedmati\Kyc\Drivers\Finnotech\Factory;
use Alikhedmati\Kyc\Exceptions\KycException;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class Authentication extends Factory
{

    /**
     * @return Collection
     * @throws GuzzleException
     * @throws KycException
     */

    public function getAccessTokens(): Collection
    {
        /**
         * Set Scope.
         */

        $this->setScope('boomrang:tokens:get');

        /**
         * Make Request.
         */

        $request = $this->client(true)->get('dev/v2/clients/' . $this->getClientId() . '/tokens');

        /**
         * handle request failures.
         */

        if ($request->getStatusCode() != 200){

            throw new KycException(json_decode($request->getBody()->getContents())->error->message);

        }

        /**
         * Cast And Return.
         */

        return collect(json_decode($request->getBody()->getContents())->result);
    }
    
    /**
     * @param string|null $requiredScope
     * @return string
     * @throws GuzzleException
     * @throws KycException
     */

    public function getAccessToken(string $requiredScope = null): string
    {
        try {

            /**
             * Seek cache.
             */

            $cachedAccessToken = Cache::get('kyc.finnotech.access-token');

            if (!$cachedAccessToken) {

                throw new KycException('NotFound');

            }

            $cachedAccessToken = json_decode($cachedAccessToken);

            /**
             * Check if access token has requiredScopes or not.
             */

            if ($requiredScope && $cachedAccessToken->scopes) {

                if (!collect($cachedAccessToken->scopes)->contains($requiredScope)) {

                    throw new KycException('Scope');

                }

            }

            /**
             * Check if access token has valid expiration time or not.
             */

            $diffInHours = now()->diffInHours($cachedAccessToken->isValidUntil);

            /**
             * Logic: Cached access token should have at least N hours of expiration time.
             * else, We will call refresh token and return the new access token.
             */

            if ($diffInHours < config('kyc.drivers.finnotech.refresh-token-margin')) {

                throw new KycException('Expiration', $cachedAccessToken->refreshToken);

            }

            /**
             * Cached access token was all good!
             */

            return $cachedAccessToken->value;

        } catch (KycException $kycException){

            if (in_array($kycException->getMessage(), ['NotFound', 'Scope'])){

                $accessToken = $this->createAccessToken();

            }

            elseif ($kycException->getMessage() === 'Expiration') {

                $refreshedAccessToken = $this->refreshAccessToken($kycException->getCode());

                $accessToken = $cachedAccessToken;
                $accessToken['value'] = $refreshedAccessToken->value;
                $accessToken['isValidUntil'] = now()->addMilliseconds($refreshedAccessToken->lifeTime);

            }

            else {

                throw new KycException($kycException->getMessage());

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
             * Return.
             */

            return $accessToken['value'];
        }
    }

    /**
     * @return string
     * @throws KycException
     */

    public function getAuthenticationString(): string
    {
        /**
         * Generate and return AuthenticationString.
         */

        return base64_encode($this->getClientId() . ':' . $this->getClientPassword());
    }

    /**
     * @return Collection
     * @throws KycException
     * @throws GuzzleException
     */

    public function createAccessToken(): Collection
    {
        /**
         * Make request.
         */

        $request = $this->client()->post('dev/v2/oauth2/token', [
            'json'  =>  [
                'grant_type'    =>  'client_credentials',
                'nid'   =>  $this->getClientNationalCode(),
                'scopes'    =>  $this->getClientScopes(),
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
}