<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Services\OrderService;

class OrderController extends Controller
{
    public function store(
        StoreOrderRequest $request,
        OrderService $orderService
    ) {
        $order = $orderService->createOrder(
            $request->validated()
        );

        return response()->json([
            'message' => 'Order created successfully',
            'data' => $order->load('customer', 'items'),
        ], 201);
    }
}