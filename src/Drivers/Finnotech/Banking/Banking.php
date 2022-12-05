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
         * Cast And Return.
         */

        $result = json_decode($request->getBody()->getContents())->result;

        /**
         * Handle failure.
         */

        if (!$result->destCard){

            throw new KycException(trans('kyc::errors.parameterIsInvalid', ['param'=>trans('kyc::attributes.cardNumber')]));

        }

        return collect([
            'card'    =>  $result->destCard,
            'owners' =>  $result->name,
            'bank'  =>  $result->bankName
        ]);
    }

    /**
     * @param string $cardNumber
     * @param string $mobile
     * @return Collection
     * @throws GuzzleException
     * @throws KycException
     */

    public function getCardOwnership(string $cardNumber, string $mobile): Collection
    {
        /**
         * Set Scope.
         */

        $this->setScope('kyc:mobile-card-verification:post');

        /**
         * Make request.
         */

        $request = $this->client(true)->post('kyc/v2/clients/' . $this->getClientId() . '/mobileCardVerification', [
            'query' =>  [
                'trackId'   =>  $this->generateTrackId()
            ],
            'json'  =>  [
                'mobile'    =>  $mobile,
                'card'  =>  $cardNumber
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

        return collect([
            'is_integrated' =>  json_decode($request->getBody()->getContents())->result->isValid
        ]);
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

        return collect([
            'Iban'    =>  $result->IBAN,
            'deposit' =>  $result->deposit,
            'status'    =>  $result->depositStatus,
            'status_casted'   =>  $result->depositDescription,
            'type'  =>  $result->depositComment,
            'owners'    =>  collect($result->depositOwners)->map(fn($v, $k) => $v->firstName . ' ' . $v->lastName),
            'bank'  =>  $result->bankName,
        ]);
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
            'owners' =>  [$result->name],
            'card'    =>  $cardNumber,
            'deposit' =>  $result->deposit,
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
            'owners' =>  [$result->depositOwners],
            'card'    =>  $cardNumber,
            'deposit' =>  $result->deposit,
            'Iban'  =>  $result->IBAN,
            'bank'  =>  $result->bankName,
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
                'bankCode'  =>  $bank
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

        return collect([
            'owners'    =>  [$result->depositOwners],
            'deposit'   =>  $result->deposit,
            'bank'  =>  $result->bankName,
            'Iban'  =>  $result->iban
        ]);
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

        $result = json_decode($request->getBody()->getContents())->result;

        return collect($result)->map(fn($v) => [
            'cardsPrefix'   =>  $v->cardPrefix,
            'code'  =>  $v->code,
            'name'  =>  $v->name
        ]);
    }
}