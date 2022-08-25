<?php

namespace Alikhedmati\Kyc\Drivers;

use Alikhedmati\Kyc\Exceptions\KycException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class Finnotech
{

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

        $clientId = $this->getClientId();

        $clientPassword = $this->getClientPassword();

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

    protected function createAccessToken(): Collection
    {
        /**
         * Fetch required parameters.
         */

        $clientNationalCode = $this->getClientNationalCode();

        $scopes = $this->getClientScopes();

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

    protected function refreshAccessToken(string $refreshToken): Collection
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
     * @throws GuzzleException
     * @throws KycException
     */

    protected function client(bool $isAuthenticated = false): Client
    {
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        if ($isAuthenticated){

            $headers['Authorization'] = 'Bearer '. $this->getAccessToken();

        }

        return new Client([
            'base_uri'  =>  $this->getRestAPIBase(),
            'headers'   =>  $headers,
            'http_errors'   =>  false
        ]);
    }

    /**
     * @param string $cardNumber
     * @return Collection
     * @throws GuzzleException
     * @throws KycException
     */

    public function getCardInformation(string $cardNumber): Collection
    {
        /**
         * Make request.
         */

        $request = $this->client(true)->get('mpg/v2/clients/' . $this->getClientId() . '/cards/' . $cardNumber, [
            'query' =>  [
                'trackId'   =>  $this->generateTrackId()
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

        /**
         * Todo: Cast the output.
         */

        return collect(json_decode($request->getBody()->getContents())->result);
    }

    /**
     * @return string
     */

    protected function generateTrackId(): string
    {
        return Str::orderedUuid()->toString();
    }

    /**
     * @return string
     * @throws KycException
     */

    protected function getClientId(): string
    {
        $clientId = config('kyc.drivers.finnotech.client-id');

        if (!$clientId){

            throw new KycException(trans('kyc::errors.requiredDataMissed'));

        }

        return $clientId;
    }

    /**
     * @return string
     * @throws KycException
     */

    protected function getClientPassword(): string
    {
        $clientPassword = config('kyc.drivers.finnotech.client-password');

        if (!$clientPassword){

            throw new KycException(trans('kyc::errors.requiredDataMissed'));

        }

        return $clientPassword;
    }

    /**
     * @return string
     * @throws KycException
     */

    protected function getClientNationalCode(): string
    {
        $clientNationalCode = config('kyc.drivers.finnotech.client-national-code');

        if (!$clientNationalCode){

            throw new KycException(trans('kyc::errors.requiredDataMissed'));

        }

        return $clientNationalCode;
    }

    /**
     * @return string
     * @throws KycException
     */

    protected function getClientScopes(): string
    {
        $clientScopes = config('kyc.drivers.finnotech.scopes');

        if (!$clientScopes){

            throw new KycException(trans('kyc::errors.requiredDataMissed'));

        }

        return $clientScopes;
    }

    /**
     * @return string
     * @throws KycException
     */

    protected function getRestAPIBase(): string
    {
        $restAPIBase = config('kyc.drivers.finnotech.base-url');

        if (!$restAPIBase){

            throw new KycException(trans('kyc::errors.requiredDataMissed'));

        }

        return $restAPIBase;
    }

    /**
     * @param string $mobile
     * @param string $nationalCode
     * @return Collection
     * @throws GuzzleException
     * @throws KycException
     */

    public function mobileAndNationalCodeVerification(string $mobile, string $nationalCode): Collection
    {
        /**
         * Make request.
         */

        $request = $this->client(true)->get('kyc/v2/clients/' . $this->getClientId(). '/shahkar/smsSend', [
            'query' =>  [
                'trackId'   =>  $this->generateTrackId(),
                'mobile'    =>  $mobile,
                'nationalCode'  =>  $nationalCode
            ],
        ]);

        /**
         * Handle request failure.
         */

        if ($request->getStatusCode() != 200){

            throw new KycException(json_decode($request->getBody()->getContents())->error->message);

        }

        /**
         * Return response.
         */

        return collect(json_decode($request->getBody()->getContents()));
    }

    /**
     * @param string $cardNumber
     * @return Collection
     * @throws GuzzleException
     * @throws KycException
     */

    public function getCardToDeposit(string $cardNumber): Collection
    {
        /**
         * Make request.
         */

        $request = $this->client(true)->get('facility/v2/clients/' . $this->getClientId() . '/cardToDeposit', [
            'query' =>  [
                'trackId'   =>  $this->generateTrackId(),
                'card'  =>  $cardNumber
            ],
        ]);

        /**
         * Handle Request failures.
         */

        if ($request->getStatusCode() != 200){

            throw new KycException(json_decode($request->getBody()->getContents())->error->message);

        }

        /**
         * Cast and Return.
         */

        $result = json_decode($request->getBody()->getContents())->result;

        return collect([
            'owner' =>  $result->name,
            'cardNumber'    =>  $cardNumber,
            'depositNumber' =>  $result->deposit,
            //Todo: $result->result is also available and should be added later.
        ]);
    }

    /**
     * @param string $cardNumber
     * @return Collection
     * @throws GuzzleException
     * @throws KycException
     */

    public function getCardToIban(string $cardNumber): Collection
    {
        /**
         * Make Request.
         */

        $request = $this->client(true)->get('facility/v2/clients/' . $this->getClientId() . '/cardToIban', [
            'query' =>  [
                'version'   =>  2,
                'trackId'   =>  $this->generateTrackId(),
                'card'  =>  $cardNumber
            ],
        ]);

        /**
         * Handle Request Failure.
         */

        if ($request->getStatusCode() != 200){

            throw new KycException(json_decode($request->getBody()->getContents())->error->message);

        }

        /**
         * Cast And Return.
         */

        $result = json_decode($request->getBody()->getContents())->result;

        return collect([
            'owner' =>  $result->depositOwners,
            'cardNumber'    =>  $cardNumber,
            'depositNumber' =>  $result->deposit,
            'IBAN'  =>  $result->IBAN,
            'bankName'  =>  $result->bankName,
            //Todo: $result->depositStatus is also available and should be added later.
        ]);
    }

    /**
     * @return Collection
     * @throws GuzzleException
     * @throws KycException
     */

    public function getBanksInformation(): Collection
    {
        /**
         * Make Request.
         */

        $request = $this->client(true)->get('facility/v2/clients/' . $this->getClientId() . '/banksInfo', [
            'query' =>  [
                'trackId'   =>  $this->generateTrackId()
            ],
        ]);

        /**
         * Handle Request Failures.
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
     * @param string $postalCode
     * @return Collection
     * @throws GuzzleException
     * @throws KycException
     */

    public function getPostalCodeInformation(string $postalCode): Collection
    {
        /**
         * Make request.
         */

        $request = $this->client(true)->get('ecity/v2/clients/' . $this->getClientId() . '/postalCode',[
            'query' =>  [
                'trackId'   =>  $this->generateTrackId(),
                'postalCode'    =>  $postalCode,
            ],
        ]);

        /**
         * Handle Request Failures.
         */

        if ($request->getStatusCode() != 200){

            throw new KycException(json_decode($request->getBody()->getContents())->error->message);

        }

        /**
         * Cast and Return.
         */

        return collect(json_decode($request->getBody()->getContents())->result);
    }

}