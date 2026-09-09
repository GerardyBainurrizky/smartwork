<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_receivables', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('store_id')->constrained('stores')->cascadeOnDelete();
            $table->string('type', 20);
            $table->decimal('amount', 15, 2);
            $table->string('reference_type', 100)->nullable();
            $table->string('reference_id', 100)->nullable();
            $table->date('transaction_date');
            $table->string('payment_method', 30)->nullable();
            $table->string('allocation', 30)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('store_id');
            $table->index('type');
            $table->index('transaction_date');
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_receivables');
    }
};
