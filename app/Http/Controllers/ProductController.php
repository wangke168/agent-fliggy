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
     * Handle the booking form submission.
     */
    public function storeBooking(Request $request, string $productId): RedirectResponse
    {
        $validated = $request->validate([
            'selected_date' => 'required|date_format:Y-m-d',
            'name' => 'required|string|max:255',
            'mobile' => ['required', 'string', 'regex:/^1[3-9]\d{9}$/'],
            'id_card' => ['required', 'string', 'regex:/(^\d{15}$)|(^\d{18}$)|(^\d{17}(\d|X|x)$)/'],
        ]);

        // At this point, validation has passed.
        // Here you would typically call the Fliggy `createOrder` API.
        // For now, we will just log the data and return a success message.

        Log::channel('fliggy')->info('Booking form submitted and validated:', [
            'productId' => $productId,
            'validated_data' => $validated,
        ]);

        // Example of how you might call the createOrder API in the future:
        /*
        try {
            $fliggyClient = new FliggyClient();
            $orderData = [
                // ... construct the order data array based on Fliggy's API requirements ...
            ];
            $response = $fliggyClient->createOrder($orderData);
            if (!$response->successful() || !($response->json()['success'] ?? false)) {
                 return back()->with('booking_error', 'Fliggy API rejected the order: ' . $response->json()['msg']);
            }
        } catch (\Exception $e) {
            return back()->with('booking_error', 'An error occurred while creating the order: ' . $e->getMessage());
        }
        */

        return redirect()->route('products.show', $productId)
                         ->with('booking_success', 'Booking request for ' . $validated['selected_date'] . ' submitted successfully!');
    }
}
