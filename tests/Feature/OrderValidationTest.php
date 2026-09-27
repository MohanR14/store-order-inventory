<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_validates_missing_customer_email(): void
    {
        $product = Product::factory()->create();

        $response = $this->postJson('/api/orders', [
            'customer_name' => 'John Doe',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['customer_email']);
    }

    public function test_validates_invalid_customer_email_format(): void
    {
        $product = Product::factory()->create();

        $response = $this->postJson('/api/orders', [
            'customer_email' => 'invalid-email-format',
            'customer_name' => 'John Doe',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['customer_email']);
    }

    public function test_validates_missing_customer_name(): void
    {
        $product = Product::factory()->create();

        $response = $this->postJson('/api/orders', [
            'customer_email' => 'john@example.com',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['customer_name']);
    }

    public function test_validates_empty_items_array(): void
    {
        $response = $this->postJson('/api/orders', [
            'customer_email' => 'john@example.com',
            'customer_name' => 'John Doe',
            'items' => [],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items']);
    }

    public function test_validates_non_existent_product_id(): void
    {
        $response = $this->postJson('/api/orders', [
            'customer_email' => 'john@example.com',
            'customer_name' => 'John Doe',
            'items' => [['product_id' => 99999, 'quantity' => 1]],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items.0.product_id']);
    }

    public function test_validates_quantity_less_than_one(): void
    {
        $product = Product::factory()->create();

        $response = $this->postJson('/api/orders', [
            'customer_email' => 'john@example.com',
            'customer_name' => 'John Doe',
            'items' => [['product_id' => $product->id, 'quantity' => 0]],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items.0.quantity']);
    }

    public function test_fails_cleanly_when_insufficient_stock(): void
    {
        $product = Product::factory()->create([
            'stock_on_hand' => 3,
        ]);

        $payload = [
            'customer_email' => 'john@example.com',
            'customer_name' => 'John Doe',
            'items' => [['product_id' => $product->id, 'quantity' => 5]],
        ];

        $response = $this->postJson('/api/orders', $payload);

        $response->assertStatus(422);

        // Assert no order or order items created in database
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);

        // Assert product stock remains untouched (3)
        $this->assertEquals(3, $product->fresh()->stock_on_hand);
    }
}
