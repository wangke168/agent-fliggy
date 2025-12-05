<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\Response;
use RuntimeException;

/**
 * Client for interacting with the Fliggy Distribution API.
 *
 * Note: The signature generation logic is based on the provided documentation.
 * It's recommended to double-check the 'param' string format for each API call.
 */
class FliggyClient
{
    protected string $distributorId;
    protected string $privateKey;
    protected string $baseUrl;

    public function __construct()
    {
        $this->distributorId = Config::get('fliggy.distributor_id');
        $this->privateKey = Config::get('fliggy.private_key');
        $this->baseUrl = Config::get('fliggy.api_base_uri');

        if (empty($this->distributorId) || empty($this->privateKey)) {
            throw new RuntimeException('Fliggy distributor ID or private key is not configured.');
        }
    }

    /**
     * Switch to use the pre-production API environment.
     * @return $this
     */
    public function usePreEnvironment(): self
    {
        $this->baseUrl = Config::get('fliggy.api_base_uri_pre');
        return $this;
    }

    /**
     * Generates the SHA256withRSA signature.
     */
    private function generateSign(string $dataToSign): string
    {
        $pem = "-----BEGIN PRIVATE KEY-----\n" . wordwrap($this->privateKey, 64, "\n", true) . "\n-----END PRIVATE KEY-----";
        $key = openssl_pkey_get_private($pem);
        if (!$key) {
            throw new RuntimeException('Invalid private key provided for Fliggy API.');
        }
        openssl_sign($dataToSign, $signature, $key, OPENSSL_ALGO_SHA256);
        openssl_free_key($key);
        return base64_encode($signature);
    }

    /**
     * The core method to send requests to the API.
     */
    private function post(string $path, array $body): Response
    {
        $fullUrl = $this->baseUrl . $path . '?format=json';
        Log::channel('fliggy')->info('Fliggy API Request:', ['url' => $fullUrl, 'body' => $body]);
        $response = Http::post($fullUrl, $body);
        Log::channel('fliggy')->info('Fliggy API Response:', ['status' => $response->status(), 'body' => $response->json() ?? $response->body()]);
        return $response;
    }

    /**
     * A generic method to build and send requests.
     *
     * @param string $path API Path
     * @param array $params Request parameters
     * @param array $signKeys Keys from params to include in the signature string, in order.
     * @return Response
     */
    public function send(string $path, array $params, array $signKeys): Response
    {
        $timestamp = round(microtime(true) * 1000);

        $signValues = [$this->distributorId, $timestamp];
        foreach ($signKeys as $key) {
            if (isset($params[$key])) {
                $value = $params[$key];
                // The documentation doesn't specify how to handle array parameters in signatures.
                // Assuming comma-separated for lists like productIds.
                $signValues[] = is_array($value) ? implode(',', $value) : $value;
            }
        }

        $stringToSign = implode('_', $signValues);

        // The doc shows a trailing underscore for param strings with no extra fields.
        if (empty($signKeys)) {
            $stringToSign .= '_';
        }

        $body = array_merge([
            'distributorId' => $this->distributorId,
            'timestamp' => $timestamp,
            'sign' => $this->generateSign($stringToSign),
        ], $params);

        return $this->post($path, $body);
    }

    /*
    |--------------------------------------------------------------------------
    | API Method Implementations
    |--------------------------------------------------------------------------
    |
    | Below are example implementations for specific API endpoints.
    | You should add methods for all the API calls you intend to use.
    |
    */

    /**
     * 2.1. 分页获取产品基本信息接口 queryProductBaseInfoByPage
     */
    public function queryProductBaseInfoByPage(int $pageNo = 1, int $pageSize = 10): Response
    {
        return $this->send(
            '/api/v1/hotelticket/queryProductBaseInfoByPage',
            ['pageNo' => $pageNo, 'pageSize' => $pageSize],
            [] // Signature param is 'distributorId_timestamp_'
        );
    }

    /**
     * 2.3. 获取产品详情接口 queryProductDetailInfo
     */
    public function queryProductDetailInfo(string $productId): Response
    {
        return $this->send(
            '/api/v1/hotelticket/queryProductDetailInfo',
            ['productId' => $productId],
            ['productId']
        );
    }

    /**
     * 3.3. 创建订单接口 createOrder
     */
    public function createOrder(array $orderData): Response
    {
        // Note: The doc says signature param is 'distributorId_timestamp_productId'.
        // This seems too simple and might be an error in the documentation, as it omits
        // crucial order details. This implementation follows the doc literally.
        // If it fails, you may need to add other fields from $orderData to the signKeys array.
        return $this->send(
            '/api/v1/hotelticket/createOrder',
            $orderData,
            ['productId']
        );
    }
}
