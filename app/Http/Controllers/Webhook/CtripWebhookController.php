<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ProductRoomtype;
use App\Services\Ctrip\Client as CtripClient;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CtripWebhookController extends Controller
{
    protected CtripClient $client;

    public function __construct(CtripClient $client)
    {
        $this->client = $client;
    }

    public function handleOrderNotice(Request $request): JsonResponse
    {
        $header = $request->input('header');
        $encryptedBody = $request->input('body');

        Log::channel('ctrip')->info('Received Ctrip Webhook:', $request->all());

        $body = $this->client->decryptBody($encryptedBody);
        if (!$body) {
            return $this->buildErrorResponse('0003', '报文解析失败 (Decryption failed)');
        }
        $bodyData = json_decode($body, true);
        Log::channel('ctrip')->info('Decrypted Body:', $bodyData);

        $serviceName = $header['serviceName'] ?? null;

        switch ($serviceName) {
            case 'CreatePreOrder':
                return $this->handleCreatePreOrder($bodyData);
            default:
                Log::channel('ctrip')->warning('Unhandled serviceName:', ['serviceName' => $serviceName]);
                return $this->buildErrorResponse('0004', '请求方法为空 (Unsupported serviceName)');
        }
    }

    private function handleCreatePreOrder(array $data): JsonResponse
    {
        $validator = Validator::make($data, [
            'otaOrderId' => 'required|string',
            'items' => 'required|array|min:1',
            'items.*.PLU' => 'required|string',
            'items.*.useStartDate' => 'required|date',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return $this->buildErrorResponse('1005', '信息缺失: ' . $validator->errors()->first());
        }

        $itemData = $data['items'][0];
        $plu = $itemData['PLU'];
        $useDate = $itemData['useStartDate'];
        $quantity = $itemData['quantity'];

        try {
            $order = DB::transaction(function () use ($data, $itemData, $plu, $useDate, $quantity) {
                $productRoomtype = ProductRoomtype::with('product', 'hotel')->find($plu);

                if (!$productRoomtype) {
                    throw new \Exception('产品PLU不存在/错误', 1001);
                }

                $inventory = $productRoomtype->inventories()->where('inventory_date', $useDate)->first();

                if (!$inventory || $inventory->stock < $quantity) {
                    throw new \Exception('库存不足', 1003);
                }

                // Lock stock
                $inventory->decrement('stock', $quantity);

                // Create local order
                $contact = $data['contacts'][0] ?? [];
                $passenger = $itemData['passengers'][0] ?? [];

                return Order::create([
                    'order_id' => 'S' . time() . rand(100, 999), // Local order ID
                    'order_source_id' => $data['otaOrderId'],
                    'order_source' => 'Ctrip',
                    'order_time' => now(),
                    'arrive_date' => $useDate,
                    'contact_name' => $contact['name'] ?? null,
                    'contact_tel' => $contact['mobile'] ?? null,
                    'guest_name' => $passenger['name'] ?? null,
                    'guest_tel' => $passenger['mobile'] ?? $contact['mobile'] ?? null,
                    'order_status' => 1, // 1: Pending Confirmation
                    'order_amount' => $itemData['price'],
                    'product_id' => $productRoomtype->product_id,
                    'hotel_id' => $productRoomtype->hotel_id,
                    'roomtype_id' => $productRoomtype->id,
                    'touist_id' => $productRoomtype->tourist_id,
                ]);
            });

            $responseBody = [
                'otaOrderId' => $data['otaOrderId'],
                'supplierOrderId' => $order->order_id,
            ];

            return $this->buildSuccessResponse($responseBody, '预下单创建成功');

        } catch (\Exception $e) {
            // Rollback is handled by DB::transaction, just need to return error
            return $this->buildErrorResponse((string)$e->getCode(), $e->getMessage());
        }
    }

    private function buildSuccessResponse(array $bodyData, string $message): JsonResponse
    {
        $encryptedBody = $this->client->encryptBody(json_encode($bodyData));
        return response()->json([
            'header' => ['resultCode' => '0000', 'resultMessage' => $message],
            'body' => $encryptedBody,
        ]);
    }

    private function buildErrorResponse(string $code, string $message): JsonResponse
    {
        return response()->json([
            'header' => ['resultCode' => $code, 'resultMessage' => $message]
        ]);
    }
}
