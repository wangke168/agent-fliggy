<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FliggyWebhookController;
use App\Http\Controllers\HengdianWebhookController;
use App\Http\Controllers\ProductController;
use App\Services\FliggyClient;
use App\Services\HengdianClient;

Route::get('/', function () {
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| Fliggy Routes
|--------------------------------------------------------------------------
*/
Route::prefix('products')->name('products.')->group(function () {
    Route::get('/', [ProductController::class, 'index'])->name('index');
    Route::get('/{productId}', [ProductController::class, 'show'])->name('show');
    Route::post('/{productId}/book', [ProductController::class, 'storeBooking'])->name('book');
});

/*
|--------------------------------------------------------------------------
| Webhook Routes
|--------------------------------------------------------------------------
*/
Route::prefix('api/webhooks')->name('webhooks.')->group(function () {
    Route::prefix('fliggy')->name('fliggy.')->group(function () {
        Route::post('product-change', [FliggyWebhookController::class, 'handleProductChange'])->name('product-change');
        Route::post('order-status', [FliggyWebhookController::class, 'handleOrderStatus'])->name('order-status');
    });
    Route::prefix('hengdian')->name('hengdian.')->group(function () {
        Route::post('room-status', [HengdianWebhookController::class, 'handleRoomStatus'])->name('room-status');
    });
});


/*
|--------------------------------------------------------------------------
| Test Routes
|--------------------------------------------------------------------------
*/
Route::prefix('test')->name('test.')->group(function () {
    // Fliggy Test Routes
    Route::get('/fliggy-products-by-ids', function (FliggyClient $fliggyClient) {
        $ids = request()->input('ids');
        if (empty($ids)) {
            return response()->json(['error' => 'Please provide product IDs. Example: ?ids=123,456'], 400);
        }
        $productIds = explode(',', $ids);
        return $fliggyClient->usePreEnvironment()->queryProductBaseInfoByIds($productIds)->json();
    })->name('fliggy.products-by-ids');

    Route::get('/clear-product-cache/{productId}', function ($productId) {
        $cacheKey = 'fliggy_product_' . $productId;
        \Illuminate\Support\Facades\Cache::forget($cacheKey);
        return "Cache cleared for Fliggy product: " . e($productId);
    })->name('cache.clear');

    // Hengdian Test Routes
    Route::get('/hengdian-validate', function (HengdianClient $hengdianClient) {
        $responseXml = $hengdianClient->validate(
            packageId: '3312',
            hotelId: '001',
            roomType: '大床房',
            checkIn: now()->addDay(4)->format('Y-m-d'),
            checkOut: now()->addDays(5)->format('Y-m-d'),
            paymentType: 1
        );
        if ($responseXml) {
            return response()->json(json_decode(json_encode($responseXml), TRUE));
        }
        return response()->json(['error' => 'Request failed or XML could not be parsed.'], 500);
    })->name('hengdian.validate');

    Route::get('/hengdian-subscribe', function (HengdianClient $hengdianClient) {
        // IMPORTANT: You must expose your local server to the internet for this to work. Use a tool like ngrok.
        $notifyUrl = route('webhooks.hengdian.room-status');

        $hotelsToSubscribe = [
            ['hotelId' => '001', 'roomTypes' => ['标准间', '大床房']],
        ];

        $responseXml = $hengdianClient->subscribeRoomStatus(
            notifyUrl: $notifyUrl,
            hotels: $hotelsToSubscribe
        );

        if ($responseXml && (string)$responseXml->ResultCode === '0') {
            return response()->json([
                'success' => true,
                'message' => 'Subscription request sent successfully.',
                'notify_url' => $notifyUrl,
                'response' => json_decode(json_encode($responseXml), TRUE)
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Subscription request failed.',
            'notify_url' => $notifyUrl,
            'response' => $responseXml ? json_decode(json_encode($responseXml), TRUE) : 'Request failed.'
        ], 500);
    })->name('hengdian.subscribe');
});
