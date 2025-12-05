<?php

namespace App\Http\Controllers;

use App\Services\FliggyClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;

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

        $cacheKey = 'fliggy_product_' . $productId;

        try {
            $cachedData = Cache::remember($cacheKey, 600, function () use ($productId, $fliggyClient) {
                $detailResponse = $fliggyClient->usePreEnvironment()->queryProductDetailInfo($productId);
                $priceStockResponse = $fliggyClient->usePreEnvironment()->queryProductPriceStock($productId);

                $detailData = $detailResponse->json();
                $priceData = $priceStockResponse->json();

                if (!$detailResponse->successful() || !($detailData['success'] ?? false)) {
                    throw new \Exception($detailData['msg'] ?? 'Failed to fetch product detail.');
                }
                if (!$priceStockResponse->successful() || !($priceData['success'] ?? false)) {
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
     * Handle the booking form submission by validating the order.
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

            // Construct the order data for validation
            $orderData = [
                'outOrderId' => 'TEST-' . time(), // Unique external order ID
                'productInfo' => [
                    'productId' => (int)$productId,
                    'price' => $priceInCents,
                    'quantity' => 1,
                    'travelDate' => $validated['selected_date'],
                ],
                'contactInfo' => [
                    'name' => $validated['name'],
                    'mobile' => $validated['mobile'],
                ],
                'travellerInfos' => [
                    [
                        'name' => $validated['name'],
                        'certificatesType' => 3, // 3 = ID Card
                        'certificates' => $validated['id_card'],
                    ],
                ],
                'totalPrice' => $priceInCents,
            ];

            // Call the validateOrder API
            $response = $fliggyClient->usePreEnvironment()->validateOrder($orderData);
            $responseData = $response->json();

            if ($response->successful() && isset($responseData['success']) && $responseData['success']) {
                return redirect()->route('products.show', $productId)
                                 ->with('booking_success', 'Validation successful! This booking is valid.');
            } else {
                $errorMessage = $responseData['msg'] ?? 'The booking is not valid.';
                return back()->with('booking_error', 'Validation Failed: ' . $errorMessage)
                             ->withInput(); // Keep form data
            }

        } catch (\Exception $e) {
            Log::channel('fliggy')->error('Exception during order validation: ' . $e->getMessage());
            return back()->with('booking_error', 'An unexpected error occurred: ' . $e->getMessage());
        }
    }
}
