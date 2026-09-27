<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    /**
     * Display products with stock_on_hand less than or equal to the configured low-stock threshold.
     */
    public function lowStock(): JsonResponse
    {
        $threshold = (int) config('inventory.low_stock_threshold', 5);

        $products = Product::where('stock_on_hand', '<=', $threshold)
            ->orderBy('stock_on_hand', 'asc')
            ->get();

        return response()->json([
            'data' => $products,
            'meta' => [
                'threshold' => $threshold,
                'total' => $products->count(),
            ],
        ]);
    }
}
