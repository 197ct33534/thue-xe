<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->enum('payment_method', ['cash', 'bank_transfer', 'momo', 'vnpay', 'credit_card']);
            $table->enum('payment_type', ['deposit', 'full_payment', 'remaining', 'refund']);
            $table->decimal('amount', 12, 2);
            $table->string('transaction_id', 255)->nullable();
            $table->enum('payment_status', ['pending', 'completed', 'failed', 'refunded'])->default('pending');
            $table->timestamp('payment_date')->nullable();
            $table->text('notes')->nullable();
            $table->string('receipt_url', 500)->nullable();
            $table->timestamps();

            $table->index(['booking_id', 'payment_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};