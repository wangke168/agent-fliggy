<?php

namespace App\Services\Fliggy;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\Response;
use RuntimeException;

/**
 * 用于与飞猪分销 API v1 进行交互的客户端。
 */
class Client
{
    protected string $distributorId;
    protected string $privateKey;
    protected string $baseUrl;

    /**
     * 构造函数，初始化配置。
     */
    public function __construct()
    {
        $this->distributorId = Config::get('fliggy.distributor_id');
        $this->privateKey = Config::get('fliggy.private_key');
        $this->baseUrl = Config::get('fliggy.api_base_uri');

        if (empty($this->distributorId) || empty($this->privateKey)) {
            throw new RuntimeException('飞猪分销商ID或私钥未配置。');
        }
    }

    /**
     * 切换到预发环境。
     * @return $this
     */
    public function usePreEnvironment(): self
    {
        $this->baseUrl = Config::get('fliggy.api_base_uri_pre');
        return $this;
    }

    /**
     * 生成 SHA256withRSA 签名。
     * @param string $dataToSign 待签名字符串
     * @return string Base64编码后的签名
     */
    private function generateSign(string $dataToSign): string
    {
        $pem = "-----BEGIN PRIVATE KEY-----\n" . wordwrap($this->privateKey, 64, "\n", true) . "\n-----END PRIVATE KEY-----";
        $key = openssl_pkey_get_private($pem);
        if (!$key) {
            throw new RuntimeException('飞猪API的私钥无效。');
        }
        openssl_sign($dataToSign, $signature, $key, OPENSSL_ALGO_SHA256);
        openssl_free_key($key);
        return base64_encode($signature);
    }

    /**
     * 发送 POST 请求的核心方法。
     * @param string $path API路径
     * @param array $body 请求体
     * @return Response
     */
    private function post(string $path, array $body): Response
    {
        $fullUrl = $this->baseUrl . $path . '?format=json';
        Log::channel('fliggy')->info('飞猪 API 请求:', ['url' => $fullUrl, 'body' => $body]);
        $response = Http::post($fullUrl, $body);
        Log::channel('fliggy')->info('飞猪 API 响应:', ['status' => $response->status(), 'body' => $response->json() ?? $response->body()]);
        return $response;
    }

    /**
     * 构建并发送请求的通用方法。
     *
     * @param string $path API 路径
     * @param array $params 请求参数
     * @param array $signKeys 用于签名的参数键名数组
     * @return Response
     */
    public function send(string $path, array $params, array $signKeys): Response
    {
        $timestamp = round(microtime(true) * 1000);

        $signValues = [$this->distributorId, $timestamp];
        foreach ($signKeys as $key) {
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

    /**
     * [2.1] 分页获取产品基本信息
     */
    public function queryProductBaseInfoByPage(int $pageNo = 1, int $pageSize = 10): Response
    {
        return $this->send('/api/v1/hotelticket/queryProductBaseInfoByPage', ['pageNo' => $pageNo, 'pageSize' => $pageSize], []);
    }

    /**
     * [2.2] 批量获取产品基本信息
     */
    public function queryProductBaseInfoByIds(array $productIds): Response
    {
        return $this->send('/api/v1/hotelticket/queryProductBaseInfoByIds', ['productIds' => $productIds], ['productIds']);
    }

    /**
     * [2.3] 获取产品详情
     */
    public function queryProductDetailInfo(string $productId): Response
    {
        return $this->send('/api/v1/hotelticket/queryProductDetailInfo', ['productId' => $productId], ['productId']);
    }

    /**
     * [2.4] 获取价格/库存信息
     */
    public function queryProductPriceStock(string $productId, ?string $beginTime = null, ?string $endTime = null): Response
    {
        $params = ['productId' => $productId];
        if ($beginTime) $params['beginTime'] = $beginTime;
        if ($endTime) $params['endTime'] = $endTime;
        return $this->send('/api/v1/hotelticket/queryProductPriceStock', $params, ['productId']);
    }

    /**
     * [3.1] 校验（预下单）接口
     */
    public function validateOrder(array $orderData): Response
    {
        $timestamp = round(microtime(true) * 1000);
        $productIdValue = data_get($orderData, 'productInfo.productId');

        $stringToSign = implode('_', [$this->distributorId, $timestamp, $productIdValue]);

        $body = array_merge([
            'distributorId' => $this->distributorId,
            'timestamp' => $timestamp,
            'sign' => $this->generateSign($stringToSign),
        ], $orderData);

        return $this->post('/api/v1/hotelticket/validateOrder', $body);
    }

    /**
     * [3.3] 创建订单接口
     */
    public function createOrder(array $orderData): Response
    {
        $timestamp = round(microtime(true) * 1000);
        $productIdValue = data_get($orderData, 'productInfo.productId');

        $stringToSign = implode('_', [$this->distributorId, $timestamp, $productIdValue]);

        $body = array_merge([
            'distributorId' => $this->distributorId,
            'timestamp' => $timestamp,
            'sign' => $this->generateSign($stringToSign),
        ], $orderData);

        return $this->post('/api/v1/hotelticket/createOrder', $body);
    }
}
