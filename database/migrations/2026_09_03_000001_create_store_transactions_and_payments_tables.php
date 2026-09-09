<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('store_id')->constrained('stores')->cascadeOnDelete();
            $table->string('transaction_code', 50)->unique();
            $table->date('transaction_date');
            $table->text('description')->nullable();
            $table->decimal('transaction_amount', 15, 2);
            $table->string('status', 30)->default('BELUM_LUNAS'); // BELUM_LUNAS, SEBAGIAN, LUNAS
            $table->string('reference_type', 100)->nullable();
            $table->string('reference_id', 100)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('store_id');
            $table->index('transaction_code');
            $table->index('transaction_date');
            $table->index('status');
            $table->index('created_by');
        });

        Schema::create('store_transaction_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('store_transaction_id')->constrained('store_transactions')->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->string('payment_method', 30); // tunai, transfer, qris
            $table->date('payment_date');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source', 100)->nullable(); // visit, manual, etc.
            $table->string('source_id', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('store_transaction_id');
            $table->index('payment_date');
            $table->index('recorded_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_transaction_payments');
        Schema::dropIfExists('store_transactions');
    }
};
