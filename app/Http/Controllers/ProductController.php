<?php

namespace App\Http\Controllers;

use App\Services\FliggyClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class ProductController extends Controller
{
    /**
     * Display a listing of the products from Fliggy API.
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
                $error = $responseData['msg'] ?? 'Failed to fetch products from Fliggy API.';
            }
        } catch (\Exception $e) {
            $error = $e->getMessage();
            Log::channel('fliggy')->error('Failed to call Fliggy API in ProductController: ' . $e->getMessage());
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
     * Display the specified product.
     */
    public function show(string $productId, FliggyClient $fliggyClient): View
    {
        $productDetail = null;
        $priceStock = null;
        $error = null;

        // Define a unique cache key for this product's data
        $cacheKey = 'fliggy_product_' . $productId;

        try {
            // Use cache to avoid hitting the API on every page load, for 10 minutes
            $cachedData = Cache::remember($cacheKey, 600, function () use ($productId, $fliggyClient) {
                $detailResponse = $fliggyClient->usePreEnvironment()->queryProductDetailInfo($productId);
                $priceStockResponse = $fliggyClient->usePreEnvironment()->queryProductPriceStock($productId);

                $detailData = $detailResponse->json();
                $priceData = $priceStockResponse->json();

                if (!$detailResponse->successful() || !($detailData['success'] ?? false)) {
                    throw new \Exception($detailData['msg'] ?? 'Failed to fetch product detail.');
                }
                if (!$priceStockResponse->successful() || !($priceData['success'] ?? false)) {
                    // Price info might not be critical, so we can choose to log instead of throwing
                    Log::channel('fliggy')->warning('Failed to fetch price/stock for ' . $productId, $priceData);
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
            Log::channel('fliggy')->error('Failed to get product details for ' . $productId . ': ' . $e->getMessage());
            Cache::forget($cacheKey); // Clear cache on error
        }

        return view('products.show', [
            'productId' => $productId,
            'productDetail' => $productDetail,
            'priceStock' => $priceStock,
            'error' => $error,
        ]);
    }
}
