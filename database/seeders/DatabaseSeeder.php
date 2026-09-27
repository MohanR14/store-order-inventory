<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Predefined realistic sample customers
        $sampleCustomers = [
            [
                'name' => 'Alice Smith',
                'email' => 'alice.smith@example.com',
            ],
            [
                'name' => 'Bob Jones',
                'email' => 'bob.jones@example.com',
            ],
            [
                'name' => 'Charlie Brown',
                'email' => 'charlie.brown@example.com',
            ],
        ];

        foreach ($sampleCustomers as $customerData) {
            Customer::firstOrCreate(['email' => $customerData['email']], $customerData);
        }

        // Predefined realistic sample products
        $sampleProducts = [
            [
                'name' => 'Wireless Mechanical Keyboard',
                'code' => 'PRD-10001',
                'price' => 120.00,
                'tax_percentage' => 10.00,
                'stock_on_hand' => 50,
            ],
            [
                'name' => 'Ergonomic Wireless Mouse',
                'code' => 'PRD-10002',
                'price' => 45.00,
                'tax_percentage' => 10.00,
                'stock_on_hand' => 100,
            ],
            [
                'name' => '27-inch 4K Monitor',
                'code' => 'PRD-10003',
                'price' => 399.99,
                'tax_percentage' => 18.00,
                'stock_on_hand' => 25,
            ],
            [
                'name' => 'USB-C Multiport Adapter',
                'code' => 'PRD-10004',
                'price' => 29.50,
                'tax_percentage' => 5.00,
                'stock_on_hand' => 200,
            ],
            [
                'name' => 'Noise Canceling Headphones',
                'code' => 'PRD-10005',
                'price' => 199.00,
                'tax_percentage' => 18.00,
                'stock_on_hand' => 15,
            ],
        ];

        foreach ($sampleProducts as $productData) {
            Product::firstOrCreate(['code' => $productData['code']], $productData);
        }

        // Additional random seed data
        Customer::factory(10)->create();
        Product::factory(15)->create();
    }
}
