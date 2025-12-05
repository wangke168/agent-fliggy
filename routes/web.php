<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FliggyWebhookController;
use App\Services\FliggyClient;

Route::get('/', function () {
    return view('welcome');
});

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

Route::get('/test-fliggy-products', function (FliggyClient $fliggyClient) {
    // You can test with different page numbers and sizes
    // e.g., /test-fliggy-products?page=2&size=20
    $page = request()->input('page', 1);
    $size = request()->input('size', 10);

    // Use the pre-production environment for testing
    $response = $fliggyClient->usePreEnvironment()->queryProductBaseInfoByPage($page, $size);

    return $response->json();
})->name('test.fliggy.products');


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
