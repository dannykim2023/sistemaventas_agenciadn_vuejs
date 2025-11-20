<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;



return new class extends Migration {
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();

          

            // Cliente
            $table->foreignId('customer_id')
                  ->constrained()
                  ->cascadeOnDelete();

            // Cotización (opcional)
            $table->foreignId('quotation_id')
                  ->nullable()
                  ->constrained()
                  ->nullOnDelete();

            // Serie y número de documento
            $table->string('series', 10)->default('F001');
            $table->string('number', 20); // luego puedes poner unique si quieres

            // Fechas
            $table->date('issue_date');
            $table->date('due_date')->nullable();

            // Condición de pago
            $table->string('payment_term')->nullable(); // "Contado", "15 días", etc.

            // Moneda
            $table->string('currency', 3)->default('PEN');

            // Totales
            $table->decimal('subtotal', 12, 2);
            $table->decimal('tax', 12, 2);
            $table->decimal('total', 12, 2);

            // Estado de la venta
            $table->string('status', 20)->default('issued'); 
            // draft / issued / partially_paid / paid / cancelled

            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
