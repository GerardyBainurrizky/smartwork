<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visits', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('route_stop_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('route_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('store_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('in_progress');

            $table->timestamp('check_in_at')->nullable();
            $table->decimal('check_in_lat', 10, 7)->nullable();
            $table->decimal('check_in_lng', 10, 7)->nullable();
            $table->string('check_in_address', 500)->nullable();
            $table->string('check_in_maps_url', 500)->nullable();
            $table->string('storefront_photo', 500)->nullable();
            $table->string('check_in_selfie', 500)->nullable();
            $table->text('delivered_goods')->nullable();
            $table->decimal('cash_received', 15, 2)->nullable();
            $table->text('initial_notes')->nullable();

            $table->timestamp('check_out_at')->nullable();
            $table->decimal('check_out_lat', 10, 7)->nullable();
            $table->decimal('check_out_lng', 10, 7)->nullable();
            $table->string('check_out_address', 500)->nullable();
            $table->string('check_out_maps_url', 500)->nullable();
            $table->text('visit_result')->nullable();
            $table->text('delivered_goods_summary')->nullable();
            $table->text('returned_goods')->nullable();
            $table->decimal('transaction_amount', 15, 2)->nullable();
            $table->text('final_notes')->nullable();
            $table->string('final_store_photo', 500)->nullable();
            $table->string('check_out_selfie', 500)->nullable();

            $table->timestamps();

            $table->index(['user_id', 'check_in_at']);
            $table->index('status');
            $table->index(['store_id', 'check_in_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visits');
    }
};