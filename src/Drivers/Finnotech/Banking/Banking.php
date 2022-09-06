<?php

namespace Alikhedmati\Kyc\Drivers\Finnotech\Banking;

use Alikhedmati\Kyc\Drivers\Finnotech\Factory;
use Alikhedmati\Kyc\Exceptions\KycException;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Collection;

class Banking extends Factory
{
    /**
     * @param string $cardNumber
     * @return Collection
     * @throws GuzzleException
     * @throws KycException
     */

    public function getCard(string $cardNumber): Collection
    {
        /**
         * Set Scope.
         */

        $this->setScope('card:information:get');

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
     * @param string $iban
     * @return Collection
     * @throws GuzzleException
     * @throws KycException
     */

    public function getIban(string $iban): Collection
    {
        /**
         * Set Scope.
         */

        $this->setScope('oak:iban-inquiry:get');

        /**
         * Make request.
         */

        $request = $this->client(true)->get('oak/v2/clients/' . $this->getClientId() . '/ibanInquiry', [
            'query' =>  [
                'trackId'   =>  $this->generateTrackId(),
                'iban'  =>  $iban
            ],
        ]);

        /**
         * Handle request failures.
         */

        if ($request->getStatusCode() != 200){

            throw new KycException(json_decode($request->getBody()->getContents())->error->message);

        }

        /**
         * Cast And Return.
         */

        $result = json_decode($request->getBody()->getContents())->result;

        return collect($result);
    }

    /**
     * @param string $deposit
     * @return Collection
     * @throws GuzzleException
     * @throws KycException
     */

    public function getDeposit(string $deposit): Collection
    {
        /**
         * Set Scope.
         */

        $this->setScope('facility:deposit-info:get');

        /**
         * Make Request.
         */

        $request = $this->client(true)->get('facility/v2/clients/' . $this->getClientId() . '/depositInfo', [
            'query' =>  [
                'trackId'   =>  $this->generateTrackId(),
                'deposit'   =>  $deposit
            ],
        ]);

        /**
         * Handle request failures.
         */

        if ($request->getStatusCode() != 200){

            throw new KycException(json_decode($request->getBody()->getContents())->error->message);

        }

        /**
         * Cast and Return.
         */

        return collect(json_decode($request->getBody()->getContents())->result);
    }

    /**
     * @param string $cardNumber
     * @return Collection
     * @throws GuzzleException
     * @throws KycException
     */

    public function getDepositToCard(string $cardNumber): Collection
    {
        /**
         * Set Scope.
         */

        $this->setScope('facility:card-to-deposit:get');

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

    public function getIbanToCard(string $cardNumber): Collection
    {
        /**
         * Set Scope.
         */

        $this->setScope('facility:card-to-iban:get');

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
     * @param string $deposit
     * @param string $bank
     * @return Collection
     * @throws GuzzleException
     * @throws KycException
     */

    public function getDepositToIban(string $deposit, string $bank): Collection
    {
        /**
         * Set Scope.
         */

        $this->setScope('facility:cc-deposit-iban:get');

        /**
         * Make Request.
         */

        $request = $this->client(true)->get('facility/v2/clients/' . $this->getClientId() . '/depositToIban', [
            'query' =>  [
                'trackId'   =>  $this->generateTrackId(),
                'deposit'   =>  $deposit,
                'bank'  =>  $bank
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
     * @return Collection
     * @throws GuzzleException
     * @throws KycException
     */

    public function getBanks(): Collection
    {
        /**
         * Set Scope.
         */

        $this->setScope('facility:cc-bank-info:get');

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
}