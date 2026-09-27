<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerOrderHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_fetch_order_history_for_existing_customer(): void
    {
        $customer = Customer::factory()->create([
            'email' => 'alice@example.com',
            'name' => 'Alice Smith',
        ]);

        $product = Product::factory()->create([
            'name' => 'Mechanical Keyboard',
            'code' => 'PRD-100',
            'price' => 120.00,
        ]);

        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'subtotal' => 240.00,
            'tax' => 24.00,
            'grand_total' => 264.00,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 120.00,
            'tax_percentage' => 10.00,
            'subtotal' => 240.00,
            'tax' => 24.00,
            'total' => 264.00,
        ]);

        $response = $this->getJson('/api/customers/orders?email=alice@example.com');

        $response->assertStatus(200)
            ->assertJsonPath('data.customer.email', 'alice@example.com')
            ->assertJsonPath('data.customer.name', 'Alice Smith')
            ->assertJsonCount(1, 'data.orders')
            ->assertJsonPath('data.orders.0.grand_total', '264.00')
            ->assertJsonPath('data.orders.0.items.0.product.code', 'PRD-100');
    }

    public function test_returns_404_when_customer_email_not_found(): void
    {
        $response = $this->getJson('/api/customers/orders?email=nonexistent@example.com');

        $response->assertStatus(404)
            ->assertJsonPath('message', 'Customer not found.');
    }

    public function test_validates_missing_email_query_param(): void
    {
        $response = $this->getJson('/api/customers/orders');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }
}
