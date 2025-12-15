<?php

namespace App\Http\Controllers\Fliggy;

use App\Http\Controllers\Controller;
use App\Services\Fliggy\Client as FliggyClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\RedirectResponse;

/**
 * (遗留测试) 用于展示飞猪产品列表和详情页的控制器。
 * 注意：此类中的方法使用了旧的视图，仅为保留历史测试功能。
 * 新的SaaS功能应使用 App\Http\Controllers\SaaS\ProductController。
 */
class ProductController extends Controller
{
    /**
     * 从飞猪API获取产品列表并展示。
     */
    public function index(Request $request, FliggyClient $fliggyClient): View
    {
        $page = (int) $request->input('page', 1);
        $size = (int) $request->input('size', 10);
        if ($page < 1) $page = 1;

        $products = [];
        $error = null;
        $responseData = null;

        try {
            $response = $fliggyClient->usePreEnvironment()->queryProductBaseInfoByPage($page, $size);
            $responseData = $response->json();

            if ($response->successful() && isset($responseData['success']) && $responseData['success']) {
                $products = $responseData['data']['productBaseInfos'] ?? [];
            } else {
                $error = $responseData['msg'] ?? '获取飞猪产品失败。';
            }
        } catch (\Exception $e) {
            $error = $e->getMessage();
            Log::channel('fliggy')->error('调用飞猪产品列表接口失败: ' . $e->getMessage());
        }

        return view('products.index', [
            'products' => $products,
            'currentPage' => $page,
            'hasMorePages' => !empty($products) && count($products) === $size,
            'error' => $error,
            'rawResponse' => json_encode($responseData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        ]);
    }

    /**
     * 从飞猪API获取单个产品详情并展示。
     */
    public function show(string $productId, FliggyClient $fliggyClient): View
    {
        $productDetail = null;
        $priceStock = null;
        $error = null;

        $cacheKey = 'fliggy_product_' . $productId;

        try {
            $cachedData = Cache::remember($cacheKey, 600, function () use ($productId, $fliggyClient) {
                $detailResponse = $fliggyClient->usePreEnvironment()->queryProductDetailInfo($productId);
                $priceStockResponse = $fliggyClient->usePreEnvironment()->queryProductPriceStock($productId);

                $detailData = $detailResponse->json();
                $priceData = $priceStockResponse->json();

                if (!$detailResponse->successful() || !($detailData['success'] ?? false)) {
                    throw new \Exception($detailData['msg'] ?? '获取产品详情失败。');
                }
                if (!$priceStockResponse->successful() || !($priceData['success'] ?? false)) {
                    Log::channel('fliggy')->warning('获取产品价格库存失败 ' . $productId, $priceData);
                }

                return [
                    'productDetail' => $detailData['data'] ?? null,
                    'priceStock' => $priceData['data'] ?? null,
                ];
            });

            $productDetail = $cachedData['productDetail'];
            $priceStock = $cachedData['priceStock'];

        } catch (\Exception $e) {
            $error = $e->getMessage();
            Log::channel('fliggy')->error('获取飞猪产品详情异常 ' . $productId . ': ' . $e->getMessage());
            Cache::forget($cacheKey);
        }

        return view('products.show', [
            'productId' => $productId,
            'productDetail' => $productDetail,
            'priceStock' => $priceStock,
            'error' => $error,
        ]);
    }

    /**
     * 处理飞猪产品的预下单(validateOrder)请求。
     */
    public function storeBooking(Request $request, string $productId, FliggyClient $fliggyClient): RedirectResponse
    {
        $validated = $request->validate([
            'selected_date' => 'required|date_format:Y-m-d',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:1',
            'name' => 'required|string|max:255',
            'mobile' => ['required', 'string', 'regex:/^1[3-9]\d{9}$/'],
            'id_card' => ['required', 'string', 'regex:/(^\d{15}$)|(^\d{18}$)|(^\d{17}(\d|X|x)$)/'],
        ]);

        try {
            $priceInCents = (int)($validated['price'] * 100);

            $orderData = [
                'outOrderId' => 'TEST-' . time(),
                'productInfo' => [
                    'productId' => (int)$productId,
                    'price' => $priceInCents,
                    'quantity' => 1,
                    'travelDate' => $validated['selected_date'],
                ],
                'contactInfo' => ['name' => $validated['name'], 'mobile' => $validated['mobile']],
                'travellerInfos' => [['name' => $validated['name'], 'certificatesType' => 3, 'certificates' => $validated['id_card']]],
                'totalPrice' => $priceInCents,
            ];

            $response = $fliggyClient->usePreEnvironment()->validateOrder($orderData);
            $responseData = $response->json();

            if ($response->successful() && isset($responseData['success']) && $responseData['success']) {
                return redirect()->route('products.show', $productId)
                                 ->with('booking_success', '飞猪预下单校验成功！');
            } else {
                $errorMessage = $responseData['msg'] ?? '预下单校验失败。';
                return back()->with('booking_error', '校验失败: ' . $errorMessage)->withInput();
            }

        } catch (\Exception $e) {
            Log::channel('fliggy')->error('飞猪预下单异常: ' . $e->getMessage());
            return back()->with('booking_error', '发生意外错误: ' . $e->getMessage());
        }
    }
}
