<?php

namespace Alikhedmati\Kyc\Drivers\Finnotech;

use Alikhedmati\Kyc\Exceptions\KycException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Str;

class Base
{
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
     * @param bool $isAuthenticated
     * @return Client
     * @throws GuzzleException
     * @throws KycException
     */

    public function client(bool $isAuthenticated = false): Client
    {
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        if ($isAuthenticated){

            $headers['Authorization'] = 'Bearer '. (new Authentication\Authentication())->getAccessToken();

        }

        return new Client([
            'base_uri'  =>  $this->getRestAPIBase(),
            'headers'   =>  $headers,
            'http_errors'   =>  false
        ]);
    }

    /**
     * @return string
     */

    public function generateTrackId(): string
    {
        return Str::orderedUuid()->toString();
    }

}