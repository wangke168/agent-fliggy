<?php

namespace App\Http\Controllers\SaaS;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use App\Models\Ota;
use App\Models\Product;
use App\Models\Tourist;
use App\Services\Ctrip\Client as CtripClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    // ... index, create, store methods

    public function edit(Product $product): View
    {
        // ... existing edit logic
    }

    public function update(Request $request, Product $product, CtripClient $ctripClient): RedirectResponse
    {
        $this->authorize('update', $product);

        $validated = $request->validate([
            // ... validation rules
        ]);

        try {
            DB::transaction(function () use ($product, $validated, $request) {
                // ... (update product, otas, hotels, roomtypes, inventories)
            });

            // Check if Ctrip is one of the selected OTAs
            $isCtripSelected = in_array(Ota::where('name', 'Ctrip')->first()->id, $validated['ota_ids'] ?? []);

            if ($isCtripSelected) {
                $product->load('inventories', 'roomtypes');

                // 1. Sync Inventory
                if ($product->inventories->isNotEmpty()) {
                    $inventoriesData = $product->inventories->map(function ($item) {
                        return ['date' => $item->inventory_date->format('Y-m-d'), 'quantity' => $item->stock];
                    })->all();

                    // We assume one product maps to one supplierOptionId for simplicity here.
                    // In a real scenario, this might need to sync per room type.
                    $supplierOptionId = $product->roomtypes->first()->id ?? null;

                    if ($supplierOptionId) {
                        $ctripClient->dateInventoryModify(
                            sequenceId: now()->format('Ymd') . Str::uuid()->toString(),
                            supplierOptionId: $supplierOptionId,
                            dateType: 'DATE_REQUIRED',
                            inventories: $inventoriesData
                        );
                    }
                }

                // 2. Sync Price
                // We'll create a price entry for the next 30 days based on the base_price
                $prices = [];
                $startDate = now();
                for ($i = 0; $i < 30; $i++) {
                    $prices[] = [
                        'date' => $startDate->copy()->addDays($i)->format('Y-m-d'),
                        'salePrice' => $product->base_price,
                        'costPrice' => $product->base_price, // Assuming cost price is same as base for now
                    ];
                }

                $supplierOptionId = $product->roomtypes->first()->id ?? null;
                if ($supplierOptionId) {
                    $ctripClient->datePriceModify(
                        sequenceId: now()->format('Ymd') . Str::uuid()->toString(),
                        supplierOptionId: $supplierOptionId,
                        dateType: 'DATE_REQUIRED',
                        prices: $prices
                    );
                }
            }

        } catch (\Exception $e) {
            Log::error('Failed to update product or sync with Ctrip: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Failed to update product. Please check the logs.');
        }

        return redirect()->route('saas.products.index')->with('success', '产品已成功更新，并已开始同步到携程！');
    }
}
