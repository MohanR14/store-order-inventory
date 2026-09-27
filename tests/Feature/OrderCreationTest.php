<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_successfully_create_an_order(): void
    {
        $product = Product::factory()->create([
            'name' => 'Test Keyboard',
            'code' => 'PRD-001',
            'price' => 100.00,
            'tax_percentage' => 10.00,
            'stock_on_hand' => 10,
        ]);

        $payload = [
            'customer_email' => 'alice@example.com',
            'customer_name' => 'Alice Smith',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                ],
            ],
        ];

        $response = $this->postJson('/api/orders', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('message', 'Order created successfully.')
            ->assertJsonPath('data.customer.email', 'alice@example.com');

        // Assert customer was created
        $this->assertDatabaseHas('customers', [
            'email' => 'alice@example.com',
            'name' => 'Alice Smith',
        ]);

        $customer = Customer::where('email', 'alice@example.com')->firstOrFail();

        // Assert order was created with correct calculations:
        // subtotal = 2 * 100 = 200.00
        // tax = 200.00 * (10 / 100) = 20.00
        // grand_total = 220.00
        $this->assertDatabaseHas('orders', [
            'customer_id' => $customer->id,
            'subtotal' => 200.00,
            'tax' => 20.00,
            'grand_total' => 220.00,
        ]);

        $order = Order::where('customer_id', $customer->id)->firstOrFail();

        // Assert order item snapshot values
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 100.00,
            'tax_percentage' => 10.00,
            'subtotal' => 200.00,
            'tax' => 20.00,
            'total' => 220.00,
        ]);

        // Assert stock was deducted (10 - 2 = 8)
        $this->assertEquals(8, $product->fresh()->stock_on_hand);
    }

    public function test_can_create_order_with_multiple_different_products(): void
    {
        $productA = Product::factory()->create([
            'price' => 100.00,
            'tax_percentage' => 10.00,
            'stock_on_hand' => 10,
        ]);

        $productB = Product::factory()->create([
            'price' => 50.00,
            'tax_percentage' => 18.00,
            'stock_on_hand' => 10,
        ]);

        $payload = [
            'customer_email' => 'bob@example.com',
            'customer_name' => 'Bob Jones',
            'items' => [
                ['product_id' => $productA->id, 'quantity' => 2],
                ['product_id' => $productB->id, 'quantity' => 1],
            ],
        ];

        $response = $this->postJson('/api/orders', $payload);

        $response->assertStatus(201);

        // Product A: 2 * 100 = 200 subtotal, 20 tax, 220 total
        // Product B: 1 * 50 = 50 subtotal, 9 tax, 59 total
        // Order totals: subtotal = 250.00, tax = 29.00, grand_total = 279.00
        $this->assertDatabaseHas('orders', [
            'subtotal' => 250.00,
            'tax' => 29.00,
            'grand_total' => 279.00,
        ]);

        $this->assertEquals(8, $productA->fresh()->stock_on_hand);
        $this->assertEquals(9, $productB->fresh()->stock_on_hand);
    }

    public function test_combines_duplicate_product_ids_in_same_request(): void
    {
        $product = Product::factory()->create([
            'price' => 40.00,
            'tax_percentage' => 10.00,
            'stock_on_hand' => 20,
        ]);

        $payload = [
            'customer_email' => 'charlie@example.com',
            'customer_name' => 'Charlie Brown',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2],
                ['product_id' => $product->id, 'quantity' => 3],
            ],
        ];

        $response = $this->postJson('/api/orders', $payload);

        $response->assertStatus(201);

        // Combined quantity = 5. Subtotal = 200, Tax = 20, Grand Total = 220
        $this->assertDatabaseHas('orders', [
            'subtotal' => 200.00,
            'tax' => 20.00,
            'grand_total' => 220.00,
        ]);

        $this->assertDatabaseHas('order_items', [
            'product_id' => $product->id,
            'quantity' => 5,
        ]);

        // Stock deducted by combined quantity (20 - 5 = 15)
        $this->assertEquals(15, $product->fresh()->stock_on_hand);
    }
}
