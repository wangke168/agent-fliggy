<?php

namespace App\Http\Controllers;

use App\Services\FliggyClient;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    /**
     * Display a listing of the products from Fliggy API.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Services\FliggyClient $fliggyClient
     * @return \Illuminate\View\View
     */
    public function index(Request $request, FliggyClient $fliggyClient): View
    {
        $page = (int) $request->input('page', 1);
        $size = (int) $request->input('size', 10);

        // Ensure page is at least 1
        if ($page < 1) {
            $page = 1;
        }

        $products = [];
        $error = null;
        $responseData = null;

        try {
            // Use the pre-production environment for testing
            $response = $fliggyClient->usePreEnvironment()->queryProductBaseInfoByPage($page, $size);
            $responseData = $response->json();

            if ($response->successful() && isset($responseData['success']) && $responseData['success']) {
                $products = $responseData['data']['productBaseInfos'] ?? [];
            } else {
                $error = $responseData['msg'] ?? 'Failed to fetch products from Fliggy API.';
            }
        } catch (\Exception $e) {
            $error = $e->getMessage();
            // Also log the full error for debugging
            Log::channel('fliggy')->error('Failed to call Fliggy API in ProductController: ' . $e->getMessage());
        }

        return view('products.index', [
            'products' => $products,
            'currentPage' => $page,
            'hasMorePages' => !empty($products) && count($products) === $size, // Simple pagination logic
            'error' => $error,
            'rawResponse' => json_encode($responseData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        ]);
    }
}
