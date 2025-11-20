<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $t->date('payment_date');
            $t->decimal('amount', 12, 2);
            $t->string('method', 30)->nullable();    // cash, transfer, yape, etc.
            $t->string('reference', 191)->nullable();
            $t->text('notes')->nullable();
            $t->timestamps();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
