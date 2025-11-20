<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('quotation_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('quotation_id')->constrained()->cascadeOnDelete();
            $t->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $t->string('description');
            $t->decimal('quantity', 12, 3)->default(1);
            $t->string('unit', 10)->default('UND');
            $t->decimal('unit_price', 12, 2)->default(0);
            $t->decimal('discount_pct', 5, 2)->default(0);  // % 0-100
            $t->decimal('discount_amount', 12, 2)->default(0);
            $t->decimal('tax_pct', 5, 4)->default(0.18);
            $t->decimal('tax_amount', 12, 2)->default(0);
            $t->decimal('line_total', 12, 2)->default(0);
            $t->unsignedInteger('sort_order')->default(0);
            $t->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('quotation_items');
    }
};
