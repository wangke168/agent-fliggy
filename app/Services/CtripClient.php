<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CtripClient
{
    protected string $accountId;
    protected string $signKey;
    protected string $aesKey;
    protected string $aesIv;
    protected string $baseUrl;

    protected const SERVICE_ENDPOINT_MAP = [
        'DatePriceModify' => 'product/price.do',
        'DateInventoryModify' => 'product/stock.do',
        'OrderCreate' => 'order/notice.do',
        'OrderCancel' => 'order/notice.do',
        'OrderQuery' => 'order/notice.do',
        'PayPreOrderConfirm' => 'order/notice.do', // Added this mapping
    ];

    public function __construct()
    {
        $this->accountId = Config::get('ctrip.account_id');
        $this->signKey = Config::get('ctrip.sign_key');
        $this->aesKey = Config::get('ctrip.aes_key');
        $this->aesIv = Config::get('ctrip.aes_iv');
        $this->baseUrl = Config::get('ctrip.url');

        if (!$this->accountId || !$this->signKey || !$this->aesKey || !$this->aesIv) {
            throw new \InvalidArgumentException('Ctrip API credentials are not fully configured.');
        }
    }

    private function customEncode(string $bytes): string
    {
        $strBuf = '';
        for ($i = 0; $i < strlen($bytes); $i++) {
            $byte = ord($bytes[$i]);
            $strBuf .= chr((($byte >> 4) & 0xF) + ord('a'));
            $strBuf .= chr((($byte) & 0xF) + ord('a'));
        }
        return $strBuf;
    }

    private function customDecode(string $str): string
    {
        $bytes = '';
        for ($i = 0; $i < strlen($str); $i += 2) {
            $c1 = $str[$i];
            $c2 = $str[$i + 1];
            $byte = (ord($c1) - ord('a')) << 4;
            $byte += (ord($c2) - ord('a'));
            $bytes .= chr($byte);
        }
        return $bytes;
    }

    private function encrypt(string $data): string
    {
        $encrypted = openssl_encrypt($data, 'AES-128-CBC', $this->aesKey, OPENSSL_RAW_DATA, $this->aesIv);
        return $this->customEncode($encrypted);
    }

    public function decryptBody(string $encodedData): ?string
    {
        $binaryData = $this->customDecode($encodedData);
        return openssl_decrypt($binaryData, 'AES-128-CBC', $this->aesKey, OPENSSL_RAW_DATA, $this->aesIv);
    }

    private function generateSign(string $serviceName, string $requestTime, string $version, string $bodyContent): string
    {
        $stringToSign = $this->accountId . $serviceName . $requestTime . $bodyContent . $version . $this->signKey;
        return strtolower(md5($stringToSign));
    }

    private function getServiceUrl(string $serviceName): ?string
    {
        $endpoint = self::SERVICE_ENDPOINT_MAP[$serviceName] ?? null;
        if (!$endpoint) {
            Log::channel('ctrip')->error('No endpoint mapping found for service: ' . $serviceName);
            return null;
        }
        return $this->baseUrl . $endpoint;
    }

    private function sendRequest(string $serviceName, array $bodyData, string $version = '1.0')
    {
        $url = $this->getServiceUrl($serviceName);
        if (!$url) {
            return ['response' => ['error' => 'Invalid service name'], 'decrypted_body' => null];
        }

        $requestTime = now()->format('Y-m-d H:i:s');
        $bodyJson = json_encode($bodyData, JSON_UNESCAPED_SLASHES);
        $encryptedBody = $this->encrypt($bodyJson);

        $header = [
            'accountId' => $this->accountId,
            'serviceName' => $serviceName,
            'requestTime' => $requestTime,
            'version' => $version,
            'sign' => $this->generateSign($serviceName, $requestTime, $version, $encryptedBody),
        ];

        $requestPayload = [
            'header' => $header,
            'body' => $encryptedBody,
        ];

        Log::channel('ctrip')->info('Ctrip API Request:', [
            'url' => $url,
            'payload' => $requestPayload,
            'decrypted_body' => $bodyData,
            'string_to_sign' => $this->accountId . $serviceName . $requestTime . $encryptedBody . $version . $this->signKey,
        ]);

        $response = Http::post($url, $requestPayload);

        $responseJson = $response->json();
        $decryptedBody = null;
        if (isset($responseJson['body'])) {
            $decryptedBody = $this->decryptBody($responseJson['body']);
        }

        Log::channel('ctrip')->info('Ctrip API Response:', [
            'status' => $response->status(),
            'body' => $responseJson,
            'decrypted_body' => json_decode($decryptedBody, true) ?? $decryptedBody
        ]);

        return ['response' => $responseJson, 'decrypted_body' => json_decode($decryptedBody, true)];
    }

    public function datePriceModify(string $sequenceId, ?string $otaOptionId, ?string $supplierOptionId, string $dateType, array $prices): array
    {
        $body = [
            'sequenceId' => $sequenceId,
            'dateType' => $dateType,
            'prices' => $prices,
        ];
        if ($otaOptionId) $body['otaOptionId'] = $otaOptionId;
        if ($supplierOptionId) $body['supplierOptionId'] = $supplierOptionId;
        return $this->sendRequest('DatePriceModify', $body);
    }

    public function dateInventoryModify(string $sequenceId, ?string $otaOptionId, ?string $supplierOptionId, string $dateType, array $inventories): array
    {
        $body = [
            'sequenceId' => $sequenceId,
            'dateType' => $dateType,
            'inventorys' => $inventories,
        ];
        if ($otaOptionId) $body['otaOptionId'] = $otaOptionId;
        if ($supplierOptionId) $body['supplierOptionId'] = $supplierOptionId;
        return $this->sendRequest('DateInventoryModify', $body);
    }

    /**
     * 预下单支付确认 (PayPreOrderConfirm)
     */
    public function payPreOrderConfirm(string $sequenceId, string $otaOrderId, string $supplierOrderId, array $items): array
    {
        $body = [
            'sequenceId' => $sequenceId,
            'otaOrderId' => $otaOrderId,
            'supplierOrderId' => $supplierOrderId,
            'confirmResultCode' => '0000',
            'confirmResultMessage' => '确认成功',
            'voucherSender' => 1, // 1 = Ctrip sends voucher
            'items' => $items,
        ];
        return $this->sendRequest('PayPreOrderConfirm', $body);
    }
}
