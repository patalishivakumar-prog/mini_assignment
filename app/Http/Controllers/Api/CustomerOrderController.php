<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerOrderController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $customer = Customer::where(
            'email',
            $request->query('email')
        )->first();

        if (!$customer) {
            return response()->json([
                'message' => 'Customer not found',
            ], 404);
        }

        $orders = $customer->orders()
            ->with('items')
            ->latest()
            ->get();

        return response()->json([
            'customer' => $customer,
            'orders' => $orders,
        ], 200);
    }
}