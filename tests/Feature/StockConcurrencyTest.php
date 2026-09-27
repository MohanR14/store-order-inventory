<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test concurrency-safe stock deduction behavior.
     *
     * Business Scenario:
     * Product has stock_on_hand = 1. Two order attempts both request quantity = 1.
     *
     * Expected Result:
     * Exactly one order attempt succeeds (HTTP 201), consuming the single available stock unit.
     * The second order attempt fails cleanly (HTTP 422 Insufficient Stock), leaving stock_on_hand = 0
     * and preventing negative inventory or over-selling.
     *
     * Architecture / Locking Note:
     * In production (PostgreSQL), `Product::whereIn(...)->orderBy('id')->lockForUpdate()` acquires
     * exclusive row-level pessimistic locks (`SELECT ... FOR UPDATE`).
     * If two HTTP worker processes execute concurrently, PostgreSQL blocks the second process
     * until the first process commits its transaction. Once unblocked, the second process reads the updated
     * `stock_on_hand` (0), detects insufficient stock, and rolls back cleanly.
     *
     * In this automated test suite, we simulate sequential execution of the two competing requests
     * across the transactional boundaries to verify that post-lock stock checks properly prevent over-selling.
     */
    public function test_prevents_over_selling_when_two_requests_compete_for_single_stock_unit(): void
    {
        $product = Product::factory()->create([
            'name' => 'Limited Stock Item',
            'stock_on_hand' => 1,
            'price' => 50.00,
            'tax_percentage' => 10.00,
        ]);

        $payloadAttempt1 = [
            'customer_email' => 'buyer1@example.com',
            'customer_name' => 'First Buyer',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ];

        $payloadAttempt2 = [
            'customer_email' => 'buyer2@example.com',
            'customer_name' => 'Second Buyer',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ];

        // Attempt 1: Consumes the 1 available stock unit
        $response1 = $this->postJson('/api/orders', $payloadAttempt1);
        $response1->assertStatus(201);

        // Verify stock is now 0 after Attempt 1
        $this->assertEquals(0, $product->fresh()->stock_on_hand);

        // Attempt 2: Must fail due to zero remaining stock
        $response2 = $this->postJson('/api/orders', $payloadAttempt2);
        $response2->assertStatus(422)
            ->assertJsonValidationErrors(["items.{$product->id}"]);

        // Final verification: Stock remains 0 and exactly 1 order exists in database
        $this->assertEquals(0, $product->fresh()->stock_on_hand);
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseHas('orders', [
            'customer_id' => \App\Models\Customer::where('email', 'buyer1@example.com')->value('id'),
        ]);
    }
}
