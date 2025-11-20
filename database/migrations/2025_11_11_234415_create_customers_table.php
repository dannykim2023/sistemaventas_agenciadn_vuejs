<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('customers', function (Blueprint $t) {
            $t->id();
            $t->enum('type', ['person', 'company'])->default('company');
            $t->enum('document_type', ['DNI','RUC','CE','PAS'])->nullable();
            $t->string('document_number', 15)->nullable();
            $t->string('name');
            $t->string('trade_name')->nullable();
            $t->string('email')->nullable();
            $t->string('phone', 30)->nullable();
            $t->string('address')->nullable();
            $t->string('district')->nullable();
            $t->string('province')->nullable();
            $t->string('department')->nullable();
            $t->text('notes')->nullable();
            $t->boolean('is_active')->default(true);
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();

            $t->unique(['document_type','document_number']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('customers');
    }
};
