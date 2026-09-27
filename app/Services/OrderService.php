<?php

namespace App\Services;

use App\Jobs\SendOrderConfirmationJob;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    /**
     * Create a new order with concurrency-safe stock deduction.
     *
     * @param array{
     *     customer_email: string,
     *     customer_name: string,
     *     items: array<int, array{product_id: int, quantity: int}>
     * } $data
     *
     * @throws ValidationException
     */
    public function createOrder(array $data): Order
    {
        return DB::transaction(function () use ($data) {

            // Find existing customer by email or create a new customer.
            $customer = Customer::firstOrCreate(
                ['email' => $data['customer_email']],
                ['name' => $data['customer_name']]
            );

            // Combine duplicate product IDs in the request.
            $itemQuantities = [];

            foreach ($data['items'] as $item) {
                $productId = (int) $item['product_id'];
                $quantity = (int) $item['quantity'];

                $itemQuantities[$productId] =
                    ($itemQuantities[$productId] ?? 0) + $quantity;
            }

            // Sort IDs so concurrent requests acquire locks
            // in the same order and reduce deadlock risk.
            $productIds = array_keys($itemQuantities);
            sort($productIds);

            // Lock products until the transaction completes.
            $products = Product::whereIn('id', $productIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            // Check stock after acquiring the locks.
            $stockErrors = [];

            foreach ($itemQuantities as $productId => $requestedQuantity) {
                $product = $products->get($productId);

                if (! $product) {
                    $stockErrors["items.{$productId}"] = [
                        "Product ID {$productId} not found.",
                    ];

                    continue;
                }

                if ($product->stock_on_hand < $requestedQuantity) {
                    $stockErrors["items.{$productId}"] = [
                        "Insufficient stock for product '{$product->name}' ".
                        "(Code: {$product->code}). ".
                        "Available: {$product->stock_on_hand}, ".
                        "Requested: {$requestedQuantity}.",
                    ];
                }
            }

            // Roll back the entire transaction if any product
            // has insufficient stock.
            if (! empty($stockErrors)) {
                throw ValidationException::withMessages($stockErrors);
            }

            $orderSubtotal = 0.0;
            $orderTax = 0.0;
            $orderGrandTotal = 0.0;

            $orderItems = [];

            foreach ($itemQuantities as $productId => $quantity) {
                $product = $products->get($productId);

                $unitPrice = (float) $product->price;
                $taxPercentage = (float) $product->tax_percentage;

                $itemSubtotal = round($quantity * $unitPrice, 2);

                $itemTax = round(
                    $itemSubtotal * ($taxPercentage / 100),
                    2
                );

                $itemTotal = round(
                    $itemSubtotal + $itemTax,
                    2
                );

                $orderSubtotal += $itemSubtotal;
                $orderTax += $itemTax;
                $orderGrandTotal += $itemTotal;

                // Store purchase-time price and tax snapshots.
                $orderItems[] = [
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'tax_percentage' => $taxPercentage,
                    'subtotal' => $itemSubtotal,
                    'tax' => $itemTax,
                    'total' => $itemTotal,
                ];

                // Deduct stock while the product row is locked.
                $product->decrement('stock_on_hand', $quantity);
            }

            // Create the order.
            $order = Order::create([
                'customer_id' => $customer->id,
                'subtotal' => round($orderSubtotal, 2),
                'tax' => round($orderTax, 2),
                'grand_total' => round($orderGrandTotal, 2),
            ]);

            // Create order line items.
            foreach ($orderItems as $item) {
                $order->items()->create($item);
            }

            // Dispatch confirmation job to queue after transaction commits.
            SendOrderConfirmationJob::dispatch($order->id)->afterCommit();

            return $order->load([
                'customer',
                'items.product',
            ]);
        });
    }
}