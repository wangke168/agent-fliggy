<?php

namespace App\Http\Controllers;

use App\Services\CtripClient;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CtripWebhookController extends Controller
{
    /**
     * Handle all order notifications from Ctrip.
     */
    public function handleOrderNotice(Request $request, CtripClient $ctripClient): JsonResponse
    {
        $payload = $request->all();
        Log::channel('ctrip')->info('Ctrip Order Webhook Received:', $payload);

        // 1. Decrypt Body
        $decryptedBody = null;
        if (isset($payload['body'])) {
            try {
                $decryptedBodyJson = $ctripClient->decryptBody($payload['body']);
                $decryptedBody = json_decode($decryptedBodyJson, true);
            } catch (\Exception $e) {
                Log::channel('ctrip')->error('Failed to decrypt Ctrip webhook body.', ['error' => $e->getMessage()]);
                return $this->buildErrorResponse('9999', 'Decryption failed.');
            }
        }

        if (!$decryptedBody) {
            return $this->buildErrorResponse('9998', 'Decrypted body is empty or invalid JSON.');
        }

        Log::channel('ctrip')->info('Decrypted Ctrip Order Webhook Body:', ['decrypted_body' => $decryptedBody]);

        // 2. Route to specific handler based on serviceName
        $serviceName = $payload['header']['serviceName'] ?? null;
        switch ($serviceName) {
            case 'CreatePreOrder':
                return $this->handleCreatePreOrder($decryptedBody);
            // Add other cases for PayPreOrder, CancelPreOrder etc.
            // case 'PayPreOrder':
            //     return $this->handlePayPreOrder($decryptedBody);
            default:
                Log::channel('ctrip')->warning('Unhandled serviceName in webhook.', ['serviceName' => $serviceName]);
                return $this->buildErrorResponse('9997', "Unhandled serviceName: {$serviceName}");
        }
    }

    /**
     * Handle the CreatePreOrder request.
     */
    private function handleCreatePreOrder(array $body): JsonResponse
    {
        // Business logic for creating a pre-order.
        // 1. Validate the incoming data (e.g., check if PLU exists, check price, check stock).
        // 2. If validation passes, create an order in your system with a "pending_payment" status.
        // 3. Generate your own unique order ID.

        $otaOrderId = $body['otaOrderId'];
        $supplierOrderId = 'S' . time() . Str::random(4); // Generate a unique supplier order ID

        // For now, we assume success and return the required response.
        $responseBody = [
            'otaOrderId' => $otaOrderId,
            'supplierOrderId' => $supplierOrderId,
            // 'items' can be included here if there are issues, e.g., with stock.
        ];

        return $this->buildSuccessResponse($responseBody, 'CreatePreOrder');
    }

    /**
     * Build a standard success response.
     */
    private function buildSuccessResponse(array $bodyData, string $serviceName): JsonResponse
    {
        $ctripClient = new CtripClient(); // Needed for encryption
        $header = [
            'resultCode' => '0000',
            'resultMessage' => 'Success',
        ];

        $responsePayload = [
            'header' => $header,
            'body' => $ctripClient->encryptBody(json_encode($bodyData, JSON_UNESCAPED_SLASHES)),
        ];

        Log::channel('ctrip')->info("Responding to {$serviceName} webhook.", [
            'response' => $responsePayload,
            'decrypted_body' => $bodyData
        ]);

        return response()->json($responsePayload);
    }

    /**
     * Build a standard error response.
     */
    private function buildErrorResponse(string $code, string $message): JsonResponse
    {
        $header = [
            'resultCode' => $code,
            'resultMessage' => $message,
        ];

        Log::channel('ctrip')->error("Responding with error.", ['header' => $header]);

        return response()->json(['header' => $header]);
    }
}
