<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FliggyWebhookController;
use App\Http\Controllers\ProductController;
use App\Services\FliggyClient;

Route::get('/', function () {
    return view('welcome');
});

// Main application routes
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{productId}', [ProductController::class, 'show'])->name('products.show');
Route::post('/products/{productId}/book', [ProductController::class, 'storeBooking'])->name('products.book');


Route::prefix('api/webhooks/fliggy')->group(function () {
    Route::post('product-change', [FliggyWebhookController::class, 'handleProductChange'])->name('fliggy.webhooks.product-change');
    Route::post('order-status', [FliggyWebhookController::class, 'handleOrderStatus'])->name('fliggy.webhooks.order-status');
});

/**
 * =================================================================
 *  TEST ROUTES FOR FLIGGY API
 * =================================================================
 * These routes are for testing purposes. You can remove them later.
 */

Route::get('/test-fliggy-products-by-ids', function (FliggyClient $fliggyClient) {
    $ids = request()->input('ids');

    if (empty($ids)) {
        return response()->json(['error' => 'Please provide product IDs via the "ids" query parameter. Example: ?ids=123,456'], 400);
    }

    $productIds = explode(',', $ids);

    // Use the pre-production environment for testing
    $response = $fliggyClient->usePreEnvironment()->queryProductBaseInfoByIds($productIds);

    return $response->json();
})->name('test.fliggy.products-by-ids');


// The old /test-fliggy-product-detail/{productId} is now replaced by /products/{productId}
// and handled by the ProductController.

Route::get('/clear-product-cache/{productId}', function ($productId) {
    $cacheKey = 'fliggy_product_' . $productId;
    \Illuminate\Support\Facades\Cache::forget($cacheKey);
    return "Cache cleared for product: " . e($productId);
})->name('cache.clear');
