<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->decimal('price', 12, 2);
            $table->decimal('tax_percentage', 5, 2);
            $table->integer('stock_on_hand');
            $table->timestamps();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(
                'ALTER TABLE products ADD CONSTRAINT products_stock_on_hand_check CHECK (stock_on_hand >= 0)'
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};