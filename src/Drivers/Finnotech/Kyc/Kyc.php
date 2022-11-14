<?php

namespace Alikhedmati\Kyc\Drivers\Finnotech\Kyc;

use Alikhedmati\Kyc\Drivers\Finnotech\Factory;
use Alikhedmati\Kyc\Exceptions\KycException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Support\Collection;

class Kyc extends Factory
{
    /**
     * @param string $mobile
     * @param string $nationalCode
     * @return Collection
     * @throws GuzzleException
     * @throws KycException
     */

    public function getMobileOwnership(string $mobile, string $nationalCode): Collection
    {
        /**
         * Set scope.
         */

        $this->setScope('facility:shahkar:get');

        /**
         * Make request.
         */

        $request = $this->client(true)->get('facility/v2/clients/' . $this->getClientId() . '/shahkar/verify', [
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

        $result = json_decode($request->getBody()->getContents())->result;

        return collect([
            'is_integrated' =>  $result->isValid
        ]);
    }

    /**
     * @param string $postalCode
     * @return Collection
     * @throws GuzzleException
     * @throws KycException
     */

    public function getZipCode(string $postalCode): Collection
    {
        /**
         * Set Scope.
         */

        $this->setScope('ecity:cc-postal-code-inquiry:get');

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

        $result = json_decode($request->getBody()->getContents())->result;

        $result = collect([
            'zip_code'  =>  $result->postalCode,
            'province'  =>  $result->Province,
            'town'  =>  $result->TownShip,
            'street'    =>  $result->SubLocality,
            'alley' =>  $result->street,
            'house_number'  =>  $result->HouseNumber,
            'floor' =>  $result->Floor,
            'unit'  =>  $result->SideFloor,
            'zone'  =>  $result->Zone,
        ])->map(fn($i)=>(string)$i);

        return collect($result);
    }

    /**
     * @param string $pathToNationalCardImage
     * @param bool $isFrontSide
     * @return Collection
     * @throws GuzzleException
     * @throws KycException
     */

    public function getNationalCard(string $pathToNationalCardImage, bool $isFrontSide): Collection
    {
        /**
         * Set Scope.
         */

        $this->setScope('kyc:ocr-verification:post');

        /**
         * Make Request.
         */

        $request = $this->client(true)->post('kyc/v2/clients/ ' . $this->getClientId() . '/ocr', [
            'query' =>  [
                'trackId'   =>  $this->generateTrackId()
            ],
            'multipart' =>  [
                [
                    'name'  =>  'type',
                    'contents'  =>  'uploadCard' . ($isFrontSide ? 'Front' : 'Back')
                ],
                [
                    'name'  =>  'cardImage',
                    'contents'  =>  Utils::tryFopen($pathToNationalCardImage, 'r')
                ],
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
     * @param string $nationalCode
     * @param string $birthDate
     * @return Collection
     * @throws GuzzleException
     * @throws KycException
     */

    public function getNationalCode(string $nationalCode, string $birthDate): Collection
    {
        /**
         * Set Scope.
         */

        $this->setScope('facility:cc-nid-inquiry:get');

        /**
         * Make request.
         */

        $request = $this->client(true)->get('/facility/v2/clients/'. $this->getClientId() .'/users/'. $nationalCode . '/cc/nidInquiry', [
            'query' =>  [
                'trackId'   =>  $this->generateTrackId(),
                'birthDate'
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

        $result = json_decode($request->getBody()->getContents())->result;

        return collect($result);
    }

    /**
     * @param string $videoPath
     * @param string $nationalCode
     * @param string $birthDate
     * @param string $nationalCardSerialNumber
     * @param string $speechText
     * @return Collection
     * @throws GuzzleException
     * @throws KycException
     */

    public function getVideoVerification(string $videoPath, string $nationalCode, string $birthDate, string $nationalCardSerialNumber, string $speechText): Collection
    {
        /**
         * Set Scope.
         */

        $this->setScope('kyc:compare-live-video-with-national-card-image:post');

        /**
         * Make request.
         */

        $request = $this->client(true)->post('kyc/v2/clients/' . $this->getClientId(). '/compareLiveVideoWithNationalCard', [
            'query' =>  [
                'trackId'   =>  $this->generateTrackId()
            ],
            'multipart' =>  [
                [
                    'name'  =>  'video',
                    'contents'  =>  Utils::tryFopen($videoPath, 'r')
                ],
                [
                    'name'  =>  'nationalCode',
                    'contents'  =>  $nationalCode
                ],
                [
                    'name'  =>  'birthDate',
                    'contents'  =>  $birthDate
                ],
                [
                    'name'  =>  'serialNumber',
                    'contents'  =>  $nationalCardSerialNumber
                ],
                [
                    'name'  =>  'speechText',
                    'contents'  =>  $speechText
                ],
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

        $result = json_decode($request->getBody()->getContents())->result;

        return collect($result);
    }
}