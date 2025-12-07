<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FliggyWebhookController;
use App\Http\Controllers\HengdianWebhookController;
use App\Http\Controllers\CtripWebhookController;
use App\Http\Controllers\ProductController;
use App\Services\FliggyClient;
use App\Services\HengdianClient;
use App\Services\CtripClient;
use Illuminate\Support\Str;

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
    Route::prefix('ctrip')->name('ctrip.')->group(function () {
        // This is the new, cleaner URL for Ctrip order webhooks
        Route::post('/', [CtripWebhookController::class, 'handleOrderNotice'])->name('notice');
    });
});


/*
|--------------------------------------------------------------------------
| Test Routes
|--------------------------------------------------------------------------
*/
Route::prefix('test')->name('test.')->group(function () {
    // ... other test routes ...

    // Ctrip Test Routes
    Route::get('/ctrip-price-modify', function (CtripClient $ctripClient) {
        $prices = [
            ['date' => now()->addDay()->format('Y-m-d'), 'salePrice' => '200.00', 'costPrice' => '180.00'],
            ['date' => now()->addDays(2)->format('Y-m-d'), 'salePrice' => '200.00', 'costPrice' => '180.00'],
        ];
        $result = $ctripClient->datePriceModify(
            sequenceId: now()->format('Y-m-d') . Str::uuid()->toString(),
            otaOptionId: null,
            supplierOptionId: '1001',
            dateType: 'DATE_REQUIRED',
            prices: $prices
        );
        return response()->json($result);
    })->name('ctrip.price-modify');

    Route::get('/ctrip-inventory-modify', function (CtripClient $ctripClient) {
        $inventories = [
            ['date' => now()->addDay()->format('Y-m-d'), 'quantity' => 15],
            ['date' => now()->addDays(2)->format('Y-m-d'), 'quantity' => 20],
        ];
        $result = $ctripClient->dateInventoryModify(
            sequenceId: now()->format('Y-m-d') . Str::uuid()->toString(),
            otaOptionId: null,
            supplierOptionId: '1001',
            dateType: 'DATE_REQUIRED',
            inventories: $inventories
        );
        return response()->json($result);
    })->name('ctrip.inventory-modify');

    Route::get('/ctrip-order-confirm', function (CtripClient $ctripClient) {
        // Example data - you should replace these with actual data from a webhook notification
        $sequenceId = now()->format('Ymd') . Str::uuid()->toString();
        $otaOrderId = '123456789';
        $supplierOrderId = 'S' . time();
        $items = [
            [
                'itemId' => 'ITEM001',
                'isCredentialVouchers' => 0,
            ]
        ];

        $result = $ctripClient->payPreOrderConfirm(
            sequenceId: $sequenceId,
            otaOrderId: $otaOrderId,
            supplierOrderId: $supplierOrderId,
            items: $items
        );
        return response()->json($result);
    })->name('ctrip.order-confirm');
});
