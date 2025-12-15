<?php

namespace App\Http\Controllers\Test;

use App\Http\Controllers\Controller;
use App\Services\Ctrip\Client as CtripClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class CtripTestController extends Controller
{
    protected CtripClient $client;

    public function __construct(CtripClient $client)
    {
        $this->client = $client;
    }

    // ... existing test methods

    /**
     * Test CreatePreOrder Webhook
     */
    public function testCreatePreOrder(): JsonResponse
    {
        // 1. Mock the request body from Ctrip
        $mockBody = [
            'sequenceId' => now()->format('Ymd') . Str::uuid()->toString(),
            'otaOrderId' => 'TEST' . time(),
            'contacts' => [
                [
                    'name' => '测试用户',
                    'mobile' => '13800138000',
                    'intlCode' => '86',
                ]
            ],
            'items' => [
                [
                    'PLU' => '1001', // This should correspond to a product in your system
                    'locale' => 'zh-CN',
                    'useStartDate' => now()->addDays(10)->format('Y-m-d'),
                    'useEndDate' => now()->addDays(10)->format('Y-m-d'),
                    'price' => 150.00,
                    'priceCurrency' => 'CNY',
                    'salePrice' => 150.00,
                    'salePriceCurrency' => 'CNY',
                    'quantity' => 1,
                    'passengers' => [
                        [
                            'passengerId' => '1',
                            'name' => '张三',
                            'cardType' => '1',
                            'cardNo' => '310101200001011234',
                        ]
                    ]
                ]
            ]
        ];

        // 2. Build the full encrypted request payload
        $serviceName = 'CreatePreOrder';
        $requestTime = now()->format('Y-m-d H:i:s');
        $version = '1.0';
        $encryptedBody = $this->client->encryptBody(json_encode($mockBody));

        // This is a simplified way to get the sign key. In a real app, it's better to get it from the client itself.
        $config = config('services.ctrip');
        $signKey = $config['sign_key'];
        $accountId = $config['account_id'];

        $stringToSign = $accountId . $serviceName . $requestTime . $encryptedBody . $version . $signKey;
        $sign = strtolower(md5($stringToSign));

        $payload = [
            'header' => [
                'accountId' => $accountId,
                'serviceName' => $serviceName,
                'requestTime' => $requestTime,
                'version' => $version,
                'sign' => $sign,
            ],
            'body' => $encryptedBody,
        ];

        // 3. Send the request to our own webhook endpoint
        $response = Http::post(route('webhooks.ctrip.notice'), $payload);

        // 4. Return the response from our webhook
        return response()->json([
            'test_status' => 'Simulated request sent to our webhook.',
            'simulated_request_payload' => $payload,
            'decrypted_request_body_we_sent' => $mockBody,
            'webhook_response' => $response->json(),
        ]);
    }
}
