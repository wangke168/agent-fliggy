<?php

namespace App\Services\Hengdian;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use SimpleXMLElement;
use DOMDocument;

/**
 * 用于与横店影视城酒景产品进行交互的客户端。
 * 该接口使用 XML over HTTP POST 的方式通信。
 */
class Client
{
    protected string $url;
    protected string $username;
    protected string $password;

    /**
     * 构造函数，初始化配置。
     */
    public function __construct()
    {
        // 默认使用生产环境地址
        $this->url = Config::get('hengdian.url_production');
        $this->username = Config::get('hengdian.username');
        $this->password = Config::get('hengdian.password');
    }

    /**
     * 切换到测试环境。
     * @return $this
     */
    public function useTestEnvironment(): self
    {
        $this->url = Config::get('hengdian.url');
        return $this;
    }

    /**
     * 发送 XML 请求的核心方法。
     * @param string $xmlString 完整的 XML 请求字符串
     * @return SimpleXMLElement|null
     */
    private function sendRequest(string $xmlString): ?SimpleXMLElement
    {
        Log::channel('hengdian')->info('横店 API 请求:', ['url' => $this->url, 'xml' => $xmlString]);

        $response = Http::withHeaders(['Content-Type' => 'text/xml; charset=utf-8'])
            ->send('POST', $this->url, ['body' => $xmlString]);

        $responseBody = $response->body();
        Log::channel('hengdian')->info('横店 API 响应:', ['body' => $responseBody]);

        if ($response->successful()) {
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($responseBody);
            if ($xml === false) {
                $errors = libxml_get_errors();
                Log::channel('hengdian')->error('解析响应 XML 失败。', ['body' => $responseBody, 'errors' => $errors]);
                libxml_clear_errors();
                return null;
            }
            return $xml;
        }

        return null;
    }

    /**
     * 将 SimpleXMLElement 对象转换为带正确声明的 XML 字符串。
     */
    private function getXmlString(SimpleXMLElement $xml): string
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $domNode = dom_import_simplexml($xml);
        $domNode = $dom->importNode($domNode, true);
        $dom->appendChild($domNode);
        return $dom->saveXML();
    }

    /**
     * [3.1] 可订查询 (ValidateRQ)
     */
    public function validate(string $packageId, string $hotelId, string $roomType, string $checkIn, string $checkOut, int $roomNum = 1, int $customerNumber = 2, int $paymentType = 1): ?SimpleXMLElement
    {
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
     * [3.5] 房态订阅 (SubscribeRoomStatusRQ)
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
