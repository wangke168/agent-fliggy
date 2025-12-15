<?php

namespace App\Services\Ctrip;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * 用于与携程景点玩乐开放平台进行交互的客户端。
 * 实现了其独特的 AES 加密、自定义 Hex 编码和 MD5 签名机制。
 */
class Client
{
    protected string $accountId;
    protected string $signKey;
    protected string $aesKey;
    protected string $aesIv;
    protected string $baseUrl;

    /**
     * @var array<string, string> 服务名到具体接口路径的映射。
     */
    protected const SERVICE_ENDPOINT_MAP = [
        'DatePriceModify' => 'product/price.do',
        'DateInventoryModify' => 'product/stock.do',
        'CreatePreOrder' => 'order/notice.do',
        'PayPreOrder' => 'order/notice.do',
        'CancelPreOrder' => 'order/notice.do',
        'PayPreOrderConfirm' => 'order/notice.do',
        // 根据文档，所有订单相关的通知都指向同一个 notice.do 接口
    ];

    /**
     * 构造函数，初始化配置。
     */
    public function __construct()
    {
        $this->accountId = Config::get('ctrip.account_id');
        $this->signKey = Config::get('ctrip.sign_key');
        $this->aesKey = Config::get('ctrip.aes_key');
        $this->aesIv = Config::get('ctrip.aes_iv');
        $this->baseUrl = Config::get('ctrip.url');

        if (!$this->accountId || !$this->signKey || !$this->aesKey || !$this->aesIv) {
            throw new \InvalidArgumentException('携程 API 凭证未完全配置。');
        }
    }

    /**
     * 实现携程文档中提供的自定义十六进制编码。
     */
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

    /**
     * 实现携程文档中提供的自定义十六进制解码。
     */
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

    /**
     * 使用 AES-128-CBC 加密数据，并进行自定义编码。
     */
    public function encryptBody(string $data): string
    {
        $encrypted = openssl_encrypt($data, 'AES-128-CBC', $this->aesKey, OPENSSL_RAW_DATA, $this->aesIv);
        return $this->customEncode($encrypted);
    }

    /**
     * 解密来自携程的 body 数据。
     */
    public function decryptBody(string $encodedData): ?string
    {
        $binaryData = $this->customDecode($encodedData);
        return openssl_decrypt($binaryData, 'AES-128-CBC', $this->aesKey, OPENSSL_RAW_DATA, $this->aesIv);
    }

    /**
     * 生成 MD5 签名。
     */
    private function generateSign(string $serviceName, string $requestTime, string $version, string $bodyContent): string
    {
        $stringToSign = $this->accountId . $serviceName . $requestTime . $bodyContent . $version . $this->signKey;
        return strtolower(md5($stringToSign));
    }

    /**
     * 根据服务名获取完整的接口 URL。
     */
    private function getServiceUrl(string $serviceName): ?string
    {
        $endpoint = self::SERVICE_ENDPOINT_MAP[$serviceName] ?? null;
        if (!$endpoint) {
            Log::channel('ctrip')->error('未找到服务名称对应的接口端点映射: ' . $serviceName);
            return null;
        }
        return $this->baseUrl . $endpoint;
    }

    /**
     * 发送请求到携程 API 的核心方法。
     */
    private function sendRequest(string $serviceName, array $bodyData, string $version = '1.0')
    {
        $url = $this->getServiceUrl($serviceName);
        if (!$url) {
            return ['response' => ['error' => '无效的服务名称'], 'decrypted_body' => null];
        }

        $requestTime = now()->format('Y-m-d H:i:s');
        $bodyJson = json_encode($bodyData, JSON_UNESCAPED_SLASHES);
        $encryptedBody = $this->encryptBody($bodyJson);

        $header = [
            'accountId' => $this->accountId,
            'serviceName' => $serviceName,
            'requestTime' => $requestTime,
            'version' => $version,
            'sign' => $this->generateSign($serviceName, $requestTime, $version, $encryptedBody),
        ];

        $requestPayload = ['header' => $header, 'body' => $encryptedBody];

        Log::channel('ctrip')->info('携程 API 请求:', ['url' => $url, 'payload' => $requestPayload, 'decrypted_body' => $bodyData]);

        $response = Http::post($url, $requestPayload);
        $responseJson = $response->json();
        $decryptedBody = isset($responseJson['body']) ? $this->decryptBody($responseJson['body']) : null;

        Log::channel('ctrip')->info('携程 API 响应:', ['status' => $response->status(), 'body' => $responseJson, 'decrypted_body' => json_decode($decryptedBody, true) ?? $decryptedBody]);

        return ['response' => $responseJson, 'decrypted_body' => json_decode($decryptedBody, true)];
    }

    /**
     * [产品] 资源日期价格同步接口
     */
    public function datePriceModify(string $sequenceId, ?string $supplierOptionId, string $dateType, array $prices): array
    {
        $body = ['sequenceId' => $sequenceId, 'dateType' => $dateType, 'prices' => $prices, 'supplierOptionId' => $supplierOptionId];
        return $this->sendRequest('DatePriceModify', $body);
    }

    /**
     * [产品] 资源日期库存同步接口
     */
    public function dateInventoryModify(string $sequenceId, ?string $supplierOptionId, string $dateType, array $inventories): array
    {
        $body = ['sequenceId' => $sequenceId, 'dateType' => $dateType, 'inventorys' => $inventories, 'supplierOptionId' => $supplierOptionId];
        return $this->sendRequest('DateInventoryModify', $body);
    }

    /**
     * [订单] 预下单支付确认
     */
    public function payPreOrderConfirm(string $sequenceId, string $otaOrderId, string $supplierOrderId, array $items): array
    {
        $body = [
            'sequenceId' => $sequenceId,
            'otaOrderId' => $otaOrderId,
            'supplierOrderId' => $supplierOrderId,
            'confirmResultCode' => '0000',
            'confirmResultMessage' => '确认成功',
            'voucherSender' => 1,
            'items' => $items,
        ];
        return $this->sendRequest('PayPreOrderConfirm', $body);
    }
}
