<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('sale_id')
                  ->constrained()
                  ->cascadeOnDelete();

            // opcional si luego tienes products
            $table->foreignId('product_id')
                  ->nullable()
                  ->constrained()
                  ->nullOnDelete();

            $table->string('description');
            $table->decimal('quantity', 12, 2);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('tax_percent', 5, 2)->default(18);
            $table->decimal('total', 12, 2); // total del ítem

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_items');
    }
};
