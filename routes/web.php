<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FliggyWebhookController;
use App\Http\Controllers\ProductController;
use App\Services\FliggyClient;
use App\Services\HengdianClient;

Route::get('/', function () {
    return view('welcome');
});

// Main application routes for Fliggy
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{productId}', [ProductController::class, 'show'])->name('products.show');
Route::post('/products/{productId}/book', [ProductController::class, 'storeBooking'])->name('products.book');


Route::prefix('api/webhooks/fliggy')->group(function () {
    Route::post('product-change', [FliggyWebhookController::class, 'handleProductChange'])->name('fliggy.webhooks.product-change');
    Route::post('order-status', [FliggyWebhookController::class, 'handleOrderStatus'])->name('fliggy.webhooks.order-status');
});

/*
|--------------------------------------------------------------------------
| Test Routes
|--------------------------------------------------------------------------
*/
Route::prefix('test')->group(function () {
    // Fliggy Test Routes
    Route::get('/fliggy-products-by-ids', function (FliggyClient $fliggyClient) {
        $ids = request()->input('ids');
        if (empty($ids)) {
            return response()->json(['error' => 'Please provide product IDs. Example: ?ids=123,456'], 400);
        }
        $productIds = explode(',', $ids);
        return $fliggyClient->usePreEnvironment()->queryProductBaseInfoByIds($productIds)->json();
    })->name('test.fliggy.products-by-ids');

    Route::get('/clear-product-cache/{productId}', function ($productId) {
        $cacheKey = 'fliggy_product_' . $productId;
        \Illuminate\Support\Facades\Cache::forget($cacheKey);
        return "Cache cleared for Fliggy product: " . e($productId);
    })->name('test.cache.clear');

    // Hengdian Test Route
    Route::get('/hengdian-validate', function (HengdianClient $hengdianClient) {
        $responseXml = $hengdianClient->validate(
            packageId: '1064',
            hotelId: '001',
            roomType: '标准间',
            checkIn: now()->addDay()->format('Y-m-d'),
            checkOut: now()->addDays(3)->format('Y-m-d')
        );

        // To make it easily viewable in the browser, we convert the XML to a JSON-like array
        if ($responseXml) {
            $json = json_encode($responseXml);
            $array = json_decode($json, TRUE);
            return response()->json($array);
        }

        return response()->json(['error' => 'Request failed or XML could not be parsed.'], 500);
    })->name('test.hengdian.validate');
});
