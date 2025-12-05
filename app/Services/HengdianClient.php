<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use SimpleXMLElement;
use DOMDocument;

class HengdianClient
{
    protected string $url;
    protected string $username;
    protected string $password;

    public function __construct()
    {
        $this->url = Config::get('hengdian.url');
        $this->username = Config::get('hengdian.username');
        $this->password = Config::get('hengdian.password');
    }

    /**
     * Send the XML request to the Hengdian API.
     *
     * @param string $xmlString The raw XML string to send.
     * @return SimpleXMLElement|null
     */
    private function sendRequest(string $xmlString): ?SimpleXMLElement
    {
        Log::channel('hengdian')->info('Hengdian API Request:', ['xml' => $xmlString]);

        $response = Http::withHeaders([
                'Content-Type' => 'text/xml; charset=utf-8', // Using text/xml as it's more common for this style of API
            ])
            ->send('POST', $this->url, ['body' => $xmlString]);

        $responseBody = $response->body();
        Log::channel('hengdian')->info('Hengdian API Response:', ['body' => $responseBody]);

        if ($response->successful()) {
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($responseBody);
            if ($xml === false) {
                $errors = libxml_get_errors();
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->message;
                }
                Log::channel('hengdian')->error('Failed to parse response XML.', ['body' => $responseBody, 'errors' => $errorMessages]);
                libxml_clear_errors();
                return null;
            }
            return $xml;
        }

        return null;
    }

    /**
     * Helper to convert a SimpleXMLElement to a string without the XML declaration.
     * @param SimpleXMLElement $xml
     * @return string
     */
    private function getXmlString(SimpleXMLElement $xml): string
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $domNode = dom_import_simplexml($xml);
        $domNode = $dom->importNode($domNode, true);
        $dom->appendChild($domNode);
        return $dom->saveXML($dom->documentElement);
    }

    /**
     * 3.1 可订查询 (ValidateRQ)
     */
    public function validate(
        string $packageId,
        string $hotelId,
        string $roomType,
        string $checkIn,
        string $checkOut,
        int $roomNum = 1,
        int $customerNumber = 2,
        int $paymentType = 5
    ): ?SimpleXMLElement {
        $xml = new SimpleXMLElement('<ValidateRQ/>');

        $authToken = $xml->addChild('AuthenticationToken');
        $authToken->addChild('Username', $this->username);
        $authToken->addChild('Password', $this->password);

        $xml->addChild('PackageId', $packageId);
        $xml->addChild('HotelId', $hotelId);
        $xml->addChild('RoomType', $roomType);
        $xml->addChild('CheckIn', $checkIn);
        $xml->addChild('CheckOut', $checkOut);
        $xml->addChild('RoomNum', $roomNum);
        $xml->addChild('CustomerNumber', $customerNumber);
        $xml->addChild('PaymentType', $paymentType);
        $xml->addChild('Extensions', '{}');

        // Construct the final XML string with a single, correct declaration
        $xmlString = '<?xml version="1.0" encoding="UTF-8"?>' . $this->getXmlString($xml);

        return $this->sendRequest($xmlString);
    }
}
