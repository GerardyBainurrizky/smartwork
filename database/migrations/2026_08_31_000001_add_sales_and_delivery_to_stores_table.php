<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->foreignId('sales_penanggung_jawab_id')
                ->nullable()
                ->after('owner')
                ->constrained('users')
                ->nullOnDelete();
            $table->boolean('is_delivery_destination')
                ->default(true)
                ->after('status');

            $table->index('sales_penanggung_jawab_id');
            $table->index('is_delivery_destination');
        });

        Schema::create('store_submissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name', 200);
            $table->string('owner', 200)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('address', 500);
            $table->string('city', 100);
            $table->string('kecamatan', 100)->nullable();
            $table->string('province', 100);
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('maps_url', 500)->nullable();
            $table->text('notes')->nullable();
            $table->string('photo', 500)->nullable();
            $table->string('status', 20)->default('pending'); // pending, approved, rejected
            $table->text('rejection_reason')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignUuid('store_id')->nullable()->constrained('stores')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('user_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_submissions');

        Schema::table('stores', function (Blueprint $table) {
            $table->dropForeign(['sales_penanggung_jawab_id']);
            $table->dropColumn(['sales_penanggung_jawab_id', 'is_delivery_destination']);
        });
    }
};
