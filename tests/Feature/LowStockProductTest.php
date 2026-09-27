<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LowStockProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_only_products_at_or_below_low_stock_threshold(): void
    {
        config(['inventory.low_stock_threshold' => 5]);

        // Below threshold
        $productLow1 = Product::factory()->create([
            'name' => 'Low Stock Product 1',
            'stock_on_hand' => 2,
        ]);

        // Equal to threshold
        $productLow2 = Product::factory()->create([
            'name' => 'Low Stock Product 2',
            'stock_on_hand' => 5,
        ]);

        // Above threshold
        $productNormal = Product::factory()->create([
            'name' => 'Normal Stock Product',
            'stock_on_hand' => 10,
        ]);

        $response = $this->getJson('/api/products/low-stock');

        $response->assertStatus(200)
            ->assertJsonPath('meta.threshold', 5)
            ->assertJsonPath('meta.total', 2)
            ->assertJsonCount(2, 'data');

        $returnedIds = collect($response->json('data'))->pluck('id')->all();

        $this->assertContains($productLow1->id, $returnedIds);
        $this->assertContains($productLow2->id, $returnedIds);
        $this->assertNotContains($productNormal->id, $returnedIds);
    }
}
