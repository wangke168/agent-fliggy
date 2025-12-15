<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Services\Ctrip\Client as CtripClient;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * 处理所有来自携程的 Webhook 推送。
 */
class CtripController extends Controller
{
    /**
     * 统一处理所有携程订单通知的入口点。
     */
    public function handleOrderNotice(Request $request, CtripClient $ctripClient): JsonResponse
    {
        $payload = $request->all();
        Log::channel('ctrip')->info('收到携程订单 Webhook:', $payload);

        // 1. 解密 Body
        $decryptedBody = null;
        if (isset($payload['body'])) {
            try {
                $decryptedBodyJson = $ctripClient->decryptBody($payload['body']);
                $decryptedBody = json_decode($decryptedBodyJson, true);
            } catch (\Exception $e) {
                Log::channel('ctrip')->error('解密携程 Webhook Body 失败。', ['error' => $e->getMessage()]);
                return $this->buildErrorResponse('9999', '解密失败。');
            }
        }

        if (!$decryptedBody) {
            return $this->buildErrorResponse('9998', '解密后的 Body 为空或非法的 JSON。');
        }

        Log::channel('ctrip')->info('解密后的携程订单 Webhook Body:', ['decrypted_body' => $decryptedBody]);

        // 2. 根据 serviceName 路由到具体的处理方法
        $serviceName = $payload['header']['serviceName'] ?? null;
        switch ($serviceName) {
            case 'CreatePreOrder':
                return $this->handleCreatePreOrder($decryptedBody);
            // TODO: 在此为其他订单通知（如支付、取消）添加 case
            // case 'PayPreOrder':
            //     return $this->handlePayPreOrder($decryptedBody);
            default:
                Log::channel('ctrip')->warning('未处理的 Webhook serviceName。', ['serviceName' => $serviceName]);
                return $this->buildErrorResponse('9997', "未处理的服务名: {$serviceName}");
        }
    }

    /**
     * 处理创建预下单（CreatePreOrder）请求。
     */
    private function handleCreatePreOrder(array $body): JsonResponse
    {
        // TODO: 在此实现您的业务逻辑
        // 1. 验证传入的数据（例如，检查 PLU 是否存在，价格是否匹配，库存是否充足）。
        // 2. 如果验证通过，在您的数据库中创建一个状态为“待支付”的订单。
        // 3. 生成一个您系统内部唯一的订单号。

        $otaOrderId = $body['otaOrderId'];
        $supplierOrderId = 'S' . time() . Str::random(4); // 示例：生成一个唯一的供应商订单号

        // 暂时我们假设业务逻辑总是成功，并返回携程要求的成功响应。
        $responseBody = [
            'otaOrderId' => $otaOrderId,
            'supplierOrderId' => $supplierOrderId,
            // 如果有业务问题（如库存不足），可以在 'items' 节点中返回详细信息。
        ];

        return $this->buildSuccessResponse($responseBody, 'CreatePreOrder');
    }

    /**
     * 构建一个标准的成功响应（加密 Body）。
     */
    private function buildSuccessResponse(array $bodyData, string $serviceName): JsonResponse
    {
        $ctripClient = new CtripClient(); // 用于加密
        $header = [
            'resultCode' => '0000',
            'resultMessage' => 'Success',
        ];

        $responsePayload = [
            'header' => $header,
            'body' => $ctripClient->encryptBody(json_encode($bodyData, JSON_UNESCAPED_SLASHES)),
        ];

        Log::channel('ctrip')->info("响应 {$serviceName} Webhook:", [
            'response' => $responsePayload,
            'decrypted_body' => $bodyData
        ]);

        return response()->json($responsePayload);
    }

    /**
     * 构建一个标准的错误响应（不加密 Body）。
     */
    private function buildErrorResponse(string $code, string $message): JsonResponse
    {
        $header = [
            'resultCode' => $code,
            'resultMessage' => $message,
        ];

        Log::channel('ctrip')->error("响应错误:", ['header' => $header]);

        return response()->json(['header' => $header]);
    }
}
