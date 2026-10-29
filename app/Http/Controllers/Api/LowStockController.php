<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class LowStockController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'threshold' => 'sometimes|integer|min:0',
        ]);

        $threshold = $request->query('threshold', 5);

        $products = Product::where(
            'stock_quantity',
            '<',
            $threshold
        )->orderBy('stock_quantity')->get();

        return response()->json([
            'threshold' => (int) $threshold,
            'count' => $products->count(),
            'products' => $products,
        ], 200);
    }
}