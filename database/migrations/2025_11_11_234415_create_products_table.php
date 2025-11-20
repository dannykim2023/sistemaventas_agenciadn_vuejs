<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('products', function (Blueprint $t) {
            $t->id();
            $t->string('sku')->nullable()->unique();
            $t->string('name');
            $t->string('unit', 10)->default('UND');
            $t->decimal('price', 12, 2)->default(0);
            $t->decimal('tax_pct', 5, 4)->default(0.18); // 18% IGV
            $t->boolean('is_service')->default(false);
            $t->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('products');
    }
};
