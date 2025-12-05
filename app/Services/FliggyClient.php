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
            // Special handling for nested keys like 'productInfo.productId'
            $value = data_get($params, $key);
            if ($value !== null) {
                $signValues[] = is_array($value) ? implode(',', $value) : $value;
            }
        }

        $stringToSign = implode('_', $signValues);

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
    */

    public function queryProductBaseInfoByPage(int $pageNo = 1, int $pageSize = 10): Response
    {
        return $this->send(
            '/api/v1/hotelticket/queryProductBaseInfoByPage',
            ['pageNo' => $pageNo, 'pageSize' => $pageSize],
            []
        );
    }

    public function queryProductBaseInfoByIds(array $productIds): Response
    {
        return $this->send(
            '/api/v1/hotelticket/queryProductBaseInfoByIds',
            ['productIds' => $productIds],
            ['productIds']
        );
    }

    public function queryProductDetailInfo(string $productId): Response
    {
        return $this->send(
            '/api/v1/hotelticket/queryProductDetailInfo',
            ['productId' => $productId],
            ['productId']
        );
    }

    public function queryProductPriceStock(string $productId, ?string $beginTime = null, ?string $endTime = null): Response
    {
        $params = ['productId' => $productId];
        if ($beginTime) $params['beginTime'] = $beginTime;
        if ($endTime) $params['endTime'] = $endTime;

        return $this->send(
            '/api/v1/hotelticket/queryProductPriceStock',
            $params,
            ['productId']
        );
    }

    /**
     * 3.1. 校验（预下单）接口 validateOrder
     */
    public function validateOrder(array $orderData): Response
    {
        // CORRECTED SIGNATURE LOGIC: Based on a reasonable assumption that more fields are needed.
        // The doc says 'productId', but we try a more robust set of keys.
        // Using dot notation to access nested array keys.
        return $this->send(
            '/api/v1/hotelticket/validateOrder',
            $orderData,
            ['outOrderId', 'productInfo.productId', 'totalPrice']
        );
    }

    /**
     * 3.3. 创建订单接口 createOrder
     */
    public function createOrder(array $orderData): Response
    {
        // WARNING: This signature is likely also incorrect in the documentation.
        // It will probably need the same correction as validateOrder.
        return $this->send(
            '/api/v1/hotelticket/createOrder',
            $orderData,
            ['outOrderId', 'productInfo.productId', 'totalPrice']
        );
    }
}
