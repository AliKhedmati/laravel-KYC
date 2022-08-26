<?php

namespace Alikhedmati\Kyc\Drivers\Finnotech\Banking;

use Alikhedmati\Kyc\Drivers\Finnotech\Base;
use Alikhedmati\Kyc\Exceptions\KycException;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Collection;

class Banking extends Base
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
     * @param string $cardNumber
     * @return Collection
     * @throws GuzzleException
     * @throws KycException
     */

    public function getDepositToCard(string $cardNumber): Collection
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

    public function getIbanToCard(string $cardNumber): Collection
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
     * @param string $deposit
     * @param string $bank
     * @return Collection
     * @throws GuzzleException
     * @throws KycException
     */

    public function getDepositToIban(string $deposit, string $bank): Collection
    {
        /**
         * Make Request.
         */

        $request = $this->client(true)->get('oak/v2/clients/' . $this->getClientId() . '/iban', [
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

        $result = json_decode($request->getBody()->getContents())->result;

        return collect($result);
    }

    /**
     * @return Collection
     * @throws GuzzleException
     * @throws KycException
     */

    public function getBanks(): Collection
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
}