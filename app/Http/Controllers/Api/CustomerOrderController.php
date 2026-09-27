<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GetCustomerOrderHistoryRequest;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;

class CustomerOrderController extends Controller
{
    /**
     * Display order history for a specific customer by email.
     */
    public function index(GetCustomerOrderHistoryRequest $request): JsonResponse
    {
        $customer = Customer::where('email', $request->validated('email'))->first();

        if (! $customer) {
            return response()->json([
                'message' => 'Customer not found.',
            ], 404);
        }

        $orders = $customer->orders()
            ->with(['items.product'])
            ->latest()
            ->get();

        return response()->json([
            'data' => [
                'customer' => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'email' => $customer->email,
                ],
                'orders' => $orders,
            ],
        ]);
    }
}
