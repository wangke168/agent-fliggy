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
        // Default to production URL
        $this->url = Config::get('hengdian.url_production');
        $this->username = Config::get('hengdian.username');
        $this->password = Config::get('hengdian.password');
    }

    /**
     * Force the client to use the test environment.
     * @return $this
     */
    public function useTestEnvironment(): self
    {
        $this->url = Config::get('hengdian.url');
        return $this;
    }

    /**
     * Send the XML request to the Hengdian API.
     *
     * @param string $xmlString The raw XML string to send.
     * @return SimpleXMLElement|null
     */
    private function sendRequest(string $xmlString): ?SimpleXMLElement
    {
        Log::channel('hengdian')->info('Hengdian API Request:', ['url' => $this->url, 'xml' => $xmlString]);

        $response = Http::withHeaders([
                'Content-Type' => 'text/xml; charset=utf-8',
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
     * Helper to convert a SimpleXMLElement to a string with a proper declaration.
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
        return $dom->saveXML(); // saveXML() on the document object includes the declaration
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
        int $paymentType = 1
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

        return $this->sendRequest($this->getXmlString($xml));
    }

    /**
     * 3.5 房态订阅 (SubscribeRoomStatusRQ)
     *
     * @param string $notifyUrl The URL to receive status updates.
     * @param array $hotels An array of hotels and their room types to subscribe to.
     *                      Example: [['hotelId' => '001', 'roomTypes' => ['标准间', '豪华间']]]
     * @param bool $isUnsubscribe Set to true to unsubscribe.
     * @return SimpleXMLElement|null
     */
    public function subscribeRoomStatus(string $notifyUrl, array $hotels = [], bool $isUnsubscribe = false): ?SimpleXMLElement
    {
        $xml = new SimpleXMLElement('<SubscribeRoomStatusRQ/>');

        $authToken = $xml->addChild('AuthenticationToken');
        $authToken->addChild('Username', $this->username);
        $authToken->addChild('Password', $this->password);

        $xml->addChild('NotifyUrl', $notifyUrl);
        $xml->addChild('IsUnsubscribe', $isUnsubscribe ? '1' : '0');

        $hotelsNode = $xml->addChild('Hotels');
        if (!empty($hotels)) {
            foreach ($hotels as $hotelData) {
                $hotelNode = $hotelsNode->addChild('Hotel');
                $hotelNode->addChild('HotelId', $hotelData['hotelId']);
                $roomsNode = $hotelNode->addChild('Rooms');
                foreach ($hotelData['roomTypes'] as $roomType) {
                    $roomsNode->addChild('RoomType', $roomType);
                }
            }
        }

        $xml->addChild('Extensions', '{}');

        return $this->sendRequest($this->getXmlString($xml));
    }
}
