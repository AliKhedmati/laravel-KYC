<?php

namespace Alikhedmati\Kyc\Drivers\Finnotech\Kyc;

use Alikhedmati\Kyc\Drivers\Finnotech\Base;
use Alikhedmati\Kyc\Exceptions\KycException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Support\Collection;

class Kyc extends Base
{
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

    /**
     * @param string $pathToNationalCardImage
     * @param bool $isFrontSide
     * @return Collection
     * @throws GuzzleException
     * @throws KycException
     */

    public function getNationalCardOCR(string $pathToNationalCardImage, bool $isFrontSide): Collection
    {

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

        return collect(json_decode($request->getBody()->getContents())->result);
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

    public function compareVideoWithNationalCardImage(string $videoPath, string $nationalCode, string $birthDate, string $nationalCardSerialNumber, string $speechText): Collection
    {
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

        return collect(json_decode($request->getBody()->getContents())->result);
    }

}