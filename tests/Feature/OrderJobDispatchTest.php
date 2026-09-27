<?php

namespace Tests\Feature;

use App\Jobs\SendOrderConfirmationJob;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class OrderJobDispatchTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispatches_send_order_confirmation_job_on_successful_order(): void
    {
        Queue::fake();

        $product = Product::factory()->create(['stock_on_hand' => 10]);

        $payload = [
            'customer_email' => 'alice@example.com',
            'customer_name' => 'Alice Smith',
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ];

        $response = $this->postJson('/api/orders', $payload);

        $response->assertStatus(201);

        $orderId = $response->json('data.id');

        Queue::assertPushed(SendOrderConfirmationJob::class, function ($job) use ($orderId) {
            return $job->orderId === $orderId;
        });
    }

    public function test_does_not_dispatch_job_when_order_fails_due_to_insufficient_stock(): void
    {
        Queue::fake();

        $product = Product::factory()->create(['stock_on_hand' => 1]);

        $payload = [
            'customer_email' => 'alice@example.com',
            'customer_name' => 'Alice Smith',
            'items' => [['product_id' => $product->id, 'quantity' => 5]],
        ];

        $response = $this->postJson('/api/orders', $payload);

        $response->assertStatus(422);

        Queue::assertNotPushed(SendOrderConfirmationJob::class);
    }

    public function test_send_order_confirmation_job_logs_order_details(): void
    {
        Log::shouldReceive('info')
            ->once()
            ->with('Order Confirmation Sent', \Mockery::on(function ($context) {
                return isset($context['order_id']) &&
                       $context['customer_email'] === 'test@example.com' &&
                       $context['grand_total'] == 110.00;
            }));

        $customer = Customer::factory()->create(['email' => 'test@example.com']);
        $product = Product::factory()->create(['price' => 100.00, 'tax_percentage' => 10.00]);
        $order = Order::factory()->create(['customer_id' => $customer->id, 'grand_total' => 110.00]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 100.00,
            'tax_percentage' => 10.00,
            'subtotal' => 100.00,
            'tax' => 10.00,
            'total' => 110.00,
        ]);

        $job = new SendOrderConfirmationJob($order->id);
        $job->handle();
    }
}
