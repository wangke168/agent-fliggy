<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FliggyWebhookController;
use App\Http\Controllers\ProductController;
use App\Services\FliggyClient;

Route::get('/', function () {
    return view('welcome');
});

// Main application route for displaying products
Route::get('/products', [ProductController::class, 'index'])->name('products.index');


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

// The old test route is now replaced by the /products route handled by ProductController.
// You can still use the other test routes.

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


Route::get('/test-fliggy-product-detail/{productId}', function (FliggyClient $fliggyClient, string $productId) {
    // Use the pre-production environment for testing
    $response = $fliggyClient->usePreEnvironment()->queryProductDetailInfo($productId);

    return $response->json();
})->name('test.fliggy.product-detail');
