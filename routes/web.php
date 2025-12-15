<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

// New, organized controller and service paths
use App\Http\Controllers\SaaS\ProductController as SaasProductController;
use App\Http\Controllers\SaaS\HotelController as SaasHotelController;
use App\Http\Controllers\Test\CtripTestController;
use App\Http\Controllers\Fliggy\ProductController as FliggyProductController;
use App\Http\Controllers\Webhook\FliggyController as FliggyWebhookController;
use App\Http\Controllers\Webhook\HengdianController as HengdianWebhookController;
use App\Http\Controllers\Webhook\CtripController as CtripWebhookController;
use App\Services\Fliggy\Client as FliggyClient;
use App\Services\Hengdian\Client as HengdianClient;
use App\Services\Ctrip\Client as CtripClient;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    // Redirect to the main SaaS product page for convenience
    return redirect()->route('saas.products.index');
});

/*
|--------------------------------------------------------------------------
| SaaS Application Routes
|--------------------------------------------------------------------------
*/
Route::prefix('saas')->name('saas.')->group(function () {
    // Product Management
    Route::resource('products', SaasProductController::class)->except(['show']);

    // Hotel Resource Management
    Route::resource('hotels', SaasHotelController::class)->except(['show']);

    // API for dynamic forms
    Route::get('/api/hotels/{hotel}/roomtypes', [SaasHotelController::class, 'getRoomTypes'])->name('api.hotels.roomtypes');
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
    Route::prefix('ctrip')->name('ctrip.')->group(function () {
        Route::post('/', [CtripWebhookController::class, 'handleOrderNotice'])->name('notice');
    });
});


/*
|--------------------------------------------------------------------------
| Legacy & Test Routes (Kept for reference)
|--------------------------------------------------------------------------
*/

// Fliggy Product Pages (Legacy)
Route::prefix('products')->name('products.')->group(function () {
    Route::get('/', [FliggyProductController::class, 'index'])->name('index');
    Route::get('/{productId}', [FliggyProductController::class, 'show'])->name('show');
    Route::post('/{productId}/book', [FliggyProductController::class, 'storeBooking'])->name('book');
});

// Test Routes
Route::prefix('test')->name('test.')->group(function () {
    // Hengdian Test Routes
    Route::get('/hengdian-validate', function (HengdianClient $hengdianClient) {
        $responseXml = $hengdianClient->useTestEnvironment()->validate(
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

    // Ctrip Price & Stock Sync Test Routes
    Route::get('/ctrip-price-sync', [CtripTestController::class, 'testPriceSync'])->name('ctrip.price-sync');
    Route::get('/ctrip-inventory-sync', [CtripTestController::class, 'testInventorySync'])->name('ctrip.inventory-sync');
    Route::get('/ctrip-price-sync-not-required', [CtripTestController::class, 'testPriceSyncNotRequired'])->name('ctrip.price-sync-not-required');
    Route::get('/ctrip-inventory-sync-not-required', [CtripTestController::class, 'testInventorySyncNotRequired'])->name('ctrip.inventory-sync-not-required');

    // Ctrip Order Webhook Simulation
    Route::get('/ctrip-pre-order-test', [CtripTestController::class, 'testCreatePreOrder'])->name('ctrip.pre-order-test');
});
