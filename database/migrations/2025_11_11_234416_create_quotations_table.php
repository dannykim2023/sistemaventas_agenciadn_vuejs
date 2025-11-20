<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('quotations', function (Blueprint $t) {
            $t->id();
            $t->string('number')->unique(); // COT-000001
            $t->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $t->date('issue_date');
            $t->date('valid_until')->nullable();
            $t->enum('currency', ['PEN','USD'])->default('PEN');
            $t->decimal('exchange_rate', 12, 6)->nullable();
            $t->enum('status', ['draft','sent','accepted','rejected','expired'])->default('draft');
            $t->boolean('tax_included')->default(true);
            $t->decimal('igv_rate', 5, 4)->default(0.18);
            $t->decimal('discount_total', 12, 2)->default(0);
            $t->decimal('subtotal', 12, 2)->default(0);
            $t->decimal('tax_total', 12, 2)->default(0);
            $t->decimal('total', 12, 2)->default(0);
            $t->text('notes')->nullable();
            $t->text('terms')->nullable();
            $t->timestamp('sent_at')->nullable();
            $t->timestamp('accepted_at')->nullable();
            $t->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('quotations');
    }
};
